<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AccessBan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;

class AccessBansExpired
{
    use Dispatchable;

    /**
     * @param  Collection<int, AccessBan>  $bans
     */
    public function __construct(
        public readonly Collection $bans,
    ) {}
}
