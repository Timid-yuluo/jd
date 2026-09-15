<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Models\User;

final class ResumeCreateQuotaService
{
    /**
     * @return array{limit:int,used:int,remaining:int,allowed:bool}
     */
    public function resolve(User $user): array
    {
        $plan = $user->currentPlan();
        $limit = max(0, (int) ($plan?->getTotalLimit('resumes') ?? 0));
        $used = (int) $user->resumes()->count();
        $remaining = $limit > 0 ? max(0, $limit - $used) : 0;

        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => $remaining,
            'allowed' => $limit === 0 || $used < $limit,
        ];
    }
}
