<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotification;

final class UserNotificationPolicy
{
    public function view(User $user, UserNotification $notification): bool
    {
        return $user->id === $notification->user_id;
    }

    public function update(User $user, UserNotification $notification): bool
    {
        return $user->id === $notification->user_id;
    }

    public function delete(User $user, UserNotification $notification): bool
    {
        return $user->id === $notification->user_id;
    }
}
