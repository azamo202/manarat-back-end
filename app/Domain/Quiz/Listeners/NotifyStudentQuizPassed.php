<?php

namespace App\Domain\Quiz\Listeners;

use App\Application\Quiz\Jobs\SendQuizNotification;
use App\Domain\Quiz\Events\QuizPassed;
use App\Notifications\QuizPassedNotification;

class NotifyStudentQuizPassed
{
    public function handle(QuizPassed $event): void
    {
        SendQuizNotification::dispatch(
            $event->result->user,
            new QuizPassedNotification($event->result),
        );
    }
}
