<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonGroup extends Model
{
    protected $guarded = [];
    protected $appends = ['pdf_file_url'];

    /**
     * Get the URL for the attached PDF file if it exists.
     */
    public function getPdfFileUrlAttribute()
    {
        return $this->pdf_file ? url('storage/' . $this->pdf_file) : null;
    }

    /**
     * Get the course that owns the lesson group.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the lessons for the lesson group.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }
}
