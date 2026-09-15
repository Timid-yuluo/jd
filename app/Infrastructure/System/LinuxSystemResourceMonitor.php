<?php

declare(strict_types=1);

namespace App\Infrastructure\System;

use App\Infrastructure\System\Contracts\SystemResourceMonitor;

final class LinuxSystemResourceMonitor implements SystemResourceMonitor
{
    public function getCpuUsage(): int
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return 0;
        }

        $load = sys_getloadavg();
        $cores = 1;

        if ($this->isAccessibleFile('/proc/cpuinfo')) {
            $cpuinfo = @file_get_contents('/proc/cpuinfo');
            if ($cpuinfo !== false) {
                $cores = max(1, substr_count($cpuinfo, 'processor'));
            }
        }

        return $load ? min(100, (int) round($load[0] / $cores * 100)) : 0;
    }

    public function getMemoryUsage(): int
    {
        if (PHP_OS_FAMILY !== 'Linux' || ! $this->isAccessibleFile('/proc/meminfo')) {
            return 0;
        }

        $meminfo = @file_get_contents('/proc/meminfo');
        if ($meminfo === false) {
            return 0;
        }

        preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $avail);

        if (empty($total[1]) || empty($avail[1])) {
            return 0;
        }

        return (int) round((1 - $avail[1] / $total[1]) * 100);
    }

    public function getDiskUsage(): int
    {
        $diskFree = disk_free_space(base_path());
        $diskTotal = disk_total_space(base_path());

        if ($diskTotal <= 0) {
            return 0;
        }

        return (int) round((1 - $diskFree / $diskTotal) * 100);
    }

    private function isAccessibleFile(string $path): bool
    {
        $openBasedir = trim((string) ini_get('open_basedir'));
        if ($openBasedir !== '') {
            $allowedPaths = array_filter(array_map('trim', explode(PATH_SEPARATOR, $openBasedir)));
            if ($allowedPaths !== []) {
                $normalizedPath = rtrim(str_replace('\\', '/', $path), '/');
                $isWithinAllowedPath = false;

                foreach ($allowedPaths as $allowedPath) {
                    $normalizedAllowedPath = rtrim(str_replace('\\', '/', $allowedPath), '/');
                    if ($normalizedAllowedPath !== '' && ($normalizedPath === $normalizedAllowedPath || str_starts_with($normalizedPath, $normalizedAllowedPath.'/'))) {
                        $isWithinAllowedPath = true;
                        break;
                    }
                }

                if (! $isWithinAllowedPath) {
                    return false;
                }
            }
        }

        return is_readable($path);
    }
}
