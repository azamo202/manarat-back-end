<?php

namespace App\Domain\Quiz\Enums;

enum DifficultyLevel: string
{
    case Easy   = 'easy';
    case Medium = 'medium';
    case Hard   = 'hard';

    public function label(): string
    {
        return match($this) {
            self::Easy   => 'سهل',
            self::Medium => 'متوسط',
            self::Hard   => 'صعب',
        };
    }

    public function weight(): int
    {
        return match($this) {
            self::Easy   => 1,
            self::Medium => 2,
            self::Hard   => 3,
        };
    }
}
