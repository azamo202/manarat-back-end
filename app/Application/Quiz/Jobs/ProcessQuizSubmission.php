<?php

namespace App\Application\Quiz\Jobs;

use App\Application\Quiz\Services\AnalyticsService;
use App\Application\Quiz\Services\GradingService;
use App\Domain\Quiz\Events\CertificateEligible;
use App\Domain\Quiz\Events\QuizFailed;
use App\Domain\Quiz\Events\QuizPassed;
use App\Models\QuizAttempt;
use App\Models\QuizResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class ProcessQuizSubmission implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public int $tries   = 3;
    public int $timeout = 120;
    public bool $afterCommit = true;

    public function __construct(public readonly int $attemptId) {}

    /**
     * Prevent concurrent processing of the same attempt.
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping("quiz_attempt_{$this->attemptId}")];
    }

    public function handle(GradingService $gradingService, AnalyticsService $analyticsService): void
    {
        $attempt = QuizAttempt::with(['quiz', 'answers.question.options', 'user'])->find($this->attemptId);

        if (!$attempt) {
            Log::warning("ProcessQuizSubmission: attempt {$this->attemptId} not found.");
            return;
        }

        // Prevent re-grading an already-processed attempt
        if ($attempt->result()->exists()) {
            Log::info("ProcessQuizSubmission: attempt {$this->attemptId} already graded.");
            return;
        }

        // Grade the attempt
        $resultDTO = $gradingService->grade($attempt);

        // Persist the result
        $result = QuizResult::create([
            'quiz_id'             => $resultDTO->quizId,
            'attempt_id'          => $resultDTO->attemptId,
            'user_id'             => $resultDTO->userId,
            'raw_score'           => $resultDTO->rawScore,
            'max_score'           => $resultDTO->maxScore,
            'final_score'         => $resultDTO->rawScore,
            'percentage'          => $resultDTO->percentage,
            'duration_seconds'    => $resultDTO->durationSeconds,
            'passed'              => $resultDTO->passed,
            'certificate_eligible'=> $resultDTO->certificateEligible,
            'attempt_number'      => $resultDTO->attemptNumber,
            'created_at'          => now(),
        ]);

        // Fire domain events
        if ($resultDTO->passed) {
            event(new QuizPassed($result));

            if ($resultDTO->certificateEligible) {
                event(new CertificateEligible($result));
            }
        } else {
            event(new QuizFailed($result));
        }

        // Update aggregated analytics
        $attempt->refresh();
        $analyticsService->updateAfterSubmission($attempt);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessQuizSubmission failed for attempt {$this->attemptId}: " . $exception->getMessage());
    }
}
