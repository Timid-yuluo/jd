<?php

declare(strict_types=1);

if (! function_exists('formatBytes')) {
    /**
     * 格式化字节大小
     */
    function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2).' '.$units[$unitIndex];
    }
}

if (! function_exists('escapeLike')) {
    /**
     * 转义 MySQL LIKE 语句中的特殊字符（\、%、_）。
     *
     * 仅转义 LIKE 模式中的通配符，不处理 SQL 字符串层面的转义——
     * Laravel Query Builder 会通过 PDO 参数绑定处理 SQL 注入防护。
     */
    function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
