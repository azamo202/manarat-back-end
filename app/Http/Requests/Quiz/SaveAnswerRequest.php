<?php

namespace App\Http\Requests\Quiz;

use Illuminate\Foundation\Http\FormRequest;

class SaveAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'answers'                          => ['required', 'array', 'min:1'],
            'answers.*.question_id'            => ['required', 'integer', 'exists:questions,id'],
            'answers.*.answer_value'           => ['nullable'],  // flexible — validated per type in service
            'answers.*.time_spent_seconds'     => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required'               => 'يجب تقديم إجابة واحدة على الأقل.',
            'answers.*.question_id.required' => 'معرّف السؤال مطلوب.',
            'answers.*.question_id.exists'   => 'السؤال المحدد غير موجود.',
        ];
    }
}
