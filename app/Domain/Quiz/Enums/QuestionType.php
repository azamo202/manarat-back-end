<?php

namespace App\Domain\Quiz\Enums;

enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case MultipleSelect = 'multiple_select';
    case TrueFalse      = 'true_false';
    case ShortText      = 'short_text';
    case LongText       = 'long_text';
    case Matching       = 'matching';
    case Ordering       = 'ordering';
    case FillInBlank    = 'fill_in_blank';
    case ImageBased     = 'image_based';
    case AudioBased     = 'audio_based';
    case VideoBased     = 'video_based';

    public function label(): string
    {
        return match($this) {
            self::MultipleChoice => 'اختيار من متعدد (إجابة واحدة)',
            self::MultipleSelect => 'اختيار متعدد',
            self::TrueFalse      => 'صح أو خطأ',
            self::ShortText      => 'نص قصير',
            self::LongText       => 'نص طويل',
            self::Matching       => 'مطابقة',
            self::Ordering       => 'ترتيب',
            self::FillInBlank    => 'ملء الفراغات',
            self::ImageBased     => 'مبني على صورة',
            self::AudioBased     => 'مبني على صوت',
            self::VideoBased     => 'مبني على فيديو',
        };
    }

    /**
     * Types that require predefined options (non-open-ended).
     */
    public function hasOptions(): bool
    {
        return in_array($this, [
            self::MultipleChoice,
            self::MultipleSelect,
            self::TrueFalse,
            self::Matching,
            self::Ordering,
            self::ImageBased,
            self::AudioBased,
            self::VideoBased,
        ]);
    }

    /**
     * Types that are auto-graded by the system.
     */
    public function isAutoGraded(): bool
    {
        return !in_array($this, [self::ShortText, self::LongText]);
    }

    /**
     * Types that require media attachments.
     */
    public function requiresMedia(): bool
    {
        return in_array($this, [self::ImageBased, self::AudioBased, self::VideoBased]);
    }
}
