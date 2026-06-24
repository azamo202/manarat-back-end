<?php

namespace App\Http\Requests\Quiz;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'answers'                          => ['nullable', 'array'],
            'answers.*.question_id'            => ['required_with:answers', 'integer', 'exists:questions,id'],
            'answers.*.answer_value'           => ['nullable'],
            'answers.*.time_spent_seconds'     => ['nullable', 'integer', 'min:0'],
        ];
    }
}
