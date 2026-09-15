<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * 安全 URL 验证 trait
 *
 * 提供 URL 协议白名单校验（#13, #39）
 * 防止 javascript:、data: 等危险协议注入
 */
trait ValidatesSafeUrl
{
    /**
     * 校验 URL 是否为安全协议
     *
     * @param  string|null  $url  待校验的 URL
     * @return bool  true=安全；false=危险或非法
     */
    public function isSafeUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        // 解析 URL 协议
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme === false) {
            return false;
        }

        // 允许的协议白名单（公司情报模块已下线，直接硬编码 http/https）
        $allowed = ['http', 'https'];

        // 协议小写后比较
        return in_array(strtolower((string) $scheme), $allowed, true);
    }

    /**
     * 转义为安全 URL，不安全则返回空
     */
    public function sanitizeUrl(?string $url): string
    {
        return $this->isSafeUrl($url) ? e($url) : '';
    }
}
