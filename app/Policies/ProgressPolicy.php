<?php

namespace App\Policies;

use App\Models\LessonProgress;
use App\Models\User;

class ProgressPolicy
{
    /**
     * Determine whether the user can update the lesson progress.
     */
    public function update(User $user, LessonProgress $lessonProgress): bool
    {
        return $user->id === $lessonProgress->user_id;
    }
}
