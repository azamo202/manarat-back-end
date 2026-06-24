<?php

namespace App\Notifications;

use App\Models\QuizResult;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizPassedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly QuizResult $result) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $quiz = $this->result->quiz;

        return (new MailMessage)
            ->subject('🎉 لقد اجتزت الاختبار بنجاح!')
            ->greeting("أهلاً {$notifiable->full_name}،")
            ->line("تهانينا! لقد اجتزت اختبار \"{$quiz->title}\" بنجاح.")
            ->line("نتيجتك: {$this->result->percentage}% (الحد الأدنى للنجاح: {$quiz->passing_score}%)")
            ->action('عرض النتيجة', url("/quiz-results/{$this->result->id}"))
            ->line('شكراً على جهدك ومثابرتك.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'quiz_passed',
            'quiz_id'    => $this->result->quiz_id,
            'result_id'  => $this->result->id,
            'percentage' => $this->result->percentage,
            'message'    => "اجتزت اختبار \"{$this->result->quiz->title}\" بنجاح.",
        ];
    }
}
