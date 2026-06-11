<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $guarded = [];

    /**
     * Get the lessons for the course.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }
}
