<?php

namespace App\Models;

use App\Domain\Quiz\Enums\DifficultyLevel;
use App\Domain\Quiz\Enums\QuizStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Quiz extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status'                  => QuizStatus::class,
            'difficulty'              => DifficultyLevel::class,
            'shuffle_questions'       => 'boolean',
            'shuffle_answers'         => 'boolean',
            'show_correct_answers'    => 'boolean',
            'show_score_after_submit' => 'boolean',
            'active_from'             => 'datetime',
            'active_until'            => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Quiz $quiz) {
            Cache::forget("quiz.{$quiz->id}");
            if ($quiz->lesson_id) {
                Cache::forget("quiz.lesson.{$quiz->lesson_id}");
                Cache::forget("lesson.{$quiz->lesson_id}");
            }
            if ($quiz->course_id) {
                Cache::forget("course.{$quiz->course_id}");
                Cache::forget('courses.active');
            }
        });

        static::deleted(function (Quiz $quiz) {
            Cache::forget("quiz.{$quiz->id}");
            if ($quiz->lesson_id) {
                Cache::forget("quiz.lesson.{$quiz->lesson_id}");
                Cache::forget("lesson.{$quiz->lesson_id}");
            }
            if ($quiz->course_id) {
                Cache::forget("course.{$quiz->course_id}");
                Cache::forget('courses.active');
            }
        });
    }

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'quiz_questions')
                    ->withPivot(['order_number', 'points_override'])
                    ->orderByPivot('order_number');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(QuizResult::class);
    }

    public function analytics(): HasOne
    {
        return $this->hasOne(QuizAnalytic::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', QuizStatus::Published);
    }

    public function scopeAvailable($query)
    {
        return $query->published()
                     ->where(function ($q) {
                         $q->whereNull('active_from')
                           ->orWhere('active_from', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('active_until')
                           ->orWhere('active_until', '>=', now());
                     });
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    public function isAvailable(): bool
    {
        if ($this->status !== QuizStatus::Published) {
            return false;
        }

        if ($this->active_from && now()->lt($this->active_from)) {
            return false;
        }

        if ($this->active_until && now()->gt($this->active_until)) {
            return false;
        }

        return true;
    }

    public function getTotalPointsAttribute(): int
    {
        return $this->questions->sum(function ($question) {
            return $question->pivot->points_override ?? $question->points;
        });
    }
}
