<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    /**
     * Admins can create quizzes.
     */
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Admins can update any quiz.
     */
    public function update(User $user, Quiz $quiz): bool
    {
        return $user->is_admin;
    }

    /**
     * Admins can delete any quiz.
     */
    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->is_admin;
    }

    /**
     * Admins can view all quiz details; students see only published available ones.
     */
    public function view(User $user, Quiz $quiz): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $quiz->isAvailable();
    }

    /**
     * Only admins can publish or archive quizzes.
     */
    public function publish(User $user, Quiz $quiz): bool
    {
        return $user->is_admin;
    }

    public function archive(User $user, Quiz $quiz): bool
    {
        return $user->is_admin;
    }

    public function duplicate(User $user, Quiz $quiz): bool
    {
        return $user->is_admin;
    }

    public function viewAnalytics(User $user, Quiz $quiz): bool
    {
        return $user->is_admin;
    }
}
