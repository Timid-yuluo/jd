<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// API v1 路由已停用（API Token 认证机制已移除）
// 如需恢复 API 功能，请先实现新的认证方案（如 Sanctum）
Route::middleware(['force.json', 'request.id', 'throttle:api'])->prefix('v1')->group(function (): void {
    // 暂无可用路由
});
