<?php

namespace App\Models;

use App\Domain\Quiz\Enums\DifficultyLevel;
use App\Domain\Quiz\Enums\QuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type'       => QuestionType::class,
            'difficulty' => DifficultyLevel::class,
        ];
    }

    protected static function booted(): void
    {
        $clearCache = function (Question $question) {
            foreach ($question->quizzes as $quiz) {
                \Illuminate\Support\Facades\Cache::forget("quiz.{$quiz->id}");
                if ($quiz->lesson_id) {
                    \Illuminate\Support\Facades\Cache::forget("quiz.lesson.{$quiz->lesson_id}");
                    \Illuminate\Support\Facades\Cache::forget("lesson.{$quiz->lesson_id}");
                }
                if ($quiz->course_id) {
                    \Illuminate\Support\Facades\Cache::forget("course.{$quiz->course_id}");
                    \Illuminate\Support\Facades\Cache::forget('courses.active');
                }
            }
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order_number');
    }

    public function media(): HasMany
    {
        return $this->hasMany(QuestionMedia::class);
    }

    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_questions')
                    ->withPivot(['order_number', 'points_override']);
    }

    public function analytics(): HasOne
    {
        return $this->hasOne(QuestionAnalytic::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    public function getCorrectOptions()
    {
        return $this->options->filter(fn($o) => $o->is_correct);
    }

    public function getCorrectOptionIds(): array
    {
        return $this->getCorrectOptions()->pluck('id')->toArray();
    }
}
