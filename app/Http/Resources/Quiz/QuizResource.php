<?php

namespace App\Http\Resources\Quiz;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'                      => $this->id,
            'title'                   => $this->title,
            'description'             => $this->description,
            'category'                => $this->category,
            'difficulty'              => $this->difficulty->value,
            'difficulty_label'        => $this->difficulty->label(),
            'passing_score'           => $this->passing_score,
            'time_limit_minutes'      => $this->time_limit_minutes,
            'max_attempts'            => $this->max_attempts,
            'shuffle_questions'       => $this->shuffle_questions,
            'shuffle_answers'         => $this->shuffle_answers,
            'show_correct_answers'    => $this->show_correct_answers,
            'show_score_after_submit' => $this->show_score_after_submit,
            'active_from'             => $this->active_from?->toIso8601String(),
            'active_until'            => $this->active_until?->toIso8601String(),
            'status'                  => $this->status->value,
            'status_label'            => $this->status->label(),
            'lesson_id'               => $this->lesson_id,
            'course_id'               => $this->course_id,

            // Conditionally loaded
            'lesson'    => $this->whenLoaded('lesson', fn() => [
                'id'    => $this->lesson->id,
                'title' => $this->lesson->title,
            ]),
            'course'    => $this->whenLoaded('course', fn() => [
                'id'    => $this->course->id,
                'title' => $this->course->title,
            ]),
            'analytics' => $this->whenLoaded('analytics'),

            // Admin-only fields
            'questions_count' => $this->when(
                $user?->is_admin,
                fn() => $this->questions_count ?? $this->questions()->count()
            ),
            'attempts_count' => $this->when(
                $user?->is_admin,
                fn() => $this->attempts_count ?? null
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
