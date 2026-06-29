<?php

namespace App\Application\Quiz\Services;

use App\Application\Quiz\Repositories\AttemptRepositoryInterface;
use App\Application\Quiz\Jobs\ProcessQuizSubmission;
use App\Domain\Quiz\DTOs\SubmitAttemptDTO;
use App\Domain\Quiz\Enums\AttemptStatus;
use App\Domain\Quiz\Events\CertificateEligible;
use App\Domain\Quiz\Events\QuizAttemptStarted;
use App\Domain\Quiz\Events\QuizAttemptSubmitted;
use App\Domain\Quiz\Events\QuizFailed;
use App\Domain\Quiz\Events\QuizPassed;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizResult;
use App\Models\LessonProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class QuizAttemptService
{
    public function __construct(
        private readonly AttemptRepositoryInterface $attemptRepository,
        private readonly QuizSecurityService        $securityService,
        private readonly GradingService             $gradingService,
        private readonly AnalyticsService           $analyticsService,
    ) {}

    /**
     * Start a new quiz attempt for a student.
     */
    public function startAttempt(Quiz $quiz, int $userId, string $ip, string $userAgent): QuizAttempt
    {
        return DB::transaction(function () use ($quiz, $userId, $ip, $userAgent) {
            // Cancel any active in-progress attempt before starting a new one
            $activeAttempt = $this->attemptRepository->getActiveAttempt($quiz->id, $userId);
            if ($activeAttempt) {
                $this->attemptRepository->update($activeAttempt, [
                    'status' => AttemptStatus::Abandoned->value,
                ]);
            }

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
     * التصحيح يتم بشكل مباشر (synchronous) لأن الاستضافة الحالية لا تدعم queue workers.
     * الـ Job (ProcessQuizSubmission) محفوظ للاستخدام مستقبلاً عند توفر استضافة تدعم queues.
     */
    public function submit(SubmitAttemptDTO $dto): QuizAttempt
    {
        $attempt = DB::transaction(function () use ($dto) {
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

            return $attempt;
        });

        // Grade immediately (synchronous) — no queue worker required
        $this->gradeAttemptSync($attempt);

        return $attempt->fresh();
    }

    /**
     * Run grading synchronously (بديل مؤقت عن الـ Queue).
     * عند الانتقال لاستضافة تدعم queues، استبدل هذا باستخدام ProcessQuizSubmission::dispatch()
     */
    private function gradeAttemptSync(QuizAttempt $attempt): void
    {
        try {
            // Prevent re-grading
            if ($attempt->result()->exists()) {
                return;
            }

            $attempt->load(['quiz', 'answers.question.options', 'user']);

            $resultDTO = $this->gradingService->grade($attempt);

            $result = QuizResult::create([
                'quiz_id'              => $resultDTO->quizId,
                'attempt_id'           => $resultDTO->attemptId,
                'user_id'              => $resultDTO->userId,
                'raw_score'            => $resultDTO->rawScore,
                'max_score'            => $resultDTO->maxScore,
                'final_score'          => $resultDTO->rawScore,
                'percentage'           => $resultDTO->percentage,
                'duration_seconds'     => $resultDTO->durationSeconds,
                'passed'               => $resultDTO->passed,
                'certificate_eligible' => $resultDTO->certificateEligible,
                'attempt_number'       => $resultDTO->attemptNumber,
                'created_at'           => now(),
            ]);

            if ($resultDTO->passed) {
                event(new QuizPassed($result));
                if ($resultDTO->certificateEligible) {
                    event(new CertificateEligible($result));
                }
            } else {
                event(new QuizFailed($result));
            }

            $attempt->refresh();
            $this->analyticsService->updateAfterSubmission($attempt);

        } catch (\Throwable $e) {
            Log::error("Synchronous grading failed for attempt {$attempt->id}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
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

            // Grade synchronously (no queue worker on current hosting)
            $this->gradeAttemptSync($attempt->fresh());

            return $attempt->fresh();
        }

        return $attempt;
    }
}
