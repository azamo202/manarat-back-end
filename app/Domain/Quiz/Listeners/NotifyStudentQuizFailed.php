<?php

namespace App\Domain\Quiz\Listeners;

use App\Application\Quiz\Jobs\SendQuizNotification;
use App\Domain\Quiz\Events\QuizFailed;
use App\Notifications\QuizFailedNotification;

class NotifyStudentQuizFailed
{
    public function handle(QuizFailed $event): void
    {
        SendQuizNotification::dispatch(
            $event->result->user,
            new QuizFailedNotification($event->result),
        );
    }
}
