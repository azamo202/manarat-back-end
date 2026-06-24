<?php

namespace App\Http\Controllers\Quiz\Student;

use App\Application\Quiz\Repositories\QuizRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\Quiz\QuestionResource;
use App\Http\Resources\Quiz\QuizResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizRepositoryInterface $quizRepository,
    ) {}

    /**
     * GET /quizzes?lesson_id=X  or  ?course_id=X
     * Returns available quizzes for a given lesson or course.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ]);

        if ($request->filled('lesson_id')) {
            $quizzes = $this->quizRepository->getAvailableForLesson($request->lesson_id);
        } elseif ($request->filled('course_id')) {
            $quizzes = $this->quizRepository->getAvailableForCourse($request->course_id);
        } else {
            return response()->json(['data' => []]);
        }

        return response()->json(QuizResource::collection($quizzes));
    }

    /**
     * GET /quizzes/{id}
     * Returns quiz details with questions (without correct answers).
     */
    public function show(int $id): JsonResponse
    {
        $quiz = $this->quizRepository->findByIdOrFail($id);

        $this->authorize('view', $quiz);

        $quiz->load(['questions.options', 'questions.media']);

        return response()->json([
            'quiz'      => new QuizResource($quiz),
            'questions' => QuestionResource::collection($quiz->questions),
        ]);
    }
}
