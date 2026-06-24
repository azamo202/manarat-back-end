<?php

namespace App\Application\Quiz\Repositories;

use App\Models\Quiz;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class QuizRepository implements QuizRepositoryInterface
{
    public function findById(int $id): ?Quiz
    {
        return Quiz::with(['lesson', 'course', 'analytics'])->find($id);
    }

    public function findByIdOrFail(int $id): Quiz
    {
        return Quiz::with(['lesson', 'course', 'analytics'])->findOrFail($id);
    }

    public function paginateForAdmin(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Quiz::with(['lesson', 'course', 'creator'])
                     ->withCount('questions')
                     ->withCount('attempts')
                     ->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (!empty($filters['lesson_id'])) {
            $query->where('lesson_id', $filters['lesson_id']);
        }

        if (!empty($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
    }

    public function getAvailableForLesson(int $lessonId): Collection
    {
        return Quiz::available()
                   ->where('lesson_id', $lessonId)
                   ->withCount('questions')
                   ->get();
    }

    public function getAvailableForCourse(int $courseId): Collection
    {
        return Quiz::available()
                   ->where('course_id', $courseId)
                   ->withCount('questions')
                   ->get();
    }

    public function create(array $data): Quiz
    {
        return Quiz::create($data);
    }

    public function update(Quiz $quiz, array $data): Quiz
    {
        $quiz->update($data);
        return $quiz->fresh();
    }

    public function delete(Quiz $quiz): void
    {
        $quiz->delete();
    }

    public function duplicate(Quiz $quiz, int $createdBy): Quiz
    {
        $newQuiz = $quiz->replicate(['status']);
        $newQuiz->title      = $quiz->title . ' (نسخة)';
        $newQuiz->status     = 'draft';
        $newQuiz->created_by = $createdBy;
        $newQuiz->save();

        // Duplicate the quiz-question associations
        foreach ($quiz->questions as $question) {
            $newQuiz->questions()->attach($question->id, [
                'order_number'   => $question->pivot->order_number,
                'points_override'=> $question->pivot->points_override,
            ]);
        }

        return $newQuiz;
    }
}
