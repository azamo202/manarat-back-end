<?php

namespace App\Domain\Quiz\Enums;

enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted  = 'submitted';
    case TimedOut   = 'timed_out';
    case Abandoned  = 'abandoned';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Submitted, self::TimedOut, self::Abandoned]);
    }

    public function label(): string
    {
        return match($this) {
            self::InProgress => 'جارٍ',
            self::Submitted  => 'مُسلَّم',
            self::TimedOut   => 'انتهى الوقت',
            self::Abandoned  => 'متروك',
        };
    }
}
