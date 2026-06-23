<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $guarded = [];

    protected $appends = ['cover_image_url', 'plan_file_url'];

    protected static function booted()
    {
        static::saved(function ($course) {
            \Illuminate\Support\Facades\Cache::forget('courses.active');
            \Illuminate\Support\Facades\Cache::forget("course.{$course->id}");
        });

        static::deleted(function ($course) {
            \Illuminate\Support\Facades\Cache::forget('courses.active');
            \Illuminate\Support\Facades\Cache::forget("course.{$course->id}");
        });
    }

    /**
     * Get the cover image full URL.
     */
    public function getCoverImageUrlAttribute()
    {
        return $this->cover_image ? asset('storage/' . $this->cover_image) : null;
    }

    /**
     * Get the plan file full URL.
     */
    public function getPlanFileUrlAttribute()
    {
        return $this->plan_file ? asset('storage/' . $this->plan_file) : null;
    }

    /**
     * Get the lessons for the course.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * Get the lesson groups for the course.
     */
    public function lessonGroups(): HasMany
    {
        return $this->hasMany(LessonGroup::class);
    }
}
