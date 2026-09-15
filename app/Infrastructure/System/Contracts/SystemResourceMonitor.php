<?php

declare(strict_types=1);

namespace App\Infrastructure\System\Contracts;

interface SystemResourceMonitor
{
    public function getCpuUsage(): int;

    public function getMemoryUsage(): int;

    public function getDiskUsage(): int;
}
