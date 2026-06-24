<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;

class QuizAttemptPolicy
{
    /**
     * Only the owner of the attempt or an admin can view it.
     */
    public function view(User $user, QuizAttempt $attempt): bool
    {
        return $user->is_admin || $user->id === $attempt->user_id;
    }

    /**
     * Only the attempt owner can save answers or submit.
     */
    public function update(User $user, QuizAttempt $attempt): bool
    {
        return $user->id === $attempt->user_id;
    }
}
