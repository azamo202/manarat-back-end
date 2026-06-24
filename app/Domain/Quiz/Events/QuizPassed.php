<?php

namespace App\Domain\Quiz\Events;

use App\Models\QuizResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizPassed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly QuizResult $result) {}
}
