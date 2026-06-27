<?php

namespace App\Console\Commands;

use App\Application\Quiz\Jobs\ProcessQuizSubmission;
use App\Application\Quiz\Repositories\AttemptRepositoryInterface;
use App\Domain\Quiz\Enums\AttemptStatus;
use App\Models\QuizAttempt;
use Illuminate\Console\Command;

class ExpireTimedOutAttempts extends Command
{
    protected $signature   = 'quiz:expire-attempts';
    protected $description = 'Mark timed-out quiz attempts as expired and trigger grading.';

    public function handle(AttemptRepositoryInterface $attemptRepository): int
    {
        $count = $attemptRepository->expireTimedOutAttempts();

        if ($count > 0) {
            $this->info("Expired {$count} timed-out attempt(s).");

            // Dispatch grading jobs for newly expired attempts
            $expiredAttempts = QuizAttempt::where('status', AttemptStatus::TimedOut)
                ->whereDoesntHave('result')
                ->get();

            foreach ($expiredAttempts as $attempt) {
                ProcessQuizSubmission::dispatch($attempt->id)->afterCommit();
            }
        }

        return self::SUCCESS;
    }
}
