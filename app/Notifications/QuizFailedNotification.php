<?php

namespace App\Notifications;

use App\Models\QuizResult;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizFailedNotification extends Notification
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
            ->subject('نتيجة اختبارك')
            ->greeting("أهلاً {$notifiable->full_name}،")
            ->line("للأسف، لم تتمكن من اجتياز اختبار \"{$quiz->title}\" هذه المرة.")
            ->line("نتيجتك: {$this->result->percentage}% (الحد الأدنى للنجاح: {$quiz->passing_score}%)")
            ->line("لا تستسلم! يمكنك المراجعة والمحاولة مجدداً.")
            ->action('مراجعة الإجابات', url("/quiz-results/{$this->result->id}"));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'quiz_failed',
            'quiz_id'    => $this->result->quiz_id,
            'result_id'  => $this->result->id,
            'percentage' => $this->result->percentage,
            'message'    => "لم تجتز اختبار \"{$this->result->quiz->title}\". حاول مرة أخرى.",
        ];
    }
}
