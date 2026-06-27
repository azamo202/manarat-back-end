<?php

namespace App\Application\Quiz\Services;

use App\Application\Quiz\Repositories\AttemptRepositoryInterface;
use App\Application\Quiz\Jobs\ProcessQuizSubmission;
use App\Domain\Quiz\DTOs\SubmitAttemptDTO;
use App\Domain\Quiz\Enums\AttemptStatus;
use App\Domain\Quiz\Events\QuizAttemptStarted;
use App\Domain\Quiz\Events\QuizAttemptSubmitted;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\LessonProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizAttemptService
{
    public function __construct(
        private readonly AttemptRepositoryInterface $attemptRepository,
        private readonly QuizSecurityService        $securityService,
    ) {}

    /**
     * Start a new quiz attempt for a student.
     */
    public function startAttempt(Quiz $quiz, int $userId, string $ip, string $userAgent): QuizAttempt
    {
        return DB::transaction(function () use ($quiz, $userId, $ip, $userAgent) {
            // Security checks
            $this->securityService->assertCanAttempt($quiz, $userId);

            // Determine shuffled question order
            $questions = $quiz->questions()->orderByPivot('order_number')->get();
            $questionIds = $questions->pluck('id')->toArray();

            $shuffledQuestionOrder  = null;
            $shuffledAnswerOrders   = null;

            if ($quiz->shuffle_questions) {
                shuffle($questionIds);
                $shuffledQuestionOrder = $questionIds;
            }

            if ($quiz->shuffle_answers) {
                $shuffledAnswerOrders = [];
                foreach ($questions as $question) {
                    if ($question->options->count() > 0) {
                        $optionIds = $question->options->pluck('id')->toArray();
                        shuffle($optionIds);
                        $shuffledAnswerOrders[$question->id] = $optionIds;
                    }
                }
            }

            $expiresAt = $quiz->time_limit_minutes
                ? now()->addMinutes($quiz->time_limit_minutes)
                : null;

            $attempt = $this->attemptRepository->create([
                'quiz_id'                  => $quiz->id,
                'user_id'                  => $userId,
                'status'                   => AttemptStatus::InProgress->value,
                'started_at'               => now(),
                'time_limit_expires_at'    => $expiresAt,
                'shuffled_question_order'  => $shuffledQuestionOrder,
                'shuffled_answer_orders'   => $shuffledAnswerOrders,
                'ip_address'               => $ip,
                'user_agent'               => $userAgent,
            ]);

            event(new QuizAttemptStarted($attempt));

            return $attempt;
        });
    }

    /**
     * Auto-save one or more answers (idempotent — upsert).
     */
    public function saveAnswers(QuizAttempt $attempt, array $answers): void
    {
        // Security: reject saves on terminal attempts or expired timer
        $this->securityService->assertAttemptIsActive($attempt);

        foreach ($answers as $answerData) {
            QuizAnswer::updateOrCreate(
                [
                    'attempt_id'  => $attempt->id,
                    'question_id' => $answerData['question_id'],
                ],
                [
                    'answer_value'        => $answerData['answer_value'] ?? null,
                    'time_spent_seconds'  => $answerData['time_spent_seconds'] ?? 0,
                    'answered_at'         => now(),
                ],
            );
        }
    }

    /**
     * Submit the attempt for grading.
     */
    public function submit(SubmitAttemptDTO $dto): QuizAttempt
    {
        return DB::transaction(function () use ($dto) {
            $attempt = $this->attemptRepository->findByIdOrFail($dto->attemptId);

            // Security checks
            $this->securityService->assertAttemptBelongsToUser($attempt, $dto->userId);
            $this->securityService->assertAttemptIsActive($attempt);

            // Save final answers
            if (!empty($dto->answers)) {
                $this->saveAnswers($attempt, $dto->answers);
            }

            // Mark as submitted
            $attempt = $this->attemptRepository->update($attempt, [
                'status'       => AttemptStatus::Submitted->value,
                'submitted_at' => now(),
            ]);

            event(new QuizAttemptSubmitted($attempt));

            // Dispatch async grading job
            ProcessQuizSubmission::dispatch($attempt->id);

            return $attempt;
        });
    }

    /**
     * Resume an existing in-progress attempt (for page refresh recovery).
     */
    public function resumeAttempt(QuizAttempt $attempt): QuizAttempt
    {
        // If timer expired, auto-submit
        if ($attempt->isTimerExpired()) {
            $this->attemptRepository->update($attempt, [
                'status' => AttemptStatus::TimedOut->value,
            ]);

            ProcessQuizSubmission::dispatch($attempt->id);

            return $attempt->fresh();
        }

        return $attempt;
    }
}
