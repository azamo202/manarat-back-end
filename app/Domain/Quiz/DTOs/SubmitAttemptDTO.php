<?php

namespace App\Domain\Quiz\DTOs;

final readonly class SubmitAttemptDTO
{
    /**
     * @param int   $attemptId
     * @param int   $userId
     * @param array $answers  Array of ['question_id' => int, 'answer_value' => mixed, 'time_spent_seconds' => int]
     */
    public function __construct(
        public int   $attemptId,
        public int   $userId,
        public array $answers,
    ) {}

    public static function fromArray(int $attemptId, int $userId, array $data): self
    {
        return new self(
            attemptId: $attemptId,
            userId:    $userId,
            answers:   $data['answers'] ?? [],
        );
    }
}
