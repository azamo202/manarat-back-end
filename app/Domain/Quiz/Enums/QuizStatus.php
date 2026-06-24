<?php

namespace App\Domain\Quiz\Enums;

enum QuizStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Archived  = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'مسودة',
            self::Published => 'منشور',
            self::Archived  => 'مؤرشف',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match($this) {
            self::Draft     => in_array($target, [self::Published]),
            self::Published => in_array($target, [self::Archived]),
            self::Archived  => in_array($target, [self::Draft]),
        };
    }
}
