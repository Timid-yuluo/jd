<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;

final class FeedbackPolicy
{
    public function view(User $user, Feedback $feedback): bool
    {
        return $feedback->user_id === $user->id;
    }

    public function reply(User $user, Feedback $feedback): bool
    {
        return $feedback->user_id === $user->id;
    }

    public function delete(User $user, Feedback $feedback): bool
    {
        return $feedback->user_id === $user->id;
    }
}
