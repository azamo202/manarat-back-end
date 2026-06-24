<?php

namespace App\Application\Quiz\Repositories;

use App\Models\Quiz;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface QuizRepositoryInterface
{
    public function findById(int $id): ?Quiz;

    public function findByIdOrFail(int $id): Quiz;

    public function paginateForAdmin(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getAvailableForLesson(int $lessonId): Collection;

    public function getAvailableForCourse(int $courseId): Collection;

    public function create(array $data): Quiz;

    public function update(Quiz $quiz, array $data): Quiz;

    public function delete(Quiz $quiz): void;

    public function duplicate(Quiz $quiz, int $createdBy): Quiz;
}
