<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $guarded = [];

    protected $appends = ['youtube_id', 'duration'];

    public function getYoutubeIdAttribute(): string
    {
        return $this->youtube_video_id ?? '';
    }

    public function getDurationAttribute(): string
    {
        $seconds = $this->duration_in_seconds ?? 0;
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    /**
     * Get the course that owns the lesson.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the progress records for the lesson.
     */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }
}
