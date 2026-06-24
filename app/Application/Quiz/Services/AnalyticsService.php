<?php

namespace App\Application\Quiz\Services;

use App\Models\QuizAttempt;
use App\Models\QuizResult;
use App\Models\QuestionAnalytic;
use App\Models\QuizAnalytic;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Update question and quiz analytics after a submission.
     * Designed to run inside a queued job.
     */
    public function updateAfterSubmission(QuizAttempt $attempt): void
    {
        $attempt->load(['quiz', 'answers.question', 'result']);

        if (!$attempt->result) {
            return;
        }

        DB::transaction(function () use ($attempt) {
            $this->updateQuestionAnalytics($attempt);
            $this->updateQuizAnalytics($attempt->quiz_id);
        });
    }

    private function updateQuestionAnalytics(QuizAttempt $attempt): void
    {
        foreach ($attempt->answers as $answer) {
            $analytic = QuestionAnalytic::firstOrCreate(
                ['question_id' => $answer->question_id],
                ['quiz_id' => $attempt->quiz_id]
            );

            $analytic->increment('total_attempts');

            if ($answer->is_correct === true) {
                $analytic->increment('correct_count');
            } elseif ($answer->is_correct === false) {
                $analytic->increment('wrong_count');
            } elseif ($answer->answer_value === null) {
                $analytic->increment('skip_count');
            }

            // Recalculate correct rate
            $analytic->refresh();
            $analytic->correct_rate = $analytic->total_attempts > 0
                ? round(($analytic->correct_count / $analytic->total_attempts) * 100, 2)
                : 0;

            // Rolling average time
            $oldTotal = $analytic->total_attempts - 1;
            $analytic->avg_time_seconds = $oldTotal > 0
                ? (($analytic->avg_time_seconds * $oldTotal) + $answer->time_spent_seconds) / $analytic->total_attempts
                : $answer->time_spent_seconds;

            $analytic->save();
        }
    }

    private function updateQuizAnalytics(int $quizId): void
    {
        $stats = QuizResult::where('quiz_id', $quizId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(passed) as pass_count,
                AVG(percentage) as avg_score,
                AVG(duration_seconds) as avg_duration
            ')
            ->first();

        $totalAttempts    = QuizAttempt::where('quiz_id', $quizId)->count();
        $completedAttempts = QuizResult::where('quiz_id', $quizId)->count();

        QuizAnalytic::updateOrCreate(
            ['quiz_id' => $quizId],
            [
                'total_attempts'     => $totalAttempts,
                'completed_attempts' => $completedAttempts,
                'pass_count'         => $stats->pass_count ?? 0,
                'fail_count'         => $completedAttempts - ($stats->pass_count ?? 0),
                'avg_score'          => round($stats->avg_score ?? 0, 2),
                'avg_duration_seconds'=> round($stats->avg_duration ?? 0, 2),
                'completion_rate'    => $totalAttempts > 0
                    ? round(($completedAttempts / $totalAttempts) * 100, 2)
                    : 0,
                'pass_rate'          => $completedAttempts > 0
                    ? round((($stats->pass_count ?? 0) / $completedAttempts) * 100, 2)
                    : 0,
            ]
        );
    }

    /**
     * Get top and bottom performing questions for a quiz.
     */
    public function getQuestionPerformance(int $quizId): array
    {
        $analytics = QuestionAnalytic::where('quiz_id', $quizId)
            ->with('question:id,content,type,difficulty')
            ->orderBy('correct_rate', 'desc')
            ->get();

        return [
            'easiest'  => $analytics->take(5)->values(),
            'hardest'  => $analytics->sortBy('correct_rate')->take(5)->values(),
            'all'      => $analytics,
        ];
    }

    /**
     * Get top-performing and failed students for a quiz.
     */
    public function getStudentPerformance(int $quizId): array
    {
        $results = QuizResult::where('quiz_id', $quizId)
            ->with('user:id,full_name,email')
            ->orderBy('percentage', 'desc')
            ->get();

        return [
            'top_students'    => $results->where('passed', true)->take(10)->values(),
            'failed_students' => $results->where('passed', false)->take(10)->values(),
        ];
    }
}
