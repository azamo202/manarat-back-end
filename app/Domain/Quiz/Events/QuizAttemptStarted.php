<?php

namespace App\Domain\Quiz\Events;

use App\Models\QuizAttempt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizAttemptStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly QuizAttempt $attempt) {}
}
