<?php

namespace App\Models;

use App\Domain\Quiz\Enums\AttemptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuizAttempt extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status'                   => AttemptStatus::class,
            'started_at'               => 'datetime',
            'submitted_at'             => 'datetime',
            'time_limit_expires_at'    => 'datetime',
            'shuffled_question_order'  => 'array',
            'shuffled_answer_orders'   => 'array',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(QuizResult::class, 'attempt_id');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    public function isInProgress(): bool
    {
        return $this->status === AttemptStatus::InProgress;
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Check if the timer has expired server-side.
     */
    public function isTimerExpired(): bool
    {
        return $this->time_limit_expires_at !== null
            && now()->gt($this->time_limit_expires_at);
    }

    /**
     * Remaining time in seconds, 0 if expired.
     */
    public function getRemainingSecondsAttribute(): ?int
    {
        if ($this->time_limit_expires_at === null) {
            return null; // unlimited
        }

        $remaining = now()->diffInSeconds($this->time_limit_expires_at, false);

        return (int) max(0, $remaining);
    }
}
