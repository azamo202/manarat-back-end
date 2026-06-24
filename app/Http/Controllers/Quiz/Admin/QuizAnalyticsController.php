<?php

namespace App\Http\Controllers\Quiz\Admin;

use App\Application\Quiz\Services\AnalyticsService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Quiz\ResultResource;
use App\Models\Quiz;
use App\Models\QuizResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizAnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
    ) {}

    /**
     * GET /admin/quizzes/{quiz}/analytics
     * Full analytics for a single quiz.
     */
    public function show(int $quizId): JsonResponse
    {
        $quiz = Quiz::with('analytics')->findOrFail($quizId);

        $questionPerformance = $this->analyticsService->getQuestionPerformance($quizId);
        $studentPerformance  = $this->analyticsService->getStudentPerformance($quizId);

        return response()->json([
            'quiz'                => [
                'id'    => $quiz->id,
                'title' => $quiz->title,
            ],
            'overview'            => $quiz->analytics,
            'question_performance'=> $questionPerformance,
            'student_performance' => $studentPerformance,
        ]);
    }

    /**
     * GET /admin/analytics/overview
     * Platform-wide quiz analytics overview.
     */
    public function overview(): JsonResponse
    {
        $stats = [
            'total_quizzes'      => Quiz::count(),
            'published_quizzes'  => Quiz::where('status', 'published')->count(),
            'total_attempts'     => \App\Models\QuizAttempt::count(),
            'total_submissions'  => QuizResult::count(),
            'overall_pass_rate'  => QuizResult::count() > 0
                ? round((QuizResult::where('passed', true)->count() / QuizResult::count()) * 100, 2)
                : 0,
            'avg_score'          => round(QuizResult::avg('percentage') ?? 0, 2),
        ];

        return response()->json($stats);
    }
}
