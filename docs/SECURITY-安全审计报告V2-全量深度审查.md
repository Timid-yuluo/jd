# 安全审计报告 V2 — 全量深度审查

> 审查日期：2026-05-19  
> 审查范围：全项目文件（路由、中间件、控制器、模型、视图、配置、API、命令行、环境变量）  
> 审查基准：Laravel 13.x + PHP 8.3+ / OWASP Top 10 2021 / CWE 常见弱点枚举  
> 审查目标：在 V1 报告基础上，对支付安全、令牌安全、命令注入、数据泄露等深层风险进行全量扫描

---

## 一、总体安全态势评估

| 维度 | 评级 | V1 → V2 变化 | 说明 |
|------|------|-------------|------|
| 认证机制 | ✅ 良好 | — | 登录/注册有内置限流，OAuth 有 state 校验 |
| 授权控制 | ✅ 良好 | — | Spatie Permission RBAC，Policy 授权覆盖关键模型 |
| CSRF 防护 | ✅ 良好 | — | 除支付宝回调外全部启用 CSRF |
| XSS 防护 | ⚠️ 中等 | — | 部分 `{!! !!}` 输出可被绕过，CSP 使用了 unsafe-inline/eval |
| SQL 注入 | ✅ 良好 | — | Eloquent ORM 为主，极少数 raw SQL 使用参数化 |
| 支付安全 | 🔴 需加固 | 🆕 新增 | 回调缺少幂等保护，金额校验存在浮点精度问题 |
| 令牌安全 | 🔴 需加固 | 🆕 新增 | 邮箱验证/账号恢复令牌缺少速率限制和暴力破解防护 |
| 命令注入 | 🟠 需关注 | 🆕 新增 | Admin runCommand 的 args 未验证，数据库备份密码泄露 |
| 数据保护 | 🟡 需改善 | 🆕 新增 | 简历/令牌明文存储，Redis 无密码 |
| 文件上传 | ✅ 良好 | — | MIME 校验 + 扩展名黑名单 + 路径穿越防护 |
| 会话安全 | ✅ 良好 | — | Session ID 再生、登出失效、封禁用户强制下线 |
| API 安全 | ✅ 良好 | — | Token 哈希存储、每次请求轮换、过期检查 |
| 环境配置 | 🟡 需改善 | 🆕 新增 | .env 含真实密钥，Redis 无密码，微信签名验证禁用 |

---

## 二、问题总览

| # | 问题 | 等级 | CWE | 状态 |
|---|------|------|-----|------|
| 1 | .env 敏感凭证泄露风险 | 🔴 严重 | CWE-312 | 待修复 |
| 2 | 账号恢复令牌缺少速率限制 | 🔴 严重 | CWE-307 | 待修复 |
| 3 | 邮箱验证令牌可被枚举和重放 | 🔴 严重 | CWE-307/CWE-294 | 待修复 |
| 4 | 支付回调缺少幂等性保护 | 🔴 严重 | CWE-367 | 待修复 |
| 5 | CSP 策略过于宽松（unsafe-inline + unsafe-eval） | 🟠 高 | CWE-693 | 待修复 |
| 6 | custom_body_code 未经过 sanitizeHeadSnippet 清理 | 🟠 高 | CWE-79 | 待修复 |
| 7 | Admin runCommand 存在命令注入风险 | 🟠 高 | CWE-78 | 待修复 |
| 8 | 管理员批量删除用户缺少数量限制 | 🟠 高 | CWE-20 | 待修复 |
| 9 | API Token 通过响应头传递存在泄露风险 | 🟠 高 | CWE-312 | 待修复 |
| 10 | 数据库备份命令密码可能通过进程列表泄露 | 🟠 高 | CWE-214 | 待修复 |
| 11 | strip_tags 用于 HTML 输出不安全 | 🟡 中 | CWE-79 | 待修复 |
| 12 | 搜索功能存在 LIKE 注入风险 | 🟡 中 | CWE-89 | 待修复 |
| 13 | Redis 未设置密码 | 🟡 中 | CWE-306 | 待修复 |
| 14 | Session Redis 连接缺少加密 | 🟡 中 | CWE-319 | 待修复 |
| 15 | 邮箱验证令牌明文存储在数据库中 | 🟡 中 | CWE-312 | 待修复 |
| 16 | SESSION_SECURE_COOKIE 默认值不安全 | 🟡 中 | CWE-614 | 待修复 |
| 17 | SVG 上传存在 XSS 风险 | 🟡 中 | CWE-79 | 待修复 |
| 18 | 系统日志查看功能可能泄露敏感信息 | 🟡 中 | CWE-532 | 待修复 |
| 19 | 数据导出接口缺少速率限制 | 🟡 中 | CWE-770 | 待修复 |
| 20 | yuanToFen 浮点精度问题 | 🟡 中 | CWE-1339 | 待修复 |
| 21 | Gate::before 允许管理员绕过所有授权检查 | 🟢 低 | CWE-863 | 建议关注 |
| 22 | 微信支付签名验证被禁用 | 🟢 低 | CWE-347 | 建议关注 |
| 23 | 数据库导出缺少权限细分 | 🟢 低 | CWE-862 | 建议关注 |
| 24 | APP_SHOW_TEST_CREDENTIALS 配置项 | 🟢 低 | CWE-798 | 建议关注 |
| 25 | 简历内容字段未加密存储 | 🟢 低 | CWE-312 | 建议关注 |

---

## 三、严重级别问题（Critical）— 需立即修复

### 3.1 .env 敏感凭证泄露风险

