<?php

namespace App\Application\Quiz\Services;

use App\Application\Quiz\Repositories\AttemptRepositoryInterface;
use App\Domain\Quiz\Enums\AttemptStatus;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Validation\ValidationException;

class QuizSecurityService
{
    public function __construct(
        private readonly AttemptRepositoryInterface $attemptRepository,
    ) {}

    /**
     * Assert a student can start a new attempt.
     * Checks: availability window, enrollment, attempts limit, no active attempt.
     */
    public function assertCanAttempt(Quiz $quiz, int $userId): void
    {
        // 1. Quiz must be available
        if (!$quiz->isAvailable()) {
            throw ValidationException::withMessages([
                'quiz' => ['هذا الاختبار غير متاح حالياً.'],
            ]);
        }

        // 2. Check enrollment via lesson_progress
        if ($quiz->lesson_id) {
            $enrolled = LessonProgress::where('user_id', $userId)
                                      ->where('lesson_id', $quiz->lesson_id)
                                      ->exists();

            if (!$enrolled) {
                throw ValidationException::withMessages([
                    'enrollment' => ['يجب الالتحاق بالدرس أولاً قبل إجراء الاختبار.'],
                ]);
            }
        }

        // 3. No active in-progress attempt
        $activeAttempt = $this->attemptRepository->getActiveAttempt($quiz->id, $userId);

        if ($activeAttempt) {
            throw ValidationException::withMessages([
                'attempt' => ['لديك محاولة جارية بالفعل. يرجى إتمامها أولاً.'],
            ]);
        }

        // 4. Check attempts limit (0 = unlimited)
        if ($quiz->max_attempts > 0) {
            $completed = $this->attemptRepository->countCompletedAttempts($quiz->id, $userId);

            if ($completed >= $quiz->max_attempts) {
                throw ValidationException::withMessages([
                    'attempts' => ['لقد استنفدت جميع محاولاتك لهذا الاختبار.'],
                ]);
            }
        }
    }

    /**
     * Assert an attempt is still active (not terminal and timer not expired).
     */
    public function assertAttemptIsActive(QuizAttempt $attempt): void
    {
        if ($attempt->isTerminal()) {
            throw ValidationException::withMessages([
                'attempt' => ['هذه المحاولة مُغلقة ولا يمكن تعديلها.'],
            ]);
        }

        if ($attempt->isTimerExpired()) {
            throw ValidationException::withMessages([
                'attempt' => ['انتهى وقت الاختبار.'],
            ]);
        }
    }

    /**
     * Assert the attempt belongs to the requesting user.
     */
    public function assertAttemptBelongsToUser(QuizAttempt $attempt, int $userId): void
    {
        if ($attempt->user_id !== $userId) {
            throw ValidationException::withMessages([
                'attempt' => ['لا تملك صلاحية الوصول إلى هذه المحاولة.'],
            ]);
        }
    }
}
