<?php

namespace App\Http\Requests\Quiz;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'type'                    => ['required', 'in:multiple_choice,multiple_select,true_false,short_text,long_text,matching,ordering,fill_in_blank,image_based,audio_based,video_based'],
            'content'                 => ['required', 'string'],
            'explanation'             => ['nullable', 'string'],
            'hint'                    => ['nullable', 'string'],
            'difficulty'              => ['nullable', 'in:easy,medium,hard'],
            'points'                  => ['required', 'integer', 'min:1'],

            // Options (required for non-open-ended types)
            'options'                 => ['nullable', 'array'],
            'options.*.content'       => ['required_with:options', 'string'],
            'options.*.is_correct'    => ['required_with:options', 'boolean'],
            'options.*.order_number'  => ['nullable', 'integer', 'min:0'],
            'options.*.match_target'  => ['nullable', 'string'],

            // Media
            'media'                   => ['nullable', 'array'],
            'media.*'                 => ['file', 'max:51200', 'mimes:jpeg,png,gif,webp,mp3,wav,ogg,mp4,webm,mov'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'     => 'نوع السؤال مطلوب.',
            'content.required'  => 'نص السؤال مطلوب.',
            'points.required'   => 'درجة السؤال مطلوبة.',
        ];
    }
}
