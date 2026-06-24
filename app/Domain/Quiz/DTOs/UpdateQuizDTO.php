<?php

namespace App\Domain\Quiz\DTOs;

use App\Domain\Quiz\Enums\DifficultyLevel;
use Carbon\Carbon;

final readonly class UpdateQuizDTO
{
    public function __construct(
        public string          $title,
        public ?string         $description,
        public ?int            $lessonId,
        public ?int            $courseId,
        public ?string         $category,
        public DifficultyLevel $difficulty,
        public int             $passingScore,
        public ?int            $timeLimitMinutes,
        public int             $maxAttempts,
        public bool            $shuffleQuestions,
        public bool            $shuffleAnswers,
        public bool            $showCorrectAnswers,
        public bool            $showScoreAfterSubmit,
        public ?Carbon         $activeFrom,
        public ?Carbon         $activeUntil,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title:                $data['title'],
            description:          $data['description'] ?? null,
            lessonId:             $data['lesson_id'] ?? null,
            courseId:             $data['course_id'] ?? null,
            category:             $data['category'] ?? null,
            difficulty:           DifficultyLevel::from($data['difficulty'] ?? 'medium'),
            passingScore:         (int) ($data['passing_score'] ?? 50),
            timeLimitMinutes:     isset($data['time_limit_minutes']) ? (int) $data['time_limit_minutes'] : null,
            maxAttempts:          (int) ($data['max_attempts'] ?? 1),
            shuffleQuestions:     (bool) ($data['shuffle_questions'] ?? false),
            shuffleAnswers:       (bool) ($data['shuffle_answers'] ?? false),
            showCorrectAnswers:   (bool) ($data['show_correct_answers'] ?? false),
            showScoreAfterSubmit: (bool) ($data['show_score_after_submit'] ?? true),
            activeFrom:           isset($data['active_from']) ? Carbon::parse($data['active_from']) : null,
            activeUntil:          isset($data['active_until']) ? Carbon::parse($data['active_until']) : null,
        );
    }
}
