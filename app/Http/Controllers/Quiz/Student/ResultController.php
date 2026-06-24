<?php

namespace App\Http\Controllers\Quiz\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Quiz\ResultResource;
use App\Models\QuizAnswer;
use App\Models\QuizResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    /**
     * GET /my/quiz-results
     * All quiz results for the authenticated student.
     */
    public function myResults(Request $request): JsonResponse
    {
        $results = QuizResult::where('user_id', $request->user()->id)
            ->with(['quiz:id,title,passing_score,show_score_after_submit'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(ResultResource::collection($results));
    }

    /**
     * GET /quizzes/{quiz}/results/{result}
     * Single result with answer breakdown (respects quiz show settings).
     */
    public function show(int $quizId, int $resultId, Request $request): JsonResponse
    {
        $result = QuizResult::with(['quiz', 'attempt.answers.question.options', 'user'])
            ->where('quiz_id', $quizId)
            ->findOrFail($resultId);

        // Only the owner or admin can see
        if (!$request->user()->is_admin && $result->user_id !== $request->user()->id) {
            abort(403, 'غير مصرح لك بعرض هذه النتيجة.');
        }

        $quiz    = $result->quiz;
        $answers = [];

        if ($quiz->show_correct_answers) {
            $answers = $result->attempt->answers->map(function (QuizAnswer $answer) {
                return [
                    'question_id'   => $answer->question_id,
                    'question'      => $answer->question->content,
                    'answer_value'  => $answer->answer_value,
                    'is_correct'    => $answer->is_correct,
                    'points_earned' => $answer->points_earned,
                    'explanation'   => $answer->question->explanation,
                    'correct_options' => $answer->question->getCorrectOptions()->map(fn($o) => [
                        'id'      => $o->id,
                        'content' => $o->content,
                    ]),
                ];
            });
        }

        return response()->json([
            'result'  => new ResultResource($result),
            'answers' => $quiz->show_correct_answers ? $answers : [],
        ]);
    }
}
