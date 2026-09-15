# 项目安全加固审查报告

> 审查日期：2026-05-17  
> 审查范围：全项目（路由、中间件、控制器、模型、FormRequest、配置、Blade 模板、API）  
> 审查目标：全面识别安全隐患并提出可操作的加固建议

---

## 一、总体安全态势评估

| 维度 | 评级 | 说明 |
|------|------|------|
| 认证机制 | ✅ 良好 | 登录/注册有内置限流，OAuth 有 state 校验 |
| 授权控制 | ✅ 良好 | Spatie Permission RBAC，Policy 授权覆盖关键模型 |
| CSRF 防护 | ✅ 良好 | 除支付宝回调外全部启用 CSRF |
| XSS 防护 | ⚠️ 中等 | 部分 `{!! !!}` 输出可被绕过，CSP 使用了 unsafe-inline/eval |
| SQL 注入 | ✅ 良好 | Eloquent ORM 为主，极少数 raw SQL 使用参数化 |
| Mass Assignment | ⚠️ 中等 | User 模型 $fillable 包含敏感字段 |
| 速率限制 | ✅ 良好 | 覆盖 AI 调用、简历操作、登录、反馈等场景 |
| 文件上传 | ✅ 良好 | MIME 校验 + 扩展名黑名单 + 路径穿越防护 |
| 会话安全 | ✅ 良好 | 回话 ID 再生、登出失效、封禁用户强制下线 |
| API 安全 | ✅ 良好 | Token 哈希存储、每次请求轮换、过期检查 |
| 日志安全 | ✅ 良好 | 未发现敏感信息日志泄漏 |
| 异常处理 | ✅ 良好 | 生产环境不暴露堆栈，API 返回 trace_id |

---

## 二、高优先级安全加固建议

### 2.1 User 模型 $fillable 包含不应填充的敏感字段

