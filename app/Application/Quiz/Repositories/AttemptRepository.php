<?php

namespace App\Application\Quiz\Repositories;

use App\Domain\Quiz\Enums\AttemptStatus;
use App\Models\QuizAttempt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AttemptRepository implements AttemptRepositoryInterface
{
    public function findById(int $id): ?QuizAttempt
    {
        return QuizAttempt::with(['quiz', 'user', 'answers.question', 'result'])->find($id);
    }

    public function findByIdOrFail(int $id): QuizAttempt
    {
        return QuizAttempt::with(['quiz', 'user', 'answers.question', 'result'])->findOrFail($id);
    }

    public function getActiveAttempt(int $quizId, int $userId): ?QuizAttempt
    {
        return QuizAttempt::where('quiz_id', $quizId)
                          ->where('user_id', $userId)
                          ->where('status', AttemptStatus::InProgress)
                          ->latest()
                          ->first();
    }

    public function countCompletedAttempts(int $quizId, int $userId): int
    {
        return QuizAttempt::where('quiz_id', $quizId)
                          ->where('user_id', $userId)
                          ->whereIn('status', [
                              AttemptStatus::Submitted,
                              AttemptStatus::TimedOut,
                          ])
                          ->count();
    }

    public function getUserAttempts(int $quizId, int $userId): Collection
    {
        return QuizAttempt::where('quiz_id', $quizId)
                          ->where('user_id', $userId)
                          ->with('result')
                          ->orderBy('started_at', 'desc')
                          ->get();
    }

    public function getAllAttemptsForQuiz(int $quizId, int $perPage = 20): LengthAwarePaginator
    {
        return QuizAttempt::where('quiz_id', $quizId)
                          ->with(['user', 'result'])
                          ->orderBy('started_at', 'desc')
                          ->paginate($perPage);
    }

    public function create(array $data): QuizAttempt
    {
        return QuizAttempt::create($data);
    }

    public function update(QuizAttempt $attempt, array $data): QuizAttempt
    {
        $attempt->update($data);
        return $attempt->fresh();
    }

    /**
     * Expire all in-progress attempts that have exceeded their timer.
     * Called by the scheduled command.
     */
    public function expireTimedOutAttempts(): int
    {
        return QuizAttempt::where('status', AttemptStatus::InProgress)
                          ->whereNotNull('time_limit_expires_at')
                          ->where('time_limit_expires_at', '<', now())
                          ->update(['status' => AttemptStatus::TimedOut]);
    }
}
