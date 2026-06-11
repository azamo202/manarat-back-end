<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lesson_progress';

    protected $guarded = [];

    /**
     * Get the user that owns the lesson progress.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lesson that owns the lesson progress.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
