<?php

namespace App\Domain\Quiz\DTOs;

final readonly class QuizResultDTO
{
    public function __construct(
        public int   $quizId,
        public int   $attemptId,
        public int   $userId,
        public int   $rawScore,
        public int   $maxScore,
        public float $percentage,
        public int   $durationSeconds,
        public bool  $passed,
        public bool  $certificateEligible,
        public int   $attemptNumber,
    ) {}
}
