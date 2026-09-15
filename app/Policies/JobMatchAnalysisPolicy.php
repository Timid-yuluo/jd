<?php

namespace App\Policies;

use App\Models\JobMatchAnalysis;
use App\Models\User;

class JobMatchAnalysisPolicy
{
    public function view(User $user, JobMatchAnalysis $analysis): bool
    {
        return $user->id === $analysis->user_id;
    }

    public function delete(User $user, JobMatchAnalysis $analysis): bool
    {
        return $user->id === $analysis->user_id;
    }
}
