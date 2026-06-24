<?php

namespace App\Http\Resources\Quiz;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $quiz = $this->quiz;

        return [
            'id'                    => $this->id,
            'quiz_id'               => $this->quiz_id,
            'status'                => $this->status->value,
            'status_label'          => $this->status->label(),
            'started_at'            => $this->started_at?->toIso8601String(),
            'submitted_at'          => $this->submitted_at?->toIso8601String(),
            'time_limit_expires_at' => $this->time_limit_expires_at?->toIso8601String(),
            'remaining_seconds'     => $this->remaining_seconds,

            // The order to display questions in (shuffled or natural)
            'question_order'        => $this->shuffled_question_order,

            // Shuffled answer orders per question (keyed by question_id)
            'answer_orders'         => $this->shuffled_answer_orders,

            // Current saved answers for page refresh recovery
            'saved_answers' => $this->whenLoaded('answers', fn() =>
                $this->answers->map(fn($a) => [
                    'question_id'       => $a->question_id,
                    'answer_value'      => $a->answer_value,
                    'answered_at'       => $a->answered_at?->toIso8601String(),
                ])
            ),

            'result' => $this->whenLoaded('result', fn() =>
                new ResultResource($this->result)
            ),
        ];
    }
}
