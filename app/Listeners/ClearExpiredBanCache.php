<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccessBansExpired;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class ClearExpiredBanCache
{
    public function handle(AccessBansExpired $event): void
    {
        $count = $event->bans->count();

        foreach ($event->bans as $ban) {
            Cache::forget("ban_check:{$ban->ban_type}:{$ban->ban_value}");
        }

        Log::info('Expired access bans purged', [
            'count' => $count,
            'types' => $event->bans->groupBy('ban_type')->map->count()->toArray(),
        ]);
    }
}
