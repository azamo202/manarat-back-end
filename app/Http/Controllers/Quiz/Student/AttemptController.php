<?php

namespace App\Http\Controllers\Quiz\Student;

use App\Application\Quiz\Repositories\AttemptRepositoryInterface;
use App\Application\Quiz\Services\QuizAttemptService;
use App\Domain\Quiz\DTOs\SubmitAttemptDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Quiz\SaveAnswerRequest;
use App\Http\Requests\Quiz\SubmitAttemptRequest;
use App\Http\Resources\Quiz\AttemptResource;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttemptController extends Controller
{
    public function __construct(
        private readonly QuizAttemptService        $attemptService,
        private readonly AttemptRepositoryInterface $attemptRepository,
    ) {}

    /**
     * POST /quizzes/{quiz}/attempts
     * Start a new attempt.
     */
    public function start(int $quizId, Request $request): JsonResponse
    {
        $quiz    = Quiz::with('questions.options')->findOrFail($quizId);
        $attempt = $this->attemptService->startAttempt(
            quiz:      $quiz,
            userId:    $request->user()->id,
            ip:        $request->ip(),
            userAgent: $request->userAgent() ?? '',
        );

        $attempt->load(['answers']);

        return response()->json([
            'message' => 'بدأ الاختبار بنجاح.',
            'attempt' => new AttemptResource($attempt),
        ], 201);
    }

    /**
     * GET /quizzes/{quiz}/attempts
     * Get user's attempts for a quiz.
     */
    public function index(int $quizId, Request $request): JsonResponse
    {
        $attempts = $this->attemptRepository->getUserAttempts($quizId, $request->user()->id);

        return response()->json(AttemptResource::collection($attempts));
    }

    /**
     * GET /quizzes/{quiz}/attempts/{attempt}
     * Get attempt state (for page refresh recovery).
     */
    public function show(int $quizId, int $attemptId, Request $request): JsonResponse
    {
        $attempt = $this->attemptRepository->findByIdOrFail($attemptId);

        \Illuminate\Support\Facades\Gate::authorize('view', $attempt);

        // Check if timer expired
        $attempt = $this->attemptService->resumeAttempt($attempt);

        $attempt->load(['answers']);

        return response()->json(new AttemptResource($attempt));
    }

    /**
     * PUT /quizzes/{quiz}/attempts/{attempt}/answers
     * Auto-save answers (called every 10 seconds from frontend).
     */
    public function saveAnswers(int $quizId, int $attemptId, SaveAnswerRequest $request): JsonResponse
    {
        $attempt = $this->attemptRepository->findByIdOrFail($attemptId);

        \Illuminate\Support\Facades\Gate::authorize('update', $attempt);

        $this->attemptService->saveAnswers($attempt, $request->answers);

        return response()->json(['message' => 'تم حفظ الإجابات.']);
    }

    /**
     * POST /quizzes/{quiz}/attempts/{attempt}/submit
     * Final submission.
     */
    public function submit(int $quizId, int $attemptId, SubmitAttemptRequest $request): JsonResponse
    {
        $dto = SubmitAttemptDTO::fromArray(
            attemptId: $attemptId,
            userId:    $request->user()->id,
            data:      $request->validated(),
        );

        $attempt = $this->attemptService->submit($dto);

        return response()->json([
            'message'    => 'تم تسليم الاختبار بنجاح. جارٍ معالجة النتيجة.',
            'attempt_id' => $attempt->id,
            'status'     => $attempt->status->value,
        ]);
    }
}
