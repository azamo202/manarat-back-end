<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Question $question): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Question $question): bool
    {
        return $user->is_admin;
    }

    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }
}