**CWE**: CWE-312（敏感信息明文存储）  
**文件**: [.env](file:///www/wwwroot/124.221.19.20/.env)

**问题代码**:
```env
APP_KEY=base64:AOjs0quZi/s2GQQfS+dlJX9tR9NZX4dV1armmhHdc+Y=
DB_PASSWORD=123123
VOLCANO_API_KEY=REGENERATE_YOUR_KEY_ON_VOLCANO_CONSOLE
ZHIPU_API_KEY=REGENERATE_YOUR_KEY_ON_ZHIPU_CONSOLE
```

**风险说明**:
- `.env` 文件包含真实的 APP_KEY、数据库凭证、AI API Key 等核心敏感信息
- 如果 Web 服务器配置不当（如 Nginx 未拒绝 `.` 开头文件的访问），`.env` 可被直接下载
- APP_KEY 泄露将导致所有加密数据可被解密（Session、加密字段、API Token 等）
- 数据库凭证泄露将导致数据库被直接访问
- AI API Key 泄露将导致服务被盗用，产生费用损失

**修复建议**:

1. **确保 Nginx 拒绝访问隐藏文件**（在 Nginx 配置中添加）:
```nginx
location ~ /\. {
    deny all;
    access_log off;
    log_not_found off;
}
```

2. **立即轮换所有密钥**:
```bash
# 1. 生成新的 APP_KEY（注意：这会使现有加密数据失效！）
php artisan key:generate

# 2. 更换数据库密码
# 在 MySQL 中：ALTER USER '用户名'@'localhost' IDENTIFIED BY '新强密码';

# 3. 更换 AI API Key
# 在各平台控制台重新生成 Key
```

3. **设置文件权限**:
```bash
chmod 600 .env
chown www-data:www-data .env   # 仅 Web 服务器进程可读
```

4. **确认 .gitignore 已忽略 .env**（当前已忽略 ✅）

---

### 3.2 账号恢复令牌缺少速率限制

**CWE**: CWE-307（不适当的认证方案限制）  
**文件**: [ProfileController.php:403](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/User/ProfileController.php#L403)

**问题代码**:
```php
public function recoverAccount(string $token): RedirectResponse
{
    $recoveryToken = AccountRecoveryToken::where('token', $token)->first();
```

**风险说明**:
- 恢复令牌为 64 位十六进制字符串（`bin2hex(random_bytes(32))`），强度足够
- 但路由 `/user/account/recover/{token}` **没有速率限制**
- 攻击者可以无限次尝试暴力猜解令牌
- 路由不在 `auth` 中间件组内，未认证用户也能访问
- 虽然令牌空间极大（2^256），但无限制的尝试仍构成理论风险，且会产生大量数据库查询

**修复建议**:

在路由定义中添加速率限制中间件:

```php
// routes/web_user.php
Route::get('/account/recover/{token}', [ProfileController::class, 'recoverAccount'])
    ->middleware('throttle:6,1')  // 每分钟6次
    ->name('account.recover');
```

在 `AppServiceProvider::configureRateLimiting()` 中添加专用限流器:

```php
RateLimiter::for('account-recover', function (Request $request): Limit {
    return Limit::perMinute(6)->by('account-recover:'.$request->ip());
});
```

进一步加固：在控制器中添加 IP 维度的尝试次数检查:

```php
public function recoverAccount(Request $request, string $token): RedirectResponse
{
    $ipKey = 'account-recover:' . $request->ip();
    if (RateLimiter::tooManyAttempts($ipKey, 10)) {
        return redirect()->route('user.dashboard')
            ->with('error', '尝试次数过多，请稍后再试。');
    }
    RateLimiter::hit($ipKey, 300); // 5分钟内最多10次

    $recoveryToken = AccountRecoveryToken::where('token', $token)->first();
    // ...
}
```

---

### 3.3 邮箱验证令牌可被枚举和重放

**CWE**: CWE-307（不适当的认证方案限制）/ CWE-294（认证绕过重放攻击）  
**文件**: [EmailVerificationController.php:96](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/User/EmailVerificationController.php#L96)

**问题代码**:
```php
public function verify(Request $request, string $token): RedirectResponse
{
    $user = User::where('verification_token', $token)->first();

    if (!$user) {
        return redirect()->route('user.dashboard')->with('error', '验证链接无效或已过期。');
    }

    // 检查令牌是否过期
    if ($user->verification_token_expires_at && now()->gt($user->verification_token_expires_at)) {
        return redirect()->route('user.profile')->with('error', '验证链接已过期，请重新发送验证邮件。');
    }

    // 更新验证状态
    $user->email_verified_at = now();
    $user->verification_token = null;
    $user->verification_token_expires_at = null;
    $user->save();
```

**风险说明**:
- 验证令牌通过 URL 传递（`/email/verify/{token}`），无速率限制
- 令牌在验证成功后才被清除，存在竞态窗口（并发请求可能在清除前多次使用同一令牌）
- 不同错误消息（"无效" vs "已过期"）可帮助攻击者枚举有效令牌
- 令牌明文存储在 `users` 表的 `verification_token` 字段中

**修复建议**:

1. **添加速率限制中间件**:
```php
Route::get('/email/verify/{token}', [EmailVerificationController::class, 'verify'])
    ->middleware('throttle:6,1')
    ->name('email.verify');
```

2. **使用数据库事务 + 行锁防止竞态条件**:
```php
public function verify(Request $request, string $token): RedirectResponse
{
    $user = DB::transaction(function () use ($token) {
        $user = User::where('verification_token', $token)
            ->lockForUpdate()
            ->first();

        if (!$user || ($user->verification_token_expires_at && now()->gt($user->verification_token_expires_at))) {
            return null;
        }

        $user->email_verified_at = now();
        $user->verification_token = null;
        $user->verification_token_expires_at = null;
        $user->save();

        return $user;
    });

    if (!$user) {
        return redirect()->route('user.dashboard')->with('error', '验证链接无效或已过期。');
    }

    return redirect()->route('user.dashboard')->with('success', '邮箱验证成功！');
}
```

3. **统一错误消息**（避免信息泄露）:
```php
// 不要区分"无效"和"已过期"，统一返回相同消息
return redirect()->route('user.dashboard')->with('error', '验证链接无效或已过期。');
```

---

### 3.4 支付回调缺少幂等性保护（重复通知攻击）

**CWE**: CWE-367（Time-of-check Time-of-use 竞态条件）  
**文件**: [AlipayPayController.php:26](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/AlipayPayController.php#L26)

**问题代码**:
```php
public function notify(Request $request): Response
{
    // ...签名验证后直接处理
    $this->subscriptionService->fulfillOrder($subscriptionOrder, ...);
```

**风险说明**:
- 支付宝回调路由已排除 CSRF 验证（合理），但缺少对同一笔交易重复通知的幂等保护
- 支付宝可能在网络超时情况下重复发送回调通知
- 如果 `fulfillOrder` 不是幂等的，可能导致：
  - 重复充值/开通会员
  - 重复发放积分/额度
  - 用户余额异常
- `yuanToFen` 使用浮点转换可能精度丢失，导致金额校验失效

**修复建议**:

1. **在 `fulfillOrder` 中增加订单状态检查**（确保仅处理 pending 状态）:
```php
public function fulfillOrder(SubscriptionOrder $order, string $paymentNo, string $paymentMethod): void
{
    if ($order->status !== SubscriptionOrder::STATUS_PENDING) {
        Log::info('Order already processed, skipping', [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'current_status' => $order->status,
        ]);
        return;
    }

    DB::transaction(function () use ($order, $paymentNo, $paymentMethod) {
        // 使用 lockForUpdate 防止并发
        $order = SubscriptionOrder::lockForUpdate()->find($order->id);

        if ($order->status !== SubscriptionOrder::STATUS_PENDING) {
            return;
        }

        $order->update([
            'status' => SubscriptionOrder::STATUS_PAID,
            'payment_no' => $paymentNo,
            'payment_method' => $paymentMethod,
            'paid_at' => now(),
        ]);

        // ... 开通会员逻辑
    });
}
```

2. **严格校验支付金额与订单金额一致**:
```php
$paidAmountFen = $this->yuanToFen($totalAmount);

if ($paidAmountFen !== (int) $subscriptionOrder->amount) {
    Log::warning('Alipay amount mismatch', [
        'order_no' => $subscriptionOrder->order_no,
        'expected' => $subscriptionOrder->amount,
        'received_fen' => $paidAmountFen,
        'received_yuan' => $totalAmount,
    ]);
    return response('fail', 400);
}
```

3. **使用 bcmath 替代浮点运算**（修复 `yuanToFen` 精度问题）:
```php
private function yuanToFen(string $amountYuan): int
{
    return (int) bcmul($amountYuan, '100', 0);
}
```

---

## 四、高级别问题（High）— 尽快修复

### 4.1 CSP 策略过于宽松（unsafe-inline + unsafe-eval）

**CWE**: CWE-693（保护机制失效）  
**文件**: [SecurityHeaders.php:113](file:///www/wwwroot/124.221.19.20/app/Http/Middleware/SecurityHeaders.php#L113)

**问题代码**:
```php
"script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net ...",
```

**风险说明**:
- `'unsafe-inline'` 允许页面内所有内联 `<script>` 和事件处理器执行，完全绕过 CSP 的 XSS 防护
- `'unsafe-eval'` 允许 `eval()` / `new Function()` 执行任意代码
- 项目已生成 CSP nonce，但未在 `script-src` 中使用，形同虚设
- 这使得即使存在 XSS 漏洞，CSP 也无法作为第二道防线

**修复建议**:

**阶段一**（最低限度，移除 unsafe-inline）:
```php
"script-src 'self' 'unsafe-eval' 'nonce-{$nonce}' https://cdn.jsdelivr.net",
```

**阶段二**（完整加固，移除 unsafe-eval）:
```php
"script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net",
```

同步修改前端所有内联脚本使用 nonce:
```blade
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    // 内联脚本代码
</script>
```

> 注意：Alpine.js 依赖 `unsafe-eval`，如使用 Alpine.js 需升级到 v3.x（使用 `Alpine.data()` 替代 `x-data` 内联表达式），或暂时保留 `unsafe-eval`。

---

### 4.2 custom_body_code 未经过 sanitizeHeadSnippet 清理

**CWE**: CWE-79（跨站脚本 — 存储型）  
**文件**: [SiteSettingController.php:198](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/Admin/SiteSettingController.php#L198)

**问题代码**:
```php
foreach (['analytics_google', 'analytics_baidu', 'analytics_clarity', 'custom_head_code'] as $snippetKey) {
    if (isset($validated[$snippetKey]) && is_string($validated[$snippetKey])) {
        $validated[$snippetKey] = $this->sanitizeHeadSnippet($validated[$snippetKey]);
    }
}
// ❌ custom_body_code 不在清理列表中！
```

**风险说明**:
- `custom_body_code` 在保存时未经过 `sanitizeHeadSnippet` 清理
- 虽然渲染时通过 `$sanitizeHeadSnippet()` 函数处理，但保存端未做清理意味着恶意代码可能被存入数据库
- 如果渲染端的清理逻辑被绕过或修改，恶意代码将直接执行
- 违反"纵深防御"原则——应在入口和出口都进行清理

**修复建议**:

将 `custom_body_code` 加入清理列表:
```php
foreach (['analytics_google', 'analytics_baidu', 'analytics_clarity', 'custom_head_code', 'custom_body_code'] as $snippetKey) {
    if (isset($validated[$snippetKey]) && is_string($validated[$snippetKey])) {
        $validated[$snippetKey] = $this->sanitizeHeadSnippet($validated[$snippetKey]);
    }
}
```

---

### 4.3 Admin runCommand 存在命令注入风险

**CWE**: CWE-78（OS 命令注入）  
**文件**: [SystemOpsController.php:215](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/Admin/SystemOpsController.php#L215)

**问题代码**:
```php
public function runCommand(Request $request): JsonResponse
{
    $command = $request->get('command');
    $args = $request->get('args', []);

    // 白名单命令
    $allowedCommands = [
        'migrate:status',
        'migrate:rollback',    // ⚠️ 高危操作
        'db:seed',             // ⚠️ 高危操作
        'storage:link',
        'route:list',
        'schedule:list',
    ];

    if (!in_array($command, $allowedCommands)) {
        return response()->json(['success' => false, 'message' => '命令不在允许列表中'], 403);
    }

    Artisan::call($command, $args);
```

**风险说明**:
- 虽然有命令白名单，但 `$args` 参数直接从用户输入传入 `Artisan::call`，未做任何验证
- 攻击者可构造恶意 `args`，例如 `migrate:rollback` 的 `--step=999` 可导致大量回滚
- `db:seed` 可导致数据污染或覆盖
- 白名单中包含 `migrate:rollback` 和 `db:seed` 这种高危操作

**修复建议**:

1. **对 `$args` 做严格验证**:
```php
public function runCommand(Request $request): JsonResponse
{
    $validated = $request->validate([
        'command' => 'required|string',
        'args' => 'nullable|array',
        'args.*' => 'string|max:255',
    ]);

    $command = $validated['command'];
    $args = $validated['args'] ?? [];

    $allowedCommands = [
        'migrate:status',
        'storage:link',
        'route:list',
        'schedule:list',
    ];

    // ❌ 移除 migrate:rollback 和 db:seed
```

2. **为每个命令定义允许的参数白名单**:
```php
$allowedArgs = [
    'migrate:status' => [],
    'storage:link' => [],
    'route:list' => ['columns', 'sort'],
    'schedule:list' => [],
];

if (!isset($allowedArgs[$command])) {
    return response()->json(['success' => false, 'message' => '命令不在允许列表中'], 403);
}

// 过滤掉不允许的参数
$args = array_intersect_key($args, array_flip($allowedArgs[$command]));
```

3. **添加操作确认机制**（二次验证管理员密码）:
```php
if (!$request->has('confirmed') || !$this->verifyAdminPassword($request->input('password'))) {
    return response()->json(['success' => false, 'message' => '请确认操作'], 403);
}
```

---

### 4.4 管理员批量删除用户缺少数量限制

**CWE**: CWE-20（输入验证不充分）  
**文件**: [UserController.php:225](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/Admin/UserController.php#L225)

**问题代码**:
```php
public function batchDestroy(Request $request): RedirectResponse
{
    $ids = $request->input('ids', []);
    $ids = array_diff($ids, [auth()->id()]);
    User::whereIn('id', $ids)->delete();
```

**风险说明**:
- 没有对 `$ids` 数量做上限限制，可一次删除全部用户
- 没有验证 `$ids` 中的值是否为有效整数
- 没有防止删除其他管理员账号的检查
- 使用 `delete()` 而非 `forceDelete()`，需确认与 SoftDeletes 模型一致

**修复建议**:
```php
public function batchDestroy(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'ids' => 'required|array|min:1|max:100',
        'ids.*' => 'required|integer|exists:users,id',
    ]);

    $ids = $validated['ids'];

    // 防止删除管理员账号
    $adminIds = User::where('is_admin', true)->pluck('id')->toArray();
    $ids = array_diff($ids, $adminIds);

    // 防止删除自己
    $ids = array_diff($ids, [auth()->id()]);

    if (empty($ids)) {
        return back()->with('warning', '没有可删除的用户。');
    }

    User::whereIn('id', $ids)->each(function (User $user) {
        // 触发模型事件（如关联数据清理）
        $user->delete();
    });

    return back()->with('success', '已删除 ' . count($ids) . ' 个用户。');
}
```

---

### 4.5 API Token 通过响应头传递存在泄露风险

**CWE**: CWE-312（敏感信息明文存储/传输）  
**文件**: [ApiTokenAuth.php:64](file:///www/wwwroot/124.221.19.20/app/Http/Middleware/ApiTokenAuth.php#L64)

**问题代码**:
```php
$newToken = Str::random(64);
$response->headers->set('X-New-Token', $newToken);
```

**风险说明**:
- 每次请求都轮换 Token 并通过 `X-New-Token` 响应头返回新 Token
- 如果中间有代理/CDN 缓存了响应头，Token 可能泄露
- 宽限期机制（`api_token_grace`）30秒内旧 Token 仍可用，增加了泄露窗口
- 多个并发请求可能导致 Token 混乱（请求 A 获取新 Token，请求 B 仍使用旧 Token）

**修复建议**:

1. **确保 API 响应不被缓存**:
```php
$response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
$response->headers->set('Pragma', 'no-cache');
```

2. **缩短宽限期到 5-10 秒**:
```php
Cache::put('api_token_grace:' . $oldTokenHash, $user->id, 5); // 5秒宽限期
```

3. **确保 CDN/反向代理不缓存 API 响应**:
```nginx
location /api/ {
    proxy_cache off;
    add_header X-Cache-Status "BYPASS";
}
```

---

### 4.6 数据库备份命令密码可能通过进程列表泄露

**CWE**: CWE-214（通过进程表泄露敏感信息）  
**文件**: [DatabaseBackup.php:44](file:///www/wwwroot/124.221.19.20/app/Console/Commands/DatabaseBackup.php#L44)

**问题代码**:
```php
$command = sprintf(
    'mysqldump --user=%s --password=%s --host=%s ...',
    escapeshellarg((string) $config['username']),
    escapeshellarg((string) $config['password']),
```

**风险说明**:
- 虽然使用了 `escapeshellarg` 防止命令注入，但通过命令行参数传递密码会出现在 `/proc` 进程列表中
- 其他系统用户可通过 `ps aux | grep mysqldump` 看到完整命令行（含密码）
- 这在共享服务器环境中尤为危险

**修复建议**:

**方案一**：使用 `MYSQL_PWD` 环境变量:
```php
$command = sprintf(
    'MYSQL_PWD=%s mysqldump --user=%s --host=%s ...',
    escapeshellarg((string) $config['password']),
    escapeshellarg((string) $config['username']),
    escapeshellarg((string) $config['host']),
);
```

**方案二**（推荐）：使用 MySQL 配置文件:
```php
$cnfPath = tempnam(sys_get_temp_dir(), 'mycnf_');
file_put_contents($cnfPath, sprintf(
    "[client]\nuser=%s\npassword=%s\nhost=%s\n",
    $config['username'],
    $config['password'],
    $config['host']
));
chmod($cnfPath, 0600);

$command = sprintf(
    'mysqldump --defaults-extra-file=%s ...',
    escapeshellarg($cnfPath),
);

// 执行后删除临时文件
register_shutdown_function(function () use ($cnfPath) {
    if (file_exists($cnfPath)) {
        unlink($cnfPath);
    }
});
```

---

## 五、中级别问题（Medium）— 建议修复

### 5.1 strip_tags 用于 HTML 输出不安全

**CWE**: CWE-79（跨站脚本）  
**文件**: 多个 Blade 模板

**问题代码**:
```blade
{{-- resources/views/user/notifications/show.blade.php:62 --}}
{!! strip_tags($notification->content, '<p><br><strong>...') !!}

{{-- resources/views/admin/email-logs/show.blade.php:66 --}}
{!! strip_tags($emailLog->content, '<p><br><strong>...') !!}
```

**风险说明**: `strip_tags` 不会过滤标签属性中的事件处理器，例如：
```html
<a href="javascript:alert(1)">click</a>
<img src=x onerror=alert(1)>
<div onmouseover=alert(1)>hover me</div>
```
这些恶意内容将直接通过 `strip_tags` 的过滤。

**修复建议**: 统一使用 `App\Support\HtmlPurifier::clean()` 处理所有 HTML 输出:
```blade
{!! App\Support\HtmlPurifier::clean($notification->content) !!}
{!! App\Support\HtmlPurifier::clean($emailLog->content) !!}
```

---

### 5.2 搜索功能存在 LIKE 注入风险

**CWE**: CWE-89（SQL 注入）  
**文件**: [UserController.php:70](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/Admin/UserController.php#L70) 及其他搜索功能

**问题代码**:
```php
$q->where('name', 'like', "%{$search}%")
  ->orWhere('email', 'like', "%{$search}%");
```

**风险说明**: 用户输入的 `$search` 中的 `%` 和 `_` 是 SQL LIKE 的通配符，未被转义。攻击者可通过输入 `%` 或 `_` 来改变查询语义：
- 输入 `%` 可匹配所有记录
- 输入 `_` 可匹配任意单字符

**修复建议**:
```php
$search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
$q->where('name', 'like', "%{$search}%")
  ->orWhere('email', 'like', "%{$search}%");
```

或在项目中创建一个通用的搜索辅助函数:
```php
function escapeLike(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}
```

---

### 5.3 Redis 未设置密码

**CWE**: CWE-306（关键功能缺少认证）  
**文件**: [.env:45](file:///www/wwwroot/124.221.19.20/.env#L45)

**问题代码**:
```env
REDIS_PASSWORD=null
```

**风险说明**: Redis 无密码保护，如果 Redis 端口（6379）暴露到外网，攻击者可直接访问 Redis，可能导致：
- Session 数据泄露/篡改
- 缓存数据投毒
- 通过 Redis 写入 SSH 密钥或 crontab 实现服务器接管

**修复建议**:
1. 为 Redis 设置强密码:
```env
REDIS_PASSWORD=your-strong-redis-password-here
```

2. 在 Redis 配置中设置:
```conf
requirepass your-strong-redis-password-here
bind 127.0.0.1
```

3. 在防火墙中限制 6379 端口仅本地访问:
```bash
sudo ufw deny 6379
# 或
sudo iptables -A INPUT -p tcp --dport 6379 -s 127.0.0.1 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 6379 -j DROP
```

---

### 5.4 Session Redis 连接缺少加密

**CWE**: CWE-319（明文传输敏感信息）  
**文件**: [.env:28](file:///www/wwwroot/124.221.19.20/.env#L28)

**问题代码**:
```env
SESSION_DRIVER=redis
```

**风险说明**: Session 数据存储在 Redis 中，但 Redis 连接未启用 TLS 加密。如果 Redis 与应用不在同一台机器，Session 数据（含认证信息）可能在网络中明文传输。

**修复建议**: 如果 Redis 是远程服务，启用 TLS 连接:
```env
REDIS_SCHEME=tls
REDIS_PORT=6380
REDIS_PASSWORD=your-strong-redis-password-here
```

如果 Redis 与应用在同一台机器，确保使用 `127.0.0.1` 连接:
```env
REDIS_HOST=127.0.0.1
```

---

### 5.5 邮箱验证令牌明文存储在数据库中

**CWE**: CWE-312（敏感信息明文存储）  
**文件**: [EmailVerificationController.php:67](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/User/EmailVerificationController.php#L67)

**问题代码**:
```php
$user->verification_token = $token;
$user->save();
```

**风险说明**: 验证令牌以明文形式存储在 `users` 表的 `verification_token` 字段中。如果数据库被泄露（SQL 注入、备份泄露等），攻击者可直接使用这些令牌验证任意邮箱。

**修复建议**: 像密码一样存储令牌的哈希值，验证时用哈希比对:
```php
// 生成时存储哈希
$user->verification_token = hash('sha256', $token);
$user->save();

// 验证时比对哈希
$tokenHash = hash('sha256', $token);
$user = User::where('verification_token', $tokenHash)->first();
```

发送给用户的邮件中仍包含原始令牌，但数据库中只存储哈希值。

---

### 5.6 SESSION_SECURE_COOKIE 默认值不安全

**CWE**: CWE-614（安全关键 Cookie 缺少 "Secure" 属性）  
**文件**: [.env.example](file:///www/wwwroot/124.221.19.20/.env.example)

**问题代码**:
```php
// config/session.php
'secure' => env('SESSION_SECURE_COOKIE', null),
```

**风险说明**: 虽然生产环境 `.env` 中 `SESSION_SECURE_COOKIE=true` ✅，但默认值为 `null`。如果新部署时忘记设置此变量，Cookie 将在 HTTP 连接上传输，可能被中间人截获。

**修复建议**: 将默认值改为 `true`:
```php
'secure' => env('SESSION_SECURE_COOKIE', true),
```

---

### 5.7 SVG 上传存在 XSS 风险

**CWE**: CWE-79（跨站脚本）  
**文件**: [FileManagerController.php:34](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/Admin/FileManagerController.php#L34)

**问题代码**:
```php
'image/svg+xml',  // 允许 SVG
```

**风险说明**: SVG 文件可以包含 JavaScript 代码:
```xml
<svg xmlns="http://www.w3.org/2000/svg" onload="alert(document.cookie)">
<svg><script>alert(1)</script></svg>
```
直接在浏览器中打开 SVG URL 会执行恶意脚本。虽然仅管理员可上传，但存储在 `public` 磁盘意味着任何人都可以直接访问 URL。

**修复建议**:

**方案一**（推荐）：对 SVG 文件进行净化处理:
```php
use App\Support\HtmlPurifier;

// 上传时净化 SVG
if ($mimeType === 'image/svg+xml') {
    $content = file_get_contents($file->getRealPath());
    $content = HtmlPurifier::clean($content);
    file_put_contents($file->getRealPath(), $content);
}
```

**方案二**：在 Nginx 中对 SVG 文件添加安全头:
```nginx
location ~* \.svg$ {
    add_header Content-Security-Policy "sandbox; script-src 'none'";
    add_header X-Content-Type-Options "nosniff";
}
```

**方案三**：将 SVG 的 Content-Type 响应头设为 `application/octet-stream` 强制下载。

---

### 5.8 系统日志查看功能可能泄露敏感信息

**CWE**: CWE-532（通过日志文件泄露敏感信息）  
**文件**: [SystemOpsController.php:224](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/Admin/SystemOpsController.php#L224)

**问题代码**:
```php
$content = file_get_contents($logFile);
$lines = array_reverse(explode("\n", (string) $content));
$lines = array_slice($lines, 0, 500);
```

**风险说明**: 日志中可能包含用户邮箱、API 密钥、SQL 查询、Session ID 等敏感信息，直接在 Web 界面展示存在风险。如果管理员账号被入侵，攻击者可通过日志页面获取更多敏感信息。

**修复建议**: 对日志内容进行脱敏处理:
```php
private function sanitizeLogLine(string $line): string
{
    // 隐藏邮箱
    $line = preg_replace('/[\w.-]+@[\w.-]+\.\w+/', '***@***.***', $line);
    // 隐藏 API Key
    $line = preg_replace('/(api[_-]?key|token|secret)["\s:=]+["\']?[\w-]{8,}/i', '$1=***', $line);
    // 隐藏 IP 地址（部分）
    $line = preg_replace('/\b(\d{1,3}\.)(\d{1,3}\.)(\d{1,3}\.)(\d{1,3})\b/', '$1$2***.$4', $line);
    return $line;
}
```

---

### 5.9 数据导出接口缺少速率限制

**CWE**: CWE-770（资源分配无限制或节流不当）  
**文件**: [ProfileController.php:247](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/User/ProfileController.php#L247)

**问题说明**: 数据导出接口没有速率限制，用户可以频繁请求导出，造成服务器负载。大量数据导出可能消耗大量内存和 CPU。

**修复建议**:
```php
Route::post('/profile/export-data', [ProfileController::class, 'exportData'])
    ->middleware('throttle:2,60')  // 每小时2次
    ->name('profile.export-data');
```

---

### 5.10 yuanToFen 浮点精度问题

**CWE**: CWE-1339（数值精度不足）  
**文件**: [AlipayPayController.php:88](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/AlipayPayController.php#L88)

**问题代码**:
```php
return (int) round(((float) $amountYuan) * 100);
```

**风险说明**: 浮点数运算可能导致精度丢失:
- `19.99 * 100` 可能得到 `1998.9999...` 而非 `1999`
- `0.1 + 0.2` 不等于 `0.3`
- 在金融场景中，这种精度问题可能导致金额校验失败或资金损失

**修复建议**:
```php
private function yuanToFen(string $amountYuan): int
{
    return (int) bcmul((string) $amountYuan, '100', 0);
}
```

确保 PHP 已启用 `bcmath` 扩展（通常已默认安装）。

---

## 六、低级别问题（Low）— 建议关注

### 6.1 Gate::before 允许管理员绕过所有授权检查

**CWE**: CWE-863（不正确的授权）  
**文件**: [AppServiceProvider.php:138](file:///www/wwwroot/124.221.19.20/app/Providers/AppServiceProvider.php#L138)

**问题代码**:
```php
Gate::before(function ($user, $ability) {
    return $user->is_admin ? true : null;
});
```

**风险说明**: 管理员可以绕过所有 Policy 检查，包括跨租户数据访问。如果管理员账号被入侵，影响面极大。

**修复建议**: 对关键操作（如删除数据、修改权限、导出数据）仍应进行二次验证。可考虑对特定 ability 例外:
```php
Gate::before(function ($user, $ability) {
    // 某些操作即使是管理员也需要显式授权
    $restrictedAbilities = ['delete-system-data', 'modify-permissions'];
    if (in_array($ability, $restrictedAbilities)) {
        return null; // 不自动放行，继续走 Policy 检查
    }
    return $user->is_admin ? true : null;
});
```

---

### 6.2 微信支付签名验证被禁用

**CWE**: CWE-347（未正确验证加密签名）  
**文件**: [.env:67](file:///www/wwwroot/124.221.19.20/.env#L67)

**问题代码**:
```env
WECHAT_PAY_SIGNATURE_VERIFY=false
```

**风险说明**: 微信支付签名验证被禁用，如果启用微信支付回调，将无法验证请求来源真实性，攻击者可伪造支付成功通知。

**修复建议**: 在启用微信支付前，必须将此设为 `true` 并正确配置证书:
```env
WECHAT_PAY_SIGNATURE_VERIFY=true
```

---

### 6.3 数据库导出缺少权限细分

**CWE**: CWE-862（缺少授权）  
**文件**: [DataExportController.php](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/Admin/DataExportController.php)

**风险说明**: 数据导出功能可能缺少细粒度权限控制，任何有后台访问权限的管理员都能导出所有用户数据（含邮箱、手机号等 PII）。

**修复建议**:
1. 添加 `permission:data-export` 权限控制
2. 记录导出操作审计日志
3. 导出文件添加水印或标识

---

### 6.4 APP_SHOW_TEST_CREDENTIALS 配置项

**CWE**: CWE-798（硬编码凭证）  
**文件**: [.env.example:6](file:///www/wwwroot/124.221.19.20/.env.example#L6)

**问题代码**:
```env
APP_SHOW_TEST_CREDENTIALS=false
```

**风险说明**: 此配置项暗示可能存在测试凭证展示功能。如果生产环境误设为 `true`，将暴露测试账号和密码。

**修复建议**: 确保此功能在生产环境中完全禁用，并在代码中添加环境检查:
```php
if (config('app.show_test_credentials') && !app()->isLocal()) {
    Log::warning('APP_SHOW_TEST_CREDENTIALS is enabled in non-local environment!');
    // 强制关闭
    config(['app.show_test_credentials' => false]);
}
```

---

### 6.5 简历内容字段未加密存储

**CWE**: CWE-312（敏感信息明文存储）  
**文件**: [Resume.php:19](file:///www/wwwroot/124.221.19.20/app/Models/Resume.php#L19)

**问题代码**:
```php
protected $fillable = [
    'content_raw',
    'content_structured',
    // ...
];

protected function casts(): array
{
    return [
        'content_structured' => 'array',
        // ❌ 未使用 'encrypted' cast
    ];
}
```

**风险说明**: 简历内容（`content_raw`、`content_structured`）以明文存储在数据库中，包含用户敏感个人信息（姓名、电话、地址、工作经历等）。如果数据库泄露，用户隐私将直接暴露。

**修复建议**: 对敏感字段使用 Laravel 的 `encrypted` cast:
```php
protected function casts(): array
{
    return [
        'content_structured' => 'encrypted:array',
        'content_raw' => 'encrypted',
    ];
}
```

> 注意：启用加密后，现有数据需要迁移。建议写一个 Artisan 命令批量加密现有数据。

---

### 6.6 User 模型 $fillable 包含不应填充的敏感字段

**CWE**: CWE-915（不受控制的批量操作）  
**文件**: [User.php:20-35](file:///www/wwwroot/124.221.19.20/app/Models/User.php#L20-L35)

**问题代码**:
```php
#[Fillable([
    ...
    'api_token',              // 不应被前端直接赋值
    'current_plan_slug',      // 用户可能篡改会员级别
    'is_suspended',           // 用户可能自行解除封禁
    'suspended_at',
    'suspended_reason',
    'suspended_by',
])]
```

**风险说明**: 虽然当前所有控制器都通过 FormRequest 限制输入字段，但如果未来新增端点使用了 `$request->all()` 或未经验证的批量赋值，攻击者可能提权、解封自身账号、或篡改 API Token。

**修复建议**: 从 `$fillable` 中移除敏感字段，通过专用方法设置:
```php
// 从 $fillable 中移除以下字段
// 'api_token', 'current_plan_slug', 'is_suspended', 'suspended_at', 'suspended_reason', 'suspended_by'

// 添加专用方法
public function updateApiToken(string $token, ?Carbon $expiresAt): void
{
    $this->forceFill([
        'api_token' => hash('sha256', $token),
        'api_token_expires_at' => $expiresAt,
    ])->save();
}

public function suspend(string $reason, User $byUser): void
{
    $this->forceFill([
        'is_suspended' => true,
        'suspended_at' => now(),
        'suspended_reason' => $reason,
        'suspended_by' => $byUser->id,
    ])->save();
}

public function activatePlan(string $planSlug): void
{
    $this->forceFill(['current_plan_slug' => $planSlug])->save();
}
```

---

## 七、已具备的优秀安全实践 ✅

以下机制当前已正确实施，无需修改：

| 机制 | 实现位置 | 说明 |
|------|---------|------|
| APP_DEBUG 生产环境关闭 | `.env` | `APP_DEBUG=false` ✅ |
| Session 加密 | `config/session.php` | `SESSION_ENCRYPT=true` ✅ |
| SESSION_SECURE_COOKIE | `.env` | 生产环境已启用 ✅ |
| SESSION_SAME_SITE | `.env` | `lax` 防止 CSRF ✅ |
| HSTS 头 | `SecurityHeaders` 中间件 | 仅 HTTPS + 生产环境 ✅ |
| 安全响应头 | `SecurityHeaders` 中间件 | X-Content-Type-Options, Referrer-Policy, Permissions-Policy ✅ |
| CSP 头 | `SecurityHeaders` 中间件 | 已配置（偏宽松，见 4.1） |
| 登录 IP + Email 双重限流 | `AuthenticatedSessionController` | 5次/分钟 ✅ |
| 注册限流 | `RegisteredUserController` | 5次/小时 ✅ |
| 密码强度策略 | `AppServiceProvider::boot()` | 大小写+数字+符号+泄露检查 ✅ |
| 密码历史记录 | `PasswordHistory` 模型 | 防止重复使用 ✅ |
| OAuth State 校验 | `OAuthStateService` | 防 CSRF ✅ |
| 文件上传 MIME 校验 | `FileManagerController` | 白名单 + 黑名单 ✅ |
| 文件上传路径穿越防护 | `FileManagerController::sanitizePath()` | `realpath` 校验 ✅ |
| 请求体大小限制 | `LimitRequestBody` 中间件 | 10MB/20MB ✅ |
| 封禁用户强制下线 | `EnsureUserIsActive` 中间件 | Session 销毁 ✅ |
| API Token SHA-256 哈希存储 | `ApiTokenAuth` 中间件 | ✅ |
| API Token 过期检查 | `ApiTokenAuth` 中间件 | ✅ |
| API Token 轮换宽限期 | `ApiTokenAuth` 中间件 | 30秒 ✅ |
| 强制 JSON 响应（API 路由） | `ForceJsonResponse` 中间件 | ✅ |
| CSRF 全路由启用 | `bootstrap/app.php` | 除支付宝回调外 ✅ |
| RBAC 权限控制 | Spatie Permission | Admin 路由 + Policy ✅ |
| 分场景速率限制 | `AppServiceProvider` | AI/简历/面试/登录/反馈 ✅ |
| 幂等键机制 | `TemplateApplicationService` | 导出、模板应用 ✅ |
| 反馈 page_url 同源校验 | `FeedbackController::store()` | ✅ |
| API 异常不暴露堆栈 | `bootstrap/app.php` | 返回 trace_id ✅ |
| 用户敏感字段 Hidden | `User` 模型 `#[Hidden]` | password, remember_token, api_token ✅ |
| 微信 OpenID 加密存储 | `User` 模型 casts | `'wechat_openid' => 'encrypted'` ✅ |
| Admin 操作独立日志 | `LogAdminActions` 中间件 | ✅ |
| Model Observer 操作日志 | `UserObserver`, `ResumeObserver` | ✅ |
| 账号注销冷静期 | `AccountRecoveryToken` | 7天恢复期 ✅ |
| 账号恢复令牌强度 | `AccountRecoveryToken` | 64位十六进制 ✅ |
| HtmlPurifier 富文本清理 | `App\Support\HtmlPurifier` | DOMDocument 解析 ✅ |
| CORS 配置合理 | `config/cors.php` | ✅ |
| 代理信任配置 | `TrustProxies` 中间件 | ✅ |
| 无 unserialize 使用 | 全项目 | ✅ |
| .gitignore 忽略 .env | `.gitignore` | ✅ |
| .htaccess 目录列表禁用 | `public/.htaccess` | `Options -Indexes` ✅ |

---

## 八、修复优先级排序与工时估算

### 8.1 第一优先级（严重 — 本周内修复）

| 序号 | 问题 | 预计工时 | 影响面 |
|------|------|---------|--------|
| 1 | .env 敏感凭证保护 + 密钥轮换 | 1h | 全系统 |
| 2 | 账号恢复令牌速率限制 | 30min | 用户账号安全 |
| 3 | 邮箱验证令牌加固（速率限制 + 事务锁 + 哈希存储） | 1h | 邮箱验证安全 |
| 4 | 支付回调幂等性 + 金额校验 + bcmath | 1.5h | 支付安全 |

### 8.2 第二优先级（高 — 两周内修复）

| 序号 | 问题 | 预计工时 | 影响面 |
|------|------|---------|--------|
| 5 | CSP 移除 unsafe-inline（改用 nonce） | 2h | XSS 防护 |
| 6 | custom_body_code 清理 | 15min | 存储型 XSS |
| 7 | Admin runCommand 加固 | 1h | 命令注入 |
| 8 | 批量删除用户数量限制 | 30min | 数据安全 |
| 9 | API Token 响应头泄露防护 | 30min | API 安全 |
| 10 | 数据库备份密码保护 | 45min | 凭证泄露 |

### 8.3 第三优先级（中 — 一个月内修复）

| 序号 | 问题 | 预计工时 | 影响面 |
|------|------|---------|--------|
| 11 | strip_tags 替换为 HtmlPurifier | 1h | XSS 防护 |
| 12 | LIKE 注入修复 | 30min | SQL 注入 |
| 13 | Redis 密码设置 | 15min | 基础设施安全 |
| 14 | Session Redis 加密 | 30min | 传输安全 |
| 15 | 验证令牌哈希存储 | 45min | 数据安全 |
| 16 | SESSION_SECURE_COOKIE 默认值 | 5min | Cookie 安全 |
| 17 | SVG 上传 XSS 防护 | 30min | XSS 防护 |
| 18 | 系统日志脱敏 | 1h | 信息泄露 |
| 19 | 数据导出速率限制 | 15min | 资源滥用 |
| 20 | yuanToFen bcmath 修复 | 15min | 金融精度 |

### 8.4 第四优先级（低 — 持续改善）

| 序号 | 问题 | 预计工时 | 影响面 |
|------|------|---------|--------|
| 21 | Gate::before 例外处理 | 30min | 授权安全 |
| 22 | 微信支付签名验证启用 | 30min | 支付安全 |
| 23 | 数据导出权限细分 | 1h | 权限安全 |
| 24 | APP_SHOW_TEST_CREDENTIALS 环境检查 | 15min | 凭证泄露 |
| 25 | 简历内容加密存储 | 2h | 数据保护 |
| 26 | User $fillable 移除敏感字段 | 30min | 批量赋值 |

---

## 九、安全加固检查清单

完成所有修复后，请逐项确认：

- [ ] `.env` 文件权限为 600，Nginx 拒绝访问隐藏文件
- [ ] 所有密钥已轮换（APP_KEY、DB密码、API Key）
- [ ] 账号恢复路由添加速率限制
- [ ] 邮箱验证使用事务锁 + 令牌哈希存储
- [ ] 支付回调实现幂等性保护
- [ ] 金额计算使用 bcmath
- [ ] CSP 移除 unsafe-inline，使用 nonce
- [ ] custom_body_code 保存时清理
- [ ] Admin runCommand 参数白名单
- [ ] 批量删除数量限制 + 管理员保护
- [ ] API 响应添加 no-cache 头
- [ ] 数据库备份使用配置文件传密码
- [ ] 所有 HTML 输出使用 HtmlPurifier
- [ ] LIKE 查询转义通配符
- [ ] Redis 设置密码
- [ ] Session Redis 启用 TLS（如远程）
- [ ] SESSION_SECURE_COOKIE 默认值为 true
- [ ] SVG 上传净化或安全头
- [ ] 系统日志脱敏
- [ ] 数据导出速率限制
- [ ] 微信支付签名验证已启用
- [ ] 简历敏感字段加密存储
- [ ] User $fillable 移除敏感字段

---

*审查人：AI 安全审计助手*  
*审查基于：Laravel 13 + PHP 8.3+ 运行环境*  
*审查标准：OWASP Top 10 2021 / CWE 常见弱点枚举*
