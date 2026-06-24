<?php

namespace App\Application\Quiz\Repositories;

use App\Models\QuizAttempt;
use App\Domain\Quiz\Enums\AttemptStatus;
use Illuminate\Database\Eloquent\Collection;

interface AttemptRepositoryInterface
{
    public function findById(int $id): ?QuizAttempt;

    public function findByIdOrFail(int $id): QuizAttempt;

    public function getActiveAttempt(int $quizId, int $userId): ?QuizAttempt;

    public function countCompletedAttempts(int $quizId, int $userId): int;

    public function getUserAttempts(int $quizId, int $userId): Collection;

    public function getAllAttemptsForQuiz(int $quizId, int $perPage = 20);

    public function create(array $data): QuizAttempt;

    public function update(QuizAttempt $attempt, array $data): QuizAttempt;

    public function expireTimedOutAttempts(): int;
}
