# 反馈系统 — 代码质量与安全机制审查报告

> 审查日期：2026-05-14  
> 审查范围：反馈系统全部代码（模型、控制器、策略、服务、路由、迁移、视图、前端JS、邮件模板）  
> 审查目标：识别代码缺陷、安全漏洞、架构缺陷，并提出可操作的改进建议

---

## 目录

- [一、安全问题](#一安全问题)
  - [严重 (Critical)](#严重-critical)
  - [高危 (High)](#高危-high)
  - [中等 (Medium)](#中等-medium)
- [二、代码质量问题](#二代码质量问题)
  - [严重 Bug](#严重-bug)
  - [架构设计问题](#架构设计问题)
  - [一般性问题](#一般性问题)
- [三、已有优秀设计](#三已有优秀设计)
- [四、改进优先级排序](#四改进优先级排序)
- [五、建议修复方案](#五建议修复方案)

---

## 一、安全问题

### 严重 (Critical)

#### 1. 开放重定向漏洞 — `page_url` 未做校验

**位置**：`app/Http/Controllers/User/FeedbackController.php` 第 37-43 行 → `resources/views/user/feedbacks/show.blade.php` 第 42 行

**问题**：用户提交反馈时可自由填写 `page_url`，视图模板中直接将其作为 `<a href>` 输出：

```blade
<a href="{{ $feedback->page_url }}" target="_blank">{{ $feedback->page_name ?: $feedback->page_url }}</a>
```

攻击者可提交 `page_url: "https://evil.com/phishing"`，其他用户（含管理员）点击后跳转至恶意站点，可被用于钓鱼攻击。

**风险**：用户点击链接后被导向恶意页面，可能导致凭证泄露。

**修复建议**：
- 方案 A：校验 `page_url` 必须为当前域名或相对路径（推荐）
- 方案 B：在视图层添加 `rel="noopener noreferrer nofollow"` 属性并标注外部链接
- 方案 C：存储时只保留 path 部分，渲染时拼接当前域名

```php
// 方案 A：在 store 方法中增加校验
'page_url' => ['nullable', 'string', 'max:500', function ($attribute, $value, $fail) {
    if (filter_var($value, FILTER_VALIDATE_URL) && !str_starts_with(parse_url($value, PHP_URL_HOST), config('app.host'))) {
        $fail('页面地址必须为当前站点的链接');
    }
}],
```

---

#### 2. 批量赋值漏洞 — `$fillable` 包含管理员专属字段

**位置**：`app/Models/Feedback.php` 第 61-77 行

**问题**：模型的 `$fillable` 数组包含了不应由用户端写入的字段：

```php
protected $fillable = [
    'user_id', 'visitor_email', 'page_url', 'page_name',
    'category', 'title', 'content', 'status',           // ← status 不应由用户设置
    'adoption_status',                                    // ← 管理员专属
    'priority',                                           // ← 管理员专属
    'admin_note',                                         // ← 管理员专属
    'adopted_at', 'adopted_by', 'adoption_note',          // ← 管理员专属
    'metadata',
];
```

当前 `store()` 方法仅传递了部分字段，安全性依赖于控制器层的手动筛选。但若后续开发者使用 `Feedback::create($request->all())` 或 `Feedback::update($request->all())`，攻击者即可通过提交 `status=closed`、`adoption_status=adopted` 等字段实现提权。

**风险**：恶意用户可篡改反馈状态、优先级、采纳结果等管理端字段。

**修复建议**：
- 方案 A：从 `$fillable` 中移除管理端字段，管理端使用 `forceFill()` 写入
- 方案 B：在模型中定义两层白名单常量，控制器按场景选择

```php
// 方案 A
protected $fillable = [
    'user_id', 'visitor_email', 'page_url', 'page_name',
    'category', 'title', 'content', 'metadata',
];

// 管理端写入时
$feedback->forceFill([
    'status' => $data['status'],
    'priority' => $data['priority'],
])->save();
```

---

#### 3. metadata 中 `screen`/`language` 用户可控且未校验

**位置**：`app/Http/Controllers/User/FeedbackController.php` 第 47-51 行

**问题**：

```php
$data['metadata'] = [
    'user_agent' => $request->header('User-Agent'),
    'screen' => $request->input('screen'),     // 用户可注入任意字符串
    'language' => $request->input('language'),  // 用户可注入任意字符串
];
```

`screen` 和 `language` 来自用户输入，无格式校验。虽然 Blade `{{ }}` 会自动 HTML 转义，但如果：
- 前端 JS 直接渲染此数据
- 未来改用 `{!! !!}` 输出
- 数据被其他接口以 JSON 返回给前端动态渲染

则存在存储型 XSS 风险。

**风险**：存储型 XSS，攻击者可在 metadata 中注入恶意脚本。

**修复建议**：

```php
$data['metadata'] = [
    'user_agent' => $request->header('User-Agent'),
    'screen' => preg_match('/^\d+x\d+$/', $request->input('screen', ''))
        ? $request->input('screen') : null,
    'language' => preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $request->input('language', ''))
        ? $request->input('language') : null,
];
```

---

### 高危 (High)

#### 4. 删除操作缺少限流保护

**位置**：`routes/web_admin.php` 第 100 行

**问题**：

```php
Route::delete('/feedbacks/{feedback}', [FeedbackController::class, 'destroy'])
    ->name('feedbacks.destroy')
    ->middleware('permission:feedbacks.delete');  // ← 无 throttle
```

对比其他管理端写操作均有限流：

| 路由 | 限流 |
|------|------|
| `PUT /status` | `throttle:feedback-admin-write` |
| `POST /reply` | `throttle:feedback-admin-write` |
| `PUT /note` | `throttle:feedback-admin-write` |
| `PUT /adoption` | `throttle:feedback-admin-write` |
| `POST /reward` | `throttle:feedback-admin-reward` |
| **`DELETE /`** | **无** |

**风险**：若管理员账号被盗，攻击者可无限制批量删除反馈。

**修复建议**：

```php
Route::delete('/feedbacks/{feedback}', [FeedbackController::class, 'destroy'])
    ->name('feedbacks.destroy')
    ->middleware(['permission:feedbacks.delete', 'throttle:feedback-admin-write']);
```

---

#### 5. 审计日志记录回复原文 — 敏感信息泄露

**位置**：`app/Http/Controllers/Admin/FeedbackController.php` 第 163-167 行

**问题**：

```php
'context' => [
    'reply_id' => $reply->id,
    'notify_user' => !empty($data['notify_user']),
    'content' => $reply->content,  // ← 完整回复原文
],
```

将用户/管理员的回复原文写入审计日志 `context` JSON 字段。如果回复中包含手机号、身份证、密码等敏感信息，审计日志就成为了敏感数据汇聚点。

**风险**：
- 审计日志通常权限管控较宽松，可能导致敏感信息扩散
- 审计日志无法像回复一样被用户删除（GDPR 等）
- 增加数据库存储压力

**修复建议**：只记录 `reply_id`，需要查看原文时关联查询。

```php
'context' => [
    'reply_id' => $reply->id,
    'notify_user' => !empty($data['notify_user']),
],
```

---

#### 6. 管理端视图展示用户邮箱未脱敏

**位置**：`resources/views/admin/feedbacks/show.blade.php` 第 136-137 行

**问题**：

```blade
<tr><td class="text-secondary">邮箱</td><td>{{ $feedback->user->email }}</td></tr>
<tr><td class="text-secondary">邮箱</td><td>{{ $feedback->visitor_email }}</td></tr>
```

用户邮箱完整展示给所有有 `feedbacks.view` 权限的管理员，增加了内部信息泄露风险。

**修复建议**：

```php
// 在 User 模型或 helpers 中添加脱敏方法
public function maskedEmail(): string
{
    $parts = explode('@', $this->email);
    return Str::mask($parts[0] ?? '', '*', 1) . '@' . ($parts[1] ?? '');
}
```

---

#### 7. 状态流转无校验 — 可随意跳转

**位置**：`app/Http/Controllers/Admin/FeedbackController.php` 第 84-110 行

**问题**：

```php
public function updateStatus(Request $request, Feedback $feedback): JsonResponse
{
    $data = $request->validate([
        'status' => 'required|in:pending,processing,replied,closed',
        'priority' => 'nullable|in:low,medium,high,urgent',
    ]);

    $feedback->update($data);  // 无状态机校验，任意转换
    // ...
}
```

管理员可以将 `closed` 直接改回 `pending`，或从 `pending` 跳到 `replied` 而不经过 `processing`。虽然管理端拥有较高权限，但缺少流转校验会导致：
- 数据不一致（如 `replied` 状态但无任何回复记录）
- 审计日志混乱
- 误操作难以发现

**修复建议**：定义合法的状态流转规则：

```php
private const STATUS_TRANSITIONS = [
    'pending'    => ['processing', 'closed'],
    'processing' => ['replied', 'closed'],
    'replied'    => ['processing', 'closed'],
    'closed'     => [],  // closed 不可回退（或仅允许特定角色回退）
];

private function validateStatusTransition(Feedback $feedback, string $newStatus): void
{
    $allowed = self::STATUS_TRANSITIONS[$feedback->status] ?? [];
    if (!in_array($newStatus, $allowed)) {
        throw new RuntimeException("不允许从 {$feedback->getStatusLabel()} 变更为该状态");
    }
}
```

---

### 中等 (Medium)

#### 8. 用户端列表/详情路由缺少限流

**位置**：`routes/web_public.php` 第 69-70 行

**问题**：

```php
Route::get('/feedback', ...)->middleware(['auth', 'maintenance', 'email.verification.required', 'mobile']);
Route::get('/feedback/{feedback}', ...)->middleware(['auth', 'maintenance', 'email.verification.required', 'mobile']);
```

读操作虽然限流要求较低，但恶意用户可通过遍历 `{feedback}` ID 结合 Timing 攻击探测其他用户反馈数量（即便 Policy 会拒绝访问，403/200 响应时间差可泄露信息）。

**修复建议**：添加轻量限流，如 `throttle:60,1`（每分钟60次）。

---

#### 9. 管理端删除无二次确认后端校验

**位置**：`app/Http/Controllers/Admin/FeedbackController.php` 第 331-349 行

**问题**：删除操作仅依赖前端 `confirm()` 弹窗，后端无独立确认机制。CSRF 攻击可绕过前端确认直接触发删除。

**修复建议**：采用两步删除模式，或要求请求中携带确认参数：

```php
public function destroy(Request $request, Feedback $feedback): JsonResponse
{
    $request->validate([
        'confirm' => 'required|accepted',
    ]);
    // ...
}
```

---

#### 10. 邮件发送异常直接暴露错误信息给前端

**位置**：`app/Http/Controllers/Admin/FeedbackController.php` 第 152-153 行

**问题**：

```php
'message' => '邮件发送失败：' . $e->getMessage(),
```

`$e->getMessage()` 可能包含 SMTP 配置、服务器路径、认证失败详情等内部信息。

**修复建议**：

```php
Log::error('反馈回复邮件发送失败', [...]);
return response()->json([
    'success' => false,
    'message' => '邮件发送失败，请稍后重试或联系技术支持',
], 500);
```

---

## 二、代码质量问题

### 严重 Bug

#### 1. 调用不存在的 `$this->fail()` 方法

**位置**：`app/Http/Controllers/User/FeedbackController.php` 第 78 行

**问题**：

```php
if ($feedback->status === Feedback::STATUS_CLOSED) {
    return $this->fail('该反馈已关闭，无法追加回复', 422);
}
```

Laravel 的 `Controller` 基类没有 `fail()` 方法。**此行代码在运行时会抛出 `BadMethodCallException`**，导致用户追加回复时遇到 500 错误。

**修复**：

```php
return response()->json(['success' => false, 'message' => '该反馈已关闭，无法追加回复'], 422);
```

---

#### 2. 死代码 — 第二次检查 `STATUS_CLOSED` 永远不会执行

**位置**：`app/Http/Controllers/User/FeedbackController.php` 第 91-94 行

**问题**：

```php
// 第 77-78 行：已对 STATUS_CLOSED 做 return 处理
if ($feedback->status === Feedback::STATUS_CLOSED) {
    return $this->fail('该反馈已关闭，无法追加回复', 422);
}

// 第 91-94 行：条件永远为 false，属于死代码
if ($feedback->status === Feedback::STATUS_CLOSED) {
    $feedback->update(['status' => Feedback::STATUS_PROCESSING]);
}
```

第 77 行已对 `STATUS_CLOSED` 返回错误响应，执行到第 91 行时 `$feedback->status` 绝不可能是 `STATUS_CLOSED`。

**推断意图**：开发者可能希望在管理员回复后自动重新打开已关闭的反馈（但此时应检查的是管理端的 reply，而非用户端）。用户端的追加回复对已关闭反馈的正确行为应是直接拒绝。

**修复**：删除第 91-94 行死代码。如果确实需要"用户追加回复自动重开反馈"的功能，应修改为：

```php
// 允许用户对已关闭反馈追加回复，并自动重开
public function reply(Request $request, Feedback $feedback): JsonResponse
{
    $this->authorize('reply', $feedback);

    $data = $request->validate([
        'content' => 'required|string|max:5000',
    ]);

    $feedback->replies()->create([
        'user_id' => $request->user()->id,
        'is_admin' => false,
        'content' => $data['content'],
    ]);

    if ($feedback->status === Feedback::STATUS_CLOSED) {
        $feedback->update(['status' => Feedback::STATUS_PROCESSING]);
    }

    return response()->json(['success' => true, 'message' => '回复已发送']);
}
```

---

### 架构设计问题

#### 3. 验证逻辑内联 — 缺少 FormRequest

**位置**：`app/Http/Controllers/User/FeedbackController.php`、`app/Http/Controllers/Admin/FeedbackController.php`

**问题**：所有验证规则都以内联 `$request->validate()` 形式写在控制器中，导致：

- 验证规则无法复用（如用户端和管理端的回复验证有重复）
- 控制器方法过长
- 无法在测试中独立测试验证逻辑
- 无法通过 `php artisan route:cache` + FormRequest 自动注入文档化

**建议创建**：

| FormRequest 类 | 用途 |
|------|------|
| `StoreFeedbackRequest` | 用户提交反馈 |
| `ReplyFeedbackRequest` | 用户追加回复 |
| `AdminReplyFeedbackRequest` | 管理员回复反馈 |
| `UpdateFeedbackStatusRequest` | 更新状态/优先级 |
| `UpdateFeedbackAdoptionRequest` | 更新采纳结果 |
| `GrantFeedbackRewardRequest` | 发放奖励 |

---

#### 4. 控制器过于臃肿

**位置**：`app/Http/Controllers/Admin/FeedbackController.php`

**问题**：该控制器包含 8 个方法，其中 `reply()` 方法约 70 行，混合了验证、业务逻辑、邮件发送、审计记录。`reward()` 方法约 60 行，混合了验证、服务调用、审计记录。

**建议**：将核心业务逻辑提取到 Action 类或 Service 类：

```
App\Actions\Feedback\ReplyToFeedback     — 回复逻辑（含邮件、状态更新）
App\Actions\Feedback\UpdateFeedbackStatus — 状态更新逻辑（含流转校验）
App\Actions\Feedback\GrantFeedbackReward  — 已有 FeedbackRewardService，可保留
```

---

#### 5. 审计动作字符串未常量化

**位置**：`app/Http/Controllers/Admin/FeedbackController.php` + `app/Models/FeedbackAuditLog.php`

**问题**：控制器中使用裸字符串 `'status_updated'`、`'reply_sent'` 等，`FeedbackAuditLog` 模型的 `getActionLabelAttribute()` 也用裸字符串 match。两处需保持同步但无编译时保障。

**建议**：在 `FeedbackAuditLog` 模型中定义常量：

```php
class FeedbackAuditLog extends Model
{
    public const ACTION_STATUS_UPDATED = 'status_updated';
    public const ACTION_REPLY_SENT = 'reply_sent';
    public const ACTION_NOTE_UPDATED = 'note_updated';
    public const ACTION_ADOPTION_UPDATED = 'adoption_updated';
    public const ACTION_REWARD_GRANTED = 'reward_granted';
    public const ACTION_FEEDBACK_DELETED = 'feedback_deleted';

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_STATUS_UPDATED => '更新状态/优先级',
            self::ACTION_REPLY_SENT => '发送回复',
            // ...
        };
    }
}
```

---

#### 6. 模型标签方法使用魔法字符串而非常量

**位置**：`app/Models/Feedback.php` 第 112-151 行

**问题**：

```php
public function getCategoryLabel(): string
{
    return match ($this->category) {
        'bug' => 'Bug 报告',        // 应使用 self::CATEGORY_BUG
        'suggestion' => '功能建议',  // 应使用 self::CATEGORY_SUGGESTION
        'ux' => '体验问题',         // 应使用 self::CATEGORY_UX
        default => '其他',
    };
}
```

已定义了 `CATEGORY_BUG = 'bug'` 等常量，但 match 表达式中仍使用裸字符串，存在拼写错误风险，且无法享受 IDE 自动补全。

**建议**：所有 match 分支引用已定义的常量。

---

#### 7. 前端 JS 路由获取方式脆弱

**位置**：`public/js/pages/admin-feedbacks-show.js` 第 22、35、45 行等

**问题**：

```javascript
document.querySelector('[data-route-admin-feedbacks-update-status-0]')
    ?.getAttribute('data-route-admin-feedbacks-update-status-0')
```

使用带编号后缀（`-0`、`-1`、`-2`）的 data 属性名作为选择器，非常脆弱。这些编号可能由 Blade 指令自动生成，任何模板变更都可能导致 JS 找不到元素。

**建议**：使用语义化 data 属性名：

```html
<div data-url-update-status="{{ route(...) }}"
     data-url-update-note="{{ route(...) }}"
     data-url-reply="{{ route(...) }}">
```

```javascript
const el = document.querySelector('[data-url-update-status]');
const url = el?.dataset.urlUpdateStatus;
```

---

### 一般性问题

#### 8. 缺少状态/优先级/分类常量映射数组

**位置**：`app/Models/Feedback.php`

**问题**：合法值散落在验证规则、迁移、模型常量三处，没有统一的映射数组供验证规则引用。

**建议**：

```php
public const STATUSES = [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_REPLIED, self::STATUS_CLOSED];
public const PRIORITIES = [self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH, self::PRIORITY_URGENT];
public const CATEGORIES = [self::CATEGORY_BUG, self::CATEGORY_SUGGESTION, self::CATEGORY_UX, self::CATEGORY_OTHER];
```

验证规则中可引用：`'status' => 'required|in:' . implode(',', Feedback::STATUSES)`

---

#### 9. 管理端 `index` 筛选参数未校验枚举值

**位置**：`app/Http/Controllers/Admin/FeedbackController.php` 第 32-42 行

**问题**：

```php
if ($status = $request->input('status')) {
    $query->where('status', $status);
}
```

`status`、`category`、`priority` 筛选值未做 `in:` 校验。虽然 Eloquent 参数绑定可防 SQL 注入，但无效值会导致空结果且无提示，增加排查成本。

---

#### 10. 前端 XSS 转义不完整

**位置**：`public/js/pages/user-feedbacks-show.js` 第 31 行 + `public/js/pages/admin-feedbacks-show.js` 第 134 行

**问题**：

```javascript
content.replace(/</g, '&lt;')
```

仅替换了 `<`，未转义 `>`、`"`、`&`。虽然 `<` 是 XSS 的关键字符，但完整转义更安全。

**建议**：使用 DOM API 代替字符串拼接：

```javascript
// 安全方式
const contentDiv = document.createElement('div');
contentDiv.textContent = content;
contentDiv.style.whiteSpace = 'pre-wrap';
```

---

#### 11. `FeedbackRewardService` 缺少接口抽象

**位置**：`app/Services/Feedback/FeedbackRewardService.php`

**问题**：`FeedbackRewardService` 是 `final class` 但没有对应接口，不利于测试 mock 和未来替换实现。

---

#### 12. `FeedbackReward` 手动维护 `granted_at` 字段

**位置**：`app/Models/FeedbackReward.php`

**问题**：`granted_at` 手动设置而非使用 `created_at`，功能上没问题但增加了维护理解成本。两个时间戳容易造成混淆。

---

## 三、已有优秀设计

以下设计值得肯定，可作为项目其他模块的参考：

| # | 设计 | 说明 |
|---|------|------|
| 1 | **三层限流机制** | 用户提交(3/min)、管理端写操作(20/min)、奖励发放(6/min)，粒度合理 |
| 2 | **7 细粒度权限点** | `view/edit/manage/reply/adopt/reward/delete`，权限最小化原则 |
| 3 | **完整审计追踪** | 所有管理端关键操作均有审计日志，含 IP/UA/变更前后值 |
| 4 | **奖励发放幂等保护** | 数据库唯一约束 + 业务检查双重防重复 |
| 5 | **DB 事务保障** | 奖励发放全链路在 `DB::transaction` 中执行 |
| 6 | **软删除** | 反馈和回复均使用 `SoftDeletes`，防止误删丢失数据 |
| 7 | **LIKE 转义** | 管理端搜索对 `%` 和 `_` 做转义，防 LIKE 通配符注入 |
| 8 | **Policy 授权** | 用户端通过 Policy 限制只能操作自己的反馈 |
| 9 | **XSS 基础防护** | Blade 模板统一使用 `{{ }}` 转义输出 |
| 10 | **前端防重复提交** | 奖励发放和回复按钮有 disabled 状态，防止双击重复提交 |

---

## 四、改进优先级排序

| 优先级 | 编号 | 问题 | 类型 | 预估工作量 |
|:---:|:---:|------|:---:|:---:|
| **P0** | Bug#1 | `$this->fail()` 方法不存在（运行时崩溃） | Bug | 5min |
| **P0** | Bug#2 | 死代码（closed 状态永远无法执行到 reopen） | Bug | 5min |
| **P0** | Sec#1 | `page_url` 开放重定向 | 安全 | 30min |
| **P1** | Sec#2 | `$fillable` 包含管理员字段（批量赋值） | 安全 | 1h |
| **P1** | Sec#3 | metadata `screen`/`language` 无校验 | 安全 | 30min |
| **P1** | Sec#4 | 删除路由缺少 throttle | 安全 | 5min |
| **P1** | Sec#7 | 状态流转无校验 | 质量 | 2h |
| **P2** | Sec#5 | 审计日志记录回复原文 | 安全 | 30min |
| **P2** | Sec#6 | 邮箱未脱敏 | 安全 | 30min |
| **P2** | Sec#10 | 邮件异常暴露内部信息 | 安全 | 15min |
| **P2** | Arch#3 | 拆分 FormRequest | 质量 | 2h |
| **P2** | Arch#5 | 审计动作常量化 | 质量 | 1h |
| **P2** | Sec#8 | 用户端读路由限流 | 安全 | 5min |
| **P3** | Arch#4 | 控制器逻辑拆分为 Action 类 | 质量 | 3h |
| **P3** | Arch#7 | 前端 JS 路由选择器优化 | 质量 | 1h |
| **P3** | Arch#6 | 模型标签方法引用常量 | 质量 | 30min |
| **P3** | Gen#10 | 前端 XSS 转义完善 | 安全 | 30min |
| **P3** | Sec#9 | 管理端删除二次确认 | 安全 | 30min |
| **P3** | Gen#8 | 状态常量映射数组 | 质量 | 15min |
| **P3** | Gen#9 | 管理端筛选参数校验 | 质量 | 15min |

---

## 五、建议修复方案

### P0 修复（立即执行）

#### 1. 修复 `$this->fail()` 方法

```php
// app/Http/Controllers/User/FeedbackController.php 第 78 行
// 修改前：
return $this->fail('该反馈已关闭，无法追加回复', 422);
// 修改后：
return response()->json(['success' => false, 'message' => '该反馈已关闭，无法追加回复'], 422);
```

#### 2. 删除死代码或修改业务逻辑

根据业务意图选择：

**方案 A — 拒绝已关闭反馈的追加回复**（推荐）：

```php
public function reply(Request $request, Feedback $feedback): JsonResponse
{
    $this->authorize('reply', $feedback);

    if ($feedback->status === Feedback::STATUS_CLOSED) {
        return response()->json(['success' => false, 'message' => '该反馈已关闭，无法追加回复'], 422);
    }

    $data = $request->validate([
        'content' => 'required|string|max:5000',
    ]);

    $feedback->replies()->create([
        'user_id' => $request->user()->id,
        'is_admin' => false,
        'content' => $data['content'],
    ]);

    return response()->json(['success' => true, 'message' => '回复已发送']);
}
```

**方案 B — 允许追加回复并自动重开**：

```php
public function reply(Request $request, Feedback $feedback): JsonResponse
{
    $this->authorize('reply', $feedback);

    $data = $request->validate([
        'content' => 'required|string|max:5000',
    ]);

    $feedback->replies()->create([
        'user_id' => $request->user()->id,
        'is_admin' => false,
        'content' => $data['content'],
    ]);

    // 已关闭的反馈追加回复后自动重新打开
    if ($feedback->status === Feedback::STATUS_CLOSED) {
        $feedback->update(['status' => Feedback::STATUS_PROCESSING]);
    }

    return response()->json(['success' => true, 'message' => '回复已发送']);
}
```

#### 3. 修复 `page_url` 开放重定向

在 `store` 方法的验证规则中增加自定义规则：

```php
'page_url' => ['nullable', 'string', 'max:500', function ($attribute, $value, $fail) {
    if (empty($value)) return;
    
    // 允许相对路径
    if (str_starts_with($value, '/') && !str_starts_with($value, '//')) return;
    
    // 绝对 URL 必须为当前域名
    $host = parse_url($value, PHP_URL_HOST);
    $appHost = parse_url(config('app.url'), PHP_URL_HOST);
    if ($host && $host !== $appHost) {
        $fail('页面地址必须为当前站点的链接');
    }
}],
```

同时，在视图层增加安全属性：

```blade
<a href="{{ $feedback->page_url }}" target="_blank" rel="noopener noreferrer nofollow" class="text-reset">
```

---

### P1 修复（本周内完成）

#### 4. 修复 `$fillable` 批量赋值

```php
// app/Models/Feedback.php
protected $fillable = [
    'user_id', 'visitor_email', 'page_url', 'page_name',
    'category', 'title', 'content', 'metadata',
];

// 管理端字段白名单
public const ADMIN_FILLABLE = [
    'status', 'priority', 'admin_note',
    'adoption_status', 'adopted_at', 'adopted_by', 'adoption_note',
];
```

管理端控制器中使用：

```php
$feedback->forceFill(collect($data)->only(Feedback::ADMIN_FILLABLE)->toArray())->save();
```

#### 5. 校验 metadata

```php
$data['metadata'] = [
    'user_agent' => Str::limit($request->header('User-Agent'), 500, ''),
    'screen' => preg_match('/^\d{1,5}x\d{1,5}$/', $request->input('screen', ''))
        ? $request->input('screen') : null,
    'language' => preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $request->input('language', ''))
        ? $request->input('language') : null,
];
```

#### 6. 删除路由添加限流

```php
Route::delete('/feedbacks/{feedback}', [FeedbackController::class, 'destroy'])
    ->name('feedbacks.destroy')
    ->middleware(['permission:feedbacks.delete', 'throttle:feedback-admin-write']);
```

#### 7. 状态流转校验

```php
// app/Models/Feedback.php
public const STATUS_TRANSITIONS = [
    'pending'    => ['processing', 'closed'],
    'processing' => ['replied', 'pending', 'closed'],
    'replied'    => ['processing', 'closed'],
    'closed'     => [],
];

public function canTransitionTo(string $newStatus): bool
{
    return in_array($newStatus, self::STATUS_TRANSITIONS[$this->status] ?? []);
}

// app/Http/Controllers/Admin/FeedbackController.php
public function updateStatus(Request $request, Feedback $feedback): JsonResponse
{
    $data = $request->validate([
        'status' => 'required|in:pending,processing,replied,closed',
        'priority' => 'nullable|in:low,medium,high,urgent',
    ]);

    if (!$feedback->canTransitionTo($data['status'])) {
        return response()->json([
            'success' => false,
            'message' => "不允许从「{$feedback->getStatusLabel()}」变更为该状态",
        ], 422);
    }
    // ...
}
```

---

> **总结**：反馈系统整体架构设计合理，安全基础较好（限流、权限、审计、幂等均已覆盖）。主要问题集中在两个运行时 Bug（P0）和批量赋值/开放重定向等安全边界问题上。建议按优先级逐步修复，优先处理 P0 级别的运行时崩溃和开放重定向漏洞。
