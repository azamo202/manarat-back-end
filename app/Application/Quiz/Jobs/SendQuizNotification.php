<?php

namespace App\Application\Quiz\Jobs;

use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Notification;

class SendQuizNotification implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public int $tries = 3;

    public function __construct(
        public readonly User         $user,
        public readonly Notification $notification,
    ) {}

    public function handle(): void
    {
        $this->user->notify($this->notification);
    }
}
