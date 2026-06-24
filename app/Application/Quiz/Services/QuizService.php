<?php

namespace App\Application\Quiz\Services;

use App\Application\Quiz\Repositories\QuizRepositoryInterface;
use App\Domain\Quiz\DTOs\CreateQuizDTO;
use App\Domain\Quiz\DTOs\UpdateQuizDTO;
use App\Domain\Quiz\Enums\QuizStatus;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizService
{
    public function __construct(
        private readonly QuizRepositoryInterface $quizRepository,
    ) {}

    public function create(CreateQuizDTO $dto): Quiz
    {
        return $this->quizRepository->create([
            'title'                  => $dto->title,
            'description'            => $dto->description,
            'lesson_id'              => $dto->lessonId,
            'course_id'              => $dto->courseId,
            'category'               => $dto->category,
            'difficulty'             => $dto->difficulty->value,
            'passing_score'          => $dto->passingScore,
            'time_limit_minutes'     => $dto->timeLimitMinutes,
            'max_attempts'           => $dto->maxAttempts,
            'shuffle_questions'      => $dto->shuffleQuestions,
            'shuffle_answers'        => $dto->shuffleAnswers,
            'show_correct_answers'   => $dto->showCorrectAnswers,
            'show_score_after_submit'=> $dto->showScoreAfterSubmit,
            'active_from'            => $dto->activeFrom,
            'active_until'           => $dto->activeUntil,
            'status'                 => QuizStatus::Draft->value,
            'created_by'             => $dto->createdBy,
        ]);
    }

    public function update(Quiz $quiz, UpdateQuizDTO $dto): Quiz
    {
        return $this->quizRepository->update($quiz, [
            'title'                  => $dto->title,
            'description'            => $dto->description,
            'lesson_id'              => $dto->lessonId,
            'course_id'              => $dto->courseId,
            'category'               => $dto->category,
            'difficulty'             => $dto->difficulty->value,
            'passing_score'          => $dto->passingScore,
            'time_limit_minutes'     => $dto->timeLimitMinutes,
            'max_attempts'           => $dto->maxAttempts,
            'shuffle_questions'      => $dto->shuffleQuestions,
            'shuffle_answers'        => $dto->shuffleAnswers,
            'show_correct_answers'   => $dto->showCorrectAnswers,
            'show_score_after_submit'=> $dto->showScoreAfterSubmit,
            'active_from'            => $dto->activeFrom,
            'active_until'           => $dto->activeUntil,
        ]);
    }

    public function publish(Quiz $quiz): Quiz
    {
        if (!$quiz->status->canTransitionTo(QuizStatus::Published)) {
            throw ValidationException::withMessages([
                'status' => ['لا يمكن نشر الاختبار من حالته الحالية.'],
            ]);
        }

        if ($quiz->questions()->count() === 0) {
            throw ValidationException::withMessages([
                'questions' => ['يجب إضافة سؤال واحد على الأقل قبل النشر.'],
            ]);
        }

        return $this->quizRepository->update($quiz, ['status' => QuizStatus::Published->value]);
    }

    public function archive(Quiz $quiz): Quiz
    {
        if (!$quiz->status->canTransitionTo(QuizStatus::Archived)) {
            throw ValidationException::withMessages([
                'status' => ['لا يمكن أرشفة الاختبار من حالته الحالية.'],
            ]);
        }

        return $this->quizRepository->update($quiz, ['status' => QuizStatus::Archived->value]);
    }

    public function duplicate(Quiz $quiz, int $userId): Quiz
    {
        return DB::transaction(function () use ($quiz, $userId) {
            return $this->quizRepository->duplicate($quiz, $userId);
        });
    }

    public function delete(Quiz $quiz): void
    {
        $this->quizRepository->delete($quiz);
    }

    /**
     * Sync questions to a quiz with ordering and optional points override.
     *
     * @param array $questions  Array of ['question_id' => int, 'points_override' => ?int]
     */
    public function syncQuestions(Quiz $quiz, array $questions): void
    {
        $syncData = [];
        foreach ($questions as $index => $item) {
            $syncData[$item['question_id']] = [
                'order_number'    => $index + 1,
                'points_override' => $item['points_override'] ?? null,
            ];
        }

        $quiz->questions()->sync($syncData);
    }
}
