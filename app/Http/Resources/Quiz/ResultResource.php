<?php

namespace App\Http\Resources\Quiz;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $quiz = $this->whenLoaded('quiz', fn() => $this->quiz);

        return [
            'id'                   => $this->id,
            'quiz_id'              => $this->quiz_id,
            'attempt_id'           => $this->attempt_id,
            'attempt_number'       => $this->attempt_number,
            'raw_score'            => $this->raw_score,
            'max_score'            => $this->max_score,
            'final_score'          => $this->final_score,
            'percentage'           => (float) $this->percentage,
            'duration_seconds'     => $this->duration_seconds,
            'passed'               => $this->passed,
            'certificate_eligible' => $this->certificate_eligible,
            'created_at'           => $this->created_at?->toIso8601String(),

            // Quiz settings determine what we show
            'show_score'           => $this->whenLoaded('quiz', fn() => $this->quiz->show_score_after_submit, true),
            'show_answers'         => $this->whenLoaded('quiz', fn() => $this->quiz->show_correct_answers, false),

            'user' => $this->whenLoaded('user', fn() => [
                'id'        => $this->user->id,
                'full_name' => $this->user->full_name,
                'email'     => $this->user->email,
            ]),
        ];
    }
}
