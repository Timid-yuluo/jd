<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\User;

final class JobApplicationPolicy
{
    public function view(User $user, JobApplication $application): bool
    {
        return $user->id === $application->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, JobApplication $application): bool
    {
        return $user->id === $application->user_id;
    }

    public function delete(User $user, JobApplication $application): bool
    {
        return $user->id === $application->user_id;
    }
}
