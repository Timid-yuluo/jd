<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InterviewSession;
use App\Models\User;

final class InterviewPolicy
{
    public function view(User $user, InterviewSession $interview): bool
    {
        return $user->id === $interview->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, InterviewSession $interview): bool
    {
        return $user->id === $interview->user_id;
    }

    public function delete(User $user, InterviewSession $interview): bool
    {
        return $user->id === $interview->user_id;
    }
}
