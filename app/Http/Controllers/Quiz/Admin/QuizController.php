<?php

namespace App\Http\Controllers\Quiz\Admin;

use App\Application\Quiz\Repositories\QuizRepositoryInterface;
use App\Application\Quiz\Services\QuizService;
use App\Domain\Quiz\DTOs\CreateQuizDTO;
use App\Domain\Quiz\DTOs\UpdateQuizDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Quiz\StoreQuizRequest;
use App\Http\Requests\Quiz\UpdateQuizRequest;
use App\Http\Resources\Quiz\QuizResource;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizRepositoryInterface $quizRepository,
        private readonly QuizService             $quizService,
    ) {}

    /**
     * GET /admin/quizzes
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $quizzes = $this->quizRepository->paginateForAdmin($request->only([
            'status', 'course_id', 'lesson_id', 'difficulty', 'search'
        ]));

        return QuizResource::collection($quizzes);
    }

    /**
     * POST /admin/quizzes
     */
    public function store(StoreQuizRequest $request): JsonResponse
    {
        $dto  = CreateQuizDTO::fromArray($request->validated(), $request->user()->id);
        $quiz = $this->quizService->create($dto);

        return response()->json([
            'message' => 'تم إنشاء الاختبار بنجاح.',
            'quiz'    => new QuizResource($quiz),
        ], 201);
    }

    /**
     * GET /admin/quizzes/{quiz}
     */
    public function show(int $id): JsonResponse
    {
        $quiz = $this->quizRepository->findByIdOrFail($id);
        $quiz->load(['questions.options', 'questions.media', 'lesson', 'course']);

        return response()->json(new QuizResource($quiz));
    }

    /**
     * PUT /admin/quizzes/{quiz}
     */
    public function update(UpdateQuizRequest $request, int $id): JsonResponse
    {
        $quiz = Quiz::findOrFail($id);
        $dto  = UpdateQuizDTO::fromArray($request->validated());
        $quiz = $this->quizService->update($quiz, $dto);

        return response()->json([
            'message' => 'تم تحديث الاختبار بنجاح.',
            'quiz'    => new QuizResource($quiz),
        ]);
    }

    /**
     * DELETE /admin/quizzes/{quiz}
     */
    public function destroy(int $id): JsonResponse
    {
        $quiz = Quiz::findOrFail($id);
        $this->quizService->delete($quiz);

        return response()->json(['message' => 'تم حذف الاختبار بنجاح.']);
    }

    /**
     * POST /admin/quizzes/{quiz}/publish
     */
    public function publish(int $id): JsonResponse
    {
        $quiz = Quiz::findOrFail($id);
        $quiz = $this->quizService->publish($quiz);

        return response()->json([
            'message' => 'تم نشر الاختبار بنجاح.',
            'quiz'    => new QuizResource($quiz),
        ]);
    }

    /**
     * POST /admin/quizzes/{quiz}/archive
     */
    public function archive(int $id): JsonResponse
    {
        $quiz = Quiz::findOrFail($id);
        $quiz = $this->quizService->archive($quiz);

        return response()->json([
            'message' => 'تم أرشفة الاختبار بنجاح.',
            'quiz'    => new QuizResource($quiz),
        ]);
    }

    /**
     * POST /admin/quizzes/{quiz}/duplicate
     */
    public function duplicate(int $id, Request $request): JsonResponse
    {
        $quiz    = Quiz::findOrFail($id);
        $newQuiz = $this->quizService->duplicate($quiz, $request->user()->id);

        return response()->json([
            'message' => 'تم تكرار الاختبار بنجاح.',
            'quiz'    => new QuizResource($newQuiz),
        ], 201);
    }

    /**
     * POST /admin/quizzes/{quiz}/questions/sync
     */
    public function syncQuestions(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'questions'                   => ['required', 'array'],
            'questions.*.question_id'     => ['required', 'exists:questions,id'],
            'questions.*.points_override' => ['nullable', 'integer', 'min:1'],
        ]);

        $quiz = Quiz::findOrFail($id);
        $this->quizService->syncQuestions($quiz, $request->questions);

        return response()->json(['message' => 'تم تحديث أسئلة الاختبار بنجاح.']);
    }
}
