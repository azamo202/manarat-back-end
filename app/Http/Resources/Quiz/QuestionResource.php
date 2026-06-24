<?php

namespace App\Http\Resources\Quiz;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'          => $this->id,
            'type'        => $this->type->value,
            'type_label'  => $this->type->label(),
            'content'     => $this->content,
            'hint'        => $this->hint,
            'difficulty'  => $this->difficulty->value,
            'points'      => $this->points,

            // Explanation only shown after submission, or to admin
            'explanation' => $this->when(
                $user?->is_admin || ($this->resource->pivot_show_explanation ?? false),
                $this->explanation
            ),

            'options' => $this->whenLoaded('options', function () use ($user) {
                return $this->options->map(fn($option) => [
                    'id'           => $option->id,
                    'content'      => $option->content,
                    'order_number' => $option->order_number,
                    'match_target' => $option->match_target,
                    // Hide correct answer from student during attempt
                    'is_correct'   => $this->when($user?->is_admin, $option->is_correct),
                ]);
            }),

            'media' => $this->whenLoaded('media', fn() =>
                $this->media->map(fn($m) => [
                    'id'       => $m->id,
                    'type'     => $m->type,
                    'url'      => $m->url,
                    'alt_text' => $m->alt_text,
                ])
            ),
        ];
    }
}
