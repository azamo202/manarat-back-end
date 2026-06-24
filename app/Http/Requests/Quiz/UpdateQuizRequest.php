<?php

namespace App\Http\Requests\Quiz;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'title'                   => ['required', 'string', 'max:255'],
            'description'             => ['nullable', 'string'],
            'lesson_id'               => ['nullable', 'exists:lessons,id'],
            'course_id'               => ['nullable', 'exists:courses,id'],
            'category'                => ['nullable', 'string', 'max:100'],
            'difficulty'              => ['nullable', 'in:easy,medium,hard'],
            'passing_score'           => ['required', 'integer', 'min:1', 'max:100'],
            'time_limit_minutes'      => ['nullable', 'integer', 'min:1', 'max:600'],
            'max_attempts'            => ['required', 'integer', 'min:0'],
            'shuffle_questions'       => ['nullable', 'boolean'],
            'shuffle_answers'         => ['nullable', 'boolean'],
            'show_correct_answers'    => ['nullable', 'boolean'],
            'show_score_after_submit' => ['nullable', 'boolean'],
            'active_from'             => ['nullable', 'date'],
            'active_until'            => ['nullable', 'date', 'after_or_equal:active_from'],
        ];
    }
}
