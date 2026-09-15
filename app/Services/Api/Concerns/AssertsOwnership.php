<?php

declare(strict_types=1);

namespace App\Services\Api\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

trait AssertsOwnership
{
    public function assertOwnership(int $userId, Model $model): void
    {
        if ($model->user_id !== $userId) {
            throw new AuthorizationException('无权访问该资源');
        }
    }
}