**文件**: `app/Models/User.php` [L20-L35](file:///www/wwwroot/124.221.19.20/app/Models/User.php#L20-L35)

**问题**：以下字段在 `$fillable` 中，理论上可能被跨权限 mass assignment 利用：

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

**风险等级**：🔴 高  
**风险说明**：虽然当前所有控制器都通过 FormRequest 限制输入字段，但如果未来新增端点使用了 `$request->all()` 或未经验证的批量赋值，攻击者可能提权、解封自身账号、或篡改 API token。

**修复建议**：
```php
#[Fillable([
    'name',
    'email',
    'password',
    'wechat_openid',
    'school',
    'major',
    'verification_token',
    'verification_token_expires_at',
    // ❌ 移除以下字段，通过专门方法设置
    // 'api_token',
    // 'current_plan_slug',
    // 'is_suspended',
    // 'suspended_at',
    // 'suspended_reason',
    // 'suspended_by',
])]
```

对于需要程序化设置的敏感字段，应通过专用方法：
```php
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
```

---

### 2.2 CSP 策略使用了 unsafe-inline 和 unsafe-eval

**文件**: `app/Http/Middleware/SecurityHeaders.php` [L112-L123](file:///www/wwwroot/124.221.19.20/app/Http/Middleware/SecurityHeaders.php#L112-L123)

**问题**：
```php
"script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
```

**风险等级**：🔴 高  
**风险说明**：
- `'unsafe-inline'` 允许页面内所有内联 `<script>` 和执行字符串，完全绕过 CSP 的 XSS 防护
- `'unsafe-eval'` 允许 `eval()` 执行任意代码
- 这使得 CSP 形同虚设，无法防御 XSS 攻击

**修复建议**：
1. 使用 nonce 替代 `unsafe-inline`：
```php
// 在中间件中生成 nonce
$nonce = base64_encode(random_bytes(16));
$request->attributes->set('csp_nonce', $nonce);

"script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net",
```

2. 在 Blade 模板中对内联脚本使用 nonce：
```blade
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    // inline script
</script>
```

3. 如果 `unsafe-eval` 是 Alpine.js 等框架必需的，至少应保留 `unsafe-inline` 的移除：
```php
// 最低限度：移除 unsafe-inline，保留 unsafe-eval（若框架必需）
"script-src 'self' 'unsafe-eval' https://cdn.jsdelivr.net",
```

---

### 2.3 Help 文章内容以 HTML 原始输出（存储型 XSS 风险）

**文件**: `resources/views/public/help/show.blade.php` [L77](file:///www/wwwroot/124.221.19.20/resources/views/public/help/show.blade.php#L77)

**问题**：
```blade
{!! $article->content !!}
```

**风险等级**：🔴 高  
**风险说明**：帮助文章内容由管理员在后台编辑，未经过滤直接以 HTML 输出。如果管理员账号被盗用（或恶意管理员），可以在文章内容中植入 `<script>` 标签，实现存储型 XSS 攻击，影响所有访问帮助中心的用户。

**修复建议**：
```blade
{{-- 使用 HTML Purifier 过滤 --}}
{!! Purifier::clean($article->content, [
    'HTML.Allowed' => 'p,br,strong,b,em,i,u,ul,ol,li,a[href|target],h1,h2,h3,h4,h5,h6,table,tr,td,th,thead,tbody,blockquote,code,pre,img[src|alt|width|height],span,div',
    'HTML.AllowedAttributes' => 'href,target,src,alt,width,height,class',
]) !!}
```

或在 HelpArticle 模型中添加存取器：
```php
use Stevebauman\Purify\Facades\Purify;

public function getContentAttribute(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    return Purify::clean($value);
}
```

> 备注：项目已安装 `stevebauman/purify`？如未安装，需 `composer require stevebauman/purify`。

---

### 2.4 自定义 head/body 代码注入过滤可被绕过

**文件**: `app/Providers/AppServiceProvider.php` [L372-L396](file:///www/wwwroot/124.221.19.20/app/Providers/AppServiceProvider.php#L372-L396)

**问题**：`sanitizeHeadSnippet()` 方法使用正则过滤，可被多种方式绕过：

```php
$hasAllowedTag = preg_match('/<\s*(script|meta|link|style|noscript)\b/i', $snippet) === 1;
```

**风险等级**：🟡 中  
**风险说明**：管理员在后台设置的自定义 head/body 代码（如 Google Analytics、百度统计等），通过正则过滤后以 `{!! !!}` 输出。此正则可被以下方式绕过：
- `<<script>` （双尖括号）
- `<scr<script>ipt>` （嵌套标签）
- `data:text/html;base64,...` 注入

**修复建议**：
1. 限制此类功能仅超级管理员使用，并在 `SystemSettingAuditLog` 中记录变更
2. 使用 HTML Purifier 替代手写正则：
```php
private function sanitizeHeadSnippet(string $snippet): string
{
    $snippet = trim($snippet);
    if ($snippet === '') {
        return '';
    }
    
    // 严格模式：只允许完整的 script 标签（含闭合标签）
    $allowed = '<script>,<meta>,<link>,<style>,<noscript>';
    $cleaned = strip_tags($snippet, $allowed);
    
    // 确保 script 有闭合标签
    if ($cleaned !== $snippet) {
        return '';
    }
    
    return $cleaned;
}
```

---

## 三、中优先级安全加固建议

### 3.1 注册路由缺少限流中间件

**文件**: `routes/web_public.php` [L41-L44](file:///www/wwwroot/124.221.19.20/routes/web_public.php#L41-L44)

**问题**：
```php
Route::get('/register', ...)->name('register');
Route::post('/register', ...)->name('register.store');
```

注册路由没有应用 `throttle` 中间件。虽然 `RegisteredUserController::store()` 内部有 IP 限流（5次/小时），但这种做法不够规范。

**风险等级**：🟡 中  
**修复建议**：
```php
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register')
        ->name('register.store');
});
```

并在 `AppServiceProvider::configureRateLimiting()` 中添加：
```php
RateLimiter::for('register', function (Request $request): Limit {
    return Limit::perHour(5)->by('register:'.$request->ip());
});
```

---

### 3.2 登录路由缺少显式限流中间件

**文件**: `routes/web_public.php` [L39-L40](file:///www/wwwroot/124.221.19.20/routes/web_public.php#L39-L40)

**问题**：登录路由虽在 `AuthenticatedSessionController` 内部实现了 IP + Email 双重限流，但路由层面未声明 `throttle:login`。

**风险等级**：🟡 中  
**修复建议**：
```php
Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:login')
    ->name('login.store');
```

---

### 3.3 session 加密未强制开启

**文件**: `config/session.php` [L47](file:///www/wwwroot/124.221.19.20/config/session.php#L47)

**问题**：
```php
'encrypt' => env('SESSION_ENCRYPT', false),
```

Session 数据加密默认关闭。如果 `.env` 中 `SESSION_ENCRYPT` 未设置，session 数据以明文存储在数据库中。

**风险等级**：🟡 中  
**修复建议**：在 `.env` 中设置：
```
SESSION_ENCRYPT=true
```

或在 `config/session.php` 中将默认值改为 `true`：
```php
'encrypt' => env('SESSION_ENCRYPT', true),
```

---

### 3.4 API Token 轮换机制存在丢失风险

**文件**: `app/Http/Middleware/ApiTokenAuth.php` [L56-L62](file:///www/wwwroot/124.221.19.20/app/Http/Middleware/ApiTokenAuth.php#L56-L62)

**问题**：API Token 在每次请求后轮换，新 token 通过 `X-New-Token` 响应头返回给客户端。如果响应在网络传输中丢失，客户端的旧 token 已失效（数据库中已更新），导致客户端被永久锁定。

**风险等级**：🟡 中  
**修复建议**：在 token 轮换时保留旧 token 的短暂有效期（宽限期 30 秒）：
```php
// 更新时保留旧哈希
$oldToken = $user->api_token;
$user->update([
    'api_token' => hash('sha256', $newToken),
    'api_token_expires_at' => now()->addDays(30),
]);

// 允许旧 token 在 30 秒内仍可验证
Cache::put('api_token_grace:'.$oldToken, $user->id, 30);
```

在验证时：
```php
$user = User::query()->where('api_token', hash('sha256', $token))->first();

if (! $user) {
    // 宽限期检查
    $userId = Cache::get('api_token_grace:'.hash('sha256', $token));
    if ($userId) {
        $user = User::find($userId);
    }
}
```

---

### 3.5 Admin/UserController 分页参数过渡透传

**文件**: `app/Http/Controllers/Admin/UserController.php` [L61](file:///www/wwwroot/124.221.19.20/app/Http/Controllers/Admin/UserController.php#L61) 等多处

**问题**：
```php
$users = $query->paginate(...)->appends($request->except('page'));
```

`$request->except('page')` 会将所有非 `page` 的查询参数追加到分页 URL 中，包括恶意构造的参数。

**风险等级**：🟡 中（低利用率但影响面广）  
**修复建议**：使用白名单方式：
```php
$users = $query->paginate(...)->appends($request->only([
    'search', 'role', 'status', 'per_page', 'sort', 'direction'
]));
```

---

### 3.6 通知内容使用 strip_tags 可能遗漏 XSS 向量

**文件**: `resources/views/user/notifications/show.blade.php` [L62](file:///www/wwwroot/124.221.19.20/resources/views/user/notifications/show.blade.php#L62)

**问题**：
```blade
{!! strip_tags($notification->content, '<p><br><strong>...') !!}
```

`strip_tags` 不会过滤标签属性中的事件处理器（如 `onclick`、`onerror` 等）。

**风险等级**：🟡 中  
**修复建议**：使用 HTML Purifier 替代 `strip_tags`：
```blade
{!! Purifier::clean($notification->content) !!}
```

---

## 四、低优先级安全加固建议

### 4.1 缺少 HTTP Security Headers 完整性

**文件**: `app/Http/Middleware/SecurityHeaders.php` [L20-L22](file:///www/wwwroot/124.221.19.20/app/Http/Middleware/SecurityHeaders.php#L20-L22)

**问题**：`Permissions-Policy` 仅限制了 `camera`、`microphone`、`geolocation`，未限制其他可能被滥用的功能。

**修复建议**：
```php
$response->headers->set('Permissions-Policy', 
    'camera=(), microphone=(), geolocation=(), '
    . 'payment=(), usb=(), magnetometer=(), gyroscope=(), '
    . 'speaker-selection=(), screen-wake-lock=()'
);
```

---

### 4.2 缺少 HSTS preload 独立配置

**文件**: `app/Http/Middleware/SecurityHeaders.php` [L34-L38](file:///www/wwwroot/124.221.19.20/app/Http/Middleware/SecurityHeaders.php#L34-L38)

**问题**：HSTS `preload` 指令已硬编码，未提供配置开关。如果域名未提交到 HSTS preload 列表，`preload` 指令不会有负面影响，但建议可配置。

**修复建议**：
```php
if (app()->isProduction() && $request->isSecure()) {
    $hsts = 'max-age=31536000; includeSubDomains';
    if (config('security.hsts_preload', false)) {
        $hsts .= '; preload';
    }
    $response->headers->set('Strict-Transport-Security', $hsts);
}
```

---

### 4.3 缺少密码历史记录强制策略

**文件**: `app/Providers/AppServiceProvider.php` [L148](file:///www/wwwroot/124.221.19.20/app/Providers/AppServiceProvider.php#L148)

**问题**：虽然 `PasswordHistory` 模型存在，但密码规则中没有使用 `->uncompromised()` 之外的密码历史检查。

**修复建议**：在 `ProfilePasswordUpdateRequest` 中添加自定义规则，检查新密码是否与最近 N 次历史密码重复：
```php
'password' => ['required', 'confirmed', Rules\Password::defaults(), function (string $attribute, mixed $value, \Closure $fail) use ($user) {
    $recentPasswords = PasswordHistory::where('user_id', $user->id)
        ->latest()
        ->take(5)
        ->pluck('password_hash');
    
    foreach ($recentPasswords as $hash) {
        if (Hash::check($value, $hash)) {
            $fail('新密码不能与最近使用过的密码相同。');
            return;
        }
    }
}],
```

---

### 4.4 注册页面缺少 reCAPTCHA/人机验证

**问题**：注册只依赖 IP 限流（5次/小时），无 CAPTCHA 验证，可能被自动化脚本批量注册。

**修复建议**：集成 Google reCAPTCHA v3 或 Cloudflare Turnstile 在注册表单。

---

### 4.5 支付宝回调缺少 IP 白名单校验

**文件**: `routes/web_public.php` [L18](file:///www/wwwroot/124.221.19.20/routes/web_public.php#L18)

**问题**：支付宝回调端点排除了 CSRF（正常），但未验证请求来源 IP 是否为支付宝官方 IP。

**风险等级**：🟢 低（支付宝 SDK 自身有签名验证）  
**修复建议**：添加支付宝 IP 白名单校验：
```php
Route::post('/alipay-pay/notify', [AlipayPayController::class, 'notify'])
    ->middleware('alipay.ip.whitelist')
    ->name('alipay-pay.notify');
```

---

### 4.6 Debug 模式日志级别需调整

**文件**: `config/logging.php` [L25-L78](file:///www/wwwroot/124.221.19.20/config/logging.php#L25-L78)

**问题**：所有日志通道默认 `LOG_LEVEL=debug`，生产环境可能记录过多敏感信息。

**修复建议**：生产环境 `.env` 设置：
```
LOG_LEVEL=warning
```

或为生产环境单独配置日志级别。

---

## 五、已具备的优秀安全实践 ✅

以下机制当前已正确实施，无需修改：

| 机制 | 实现位置 |
|------|---------|
| 安全响应头（X-Content-Type-Options, Referrer-Policy, XSS-Protection） | `SecurityHeaders` 中间件 |
| HSTS（仅 HTTPS + 生产环境） | `SecurityHeaders` 中间件 |
| 请求体大小限制（10MB/20MB） | `LimitRequestBody` 中间件 |
| 封禁用户强制下线 + Session 销毁 | `EnsureUserIsActive` 中间件 |
| API Token SHA-256 哈希存储 | `ApiTokenAuth` 中间件 |
| API Token 过期检查 | `ApiTokenAuth` 中间件 |
| 强制 JSON 响应（API 路由） | `ForceJsonResponse` 中间件 |
| 登录 IP + Email 双重限流 | `AuthenticatedSessionController` |
| 密码强度策略（大小写+数字+符号+已泄露检查） | `AppServiceProvider::boot()` |
| 文件上传 MIME + 扩展名校验 | `FileManagerController` |
| 文件上传路径穿越防护（realpath 校验） | `FileManagerController::sanitizePath()` |
| 文件下载禁止目录（`directoryExists` 检查） | `FileManagerController::download()` |
| 所有路由 CSRF 启用（除支付宝回调） | `bootstrap/app.php` |
| RBAC 权限控制（Spatie Permission） | Admin 路由 + `Gate::policy()` |
| 分场景速率限制（AI/简历/面试/登录/反馈） | `AppServiceProvider::configureRateLimiting()` |
| OAuth State 参数校验（防 CSRF） | `OAuthStateService` |
| 反馈 page_url 同源校验 | `FeedbackController::store()` |
| API 异常不暴露堆栈（返回 trace_id） | `bootstrap/app.php` withExceptions |
| 用户敏感字段 Hidden（password, remember_token, api_token） | `User` 模型 `#[Hidden]` |
| 微信 OpenID 加密存储 | `User` 模型 casts: `'wechat_openid' => 'encrypted'` |
| Model Observer 记录操作日志 | `UserObserver`, `ResumeObserver` 等 |
| Admin 操作独立日志 | `LogAdminActions` 中间件 |

---

## 六、修复优先级排序

| 序号 | 问题 | 优先级 | 预计工时 |
|------|------|--------|---------|
| 1 | User $fillable 移除敏感字段 | 🔴 高 | 30min |
| 2 | CSP 移除 unsafe-inline（改用 nonce） | 🔴 高 | 2h |
| 3 | Help 文章 HTML 过滤 | 🔴 高 | 30min |
| 4 | 自定义代码注入过滤加固 | 🟡 中 | 1h |
| 5 | 注册路由添加限流中间件 | 🟡 中 | 15min |
| 6 | SESSION_ENCRYPT 强制开启 | 🟡 中 | 5min |
| 7 | API Token 轮换宽限期 | 🟡 中 | 30min |
| 8 | 分页参数白名单 | 🟡 中 | 20min |
| 9 | 通知内容 HTML Purifier | 🟡 中 | 15min |
| 10 | Permissions-Policy 完善 | 🟢 低 | 5min |
| 11 | HSTS preload 可配置 | 🟢 低 | 10min |
| 12 | 密码历史强制策略 | 🟢 低 | 30min |
| 13 | 注册 CAPTCHA | 🟢 低 | 1h |
| 14 | 支付宝 IP 白名单 | 🟢 低 | 20min |
| 15 | 生产环境 LOG_LEVEL=warning | 🟢 低 | 5min |

> **总预计工时**：约 7 小时完成全部加固项。

---

*审查人：AI 代码审查助手*  
*审查基于：Laravel 13 + PHP 8.3+ 运行环境*