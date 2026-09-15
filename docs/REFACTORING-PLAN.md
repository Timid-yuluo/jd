# 胖控制器抽离 & CheckQuota 重构计划

> **制定日期**: 2026-05-19  
> **完成日期**: 2026-05-19  
> **涉及文件**: 7 个 Controller + 1 个 Middleware + 1 个 Service  
> **总代码行数**: ~3,350 行  
> **预计总工时**: ~56h  
> **状态**: ✅ 全部完成

---

## 目录

1. [重构总览](#1-重构总览)
2. [Phase 1: CheckQuota 中间件重构](#2-phase-1-checkquota-中间间件重构)
3. [Phase 2: AiConfigController 抽离](#3-phase-2-aiconfigcontroller-抽离)
4. [Phase 3: SiteSettingController 抽离](#4-phase-3-sitesettingcontroller-抽离)
5. [Phase 4: SystemOpsController 抽离](#5-phase-4-systemopscontroller-抽离)
6. [Phase 5: FileManagerController 抽离](#6-phase-5-filemanagercontroller-抽离)
7. [Phase 6: DataExportController 抽离](#7-phase-6-dataexportcontroller-抽离)
8. [Phase 7: ResumeStreamController 抽离](#8-phase-7-resumestreamcontroller-抽离)
9. [Phase 8: ResumeOptimizeSessionController 抽离](#9-phase-8-resumeoptimizesessioncontroller-抽离)
10. [执行顺序与依赖关系](#10-执行顺序与依赖关系)
11. [风险控制](#11-风险控制)

---

## 1. 重构总览

### 当前问题汇总

| 文件 | 重构前行数 | 重构后行数 | 核心问题 | 状态 |
|------|-----------|-----------|----------|------|
| `CheckQuota.php` | 232 | ~160 | 中间件承担 6 种职责：配额检查 + 次卡选择 + 确认流程 + 响应生成 + Session flash + 扣减 | ✅ 已重构 |
| `AiConfigController.php` | 579 | ~120 | `benchmark()` 含完整基准测试逻辑；`logConfigChange()` 含复杂变更对比；10+ private 方法 | ✅ 已重构 |
| `SiteSettingController.php` | 344 | ~170 | `update()` 176 行，含 60+ 字段验证、文件上传、snippet 清洗、审计写入 | ✅ 已重构 |
| `SystemOpsController.php` | 375 | ~160 | 含原始 SQL 查询、系统信息采集、日志解析，全部内联 | ✅ 已重构 |
| `FileManagerController.php` | 404 | ~80 | 文件上传/删除/重命名/SVG 清洗/路径清理等逻辑直接在控制器中 | ✅ 已重构 |
| `DataExportController.php` | 242 | ~80 | 5 个 export 方法含重复的查询+映射逻辑 | ✅ 已重构 |
| `ResumeStreamController.php` | 484 | ~70 | `optimizeStream()` 280+ 行，包含验证+权限+AI调用+重试+结果解析 | ✅ 已重构 |
| `ResumeOptimizeSessionController.php` | 398 | 398 | `create()` 116 行，含验证+幂等+会话创建+配额标记 | ⏭ 跳过（已足够精简） |

### 重构目标

```
重构前：Controller / Middleware 包揽一切
Request → Controller(验证+业务逻辑+响应) → Model

重构后：Controller 仅做分发，业务逻辑下沉到 Service
Request → FormRequest(验证) → Controller(分发) → Service(业务逻辑) → Model
```

### 将创建的新文件

| 文件 | 类型 | 来源 | 状态 |
|------|------|------|------|
| `App\DTO\QuotaResolution` | DTO | 从 CheckQuota 中间件提取 | ✅ 已创建 |
| `App\Services\Admin\AiBenchmarkService` | Service | 从 AiConfigController::benchmark() 提取 | ✅ 已创建 |
| `App\Services\Admin\AiConfigUpdateService` | Service | 从 AiConfigController 更新方法提取 | ✅ 已创建 |
| `App\Services\Admin\SystemSettingUpdateService` | Service | 从 SiteSettingController::update() 提取 | ✅ 已创建 |
| `App\Services\Admin\SystemOpsService` | Service | 从 SystemOpsController 提取 | ✅ 已创建 |
| `App\Services\Admin\FileManagerService` | Service | 从 FileManagerController 提取 | ✅ 已创建 |
| `App\Services\Admin\DataExportService` | Service | 从 DataExportController 提取 | ✅ 已创建 |
| `App\Services\Resume\ResumeOptimizeStreamService` | Service | 从 ResumeStreamController::optimizeStream() 提取 | ✅ 已创建 |

> **注意**: 原计划中的 FormRequest 文件（`AiConfigGlobalUpdateRequest`、`AiConfigProviderUpdateRequest`、`SiteSettingUpdateRequest`、`ResumeOptimizeStreamRequest`、`ResumeOptimizeSessionCreateRequest`）因 Laravel FormRequest 在私有方法中无法自动解析的限制，改为在控制器中使用 inline `$request->validate()`。Phase 8 评估后跳过，控制器已足够精简。

---

## 2. Phase 1: CheckQuota 中间件重构

**优先级**: 🔴 最高（中间件是所有 AI 功能的必经之路）  
**预计工时**: 8h  
**前置依赖**: 无

### 2.1 当前职责分析

```
CheckQuota::handle() 当前承担的 6 种职责：
├── ① 配额检查（月配额是否充足）         → 应留在 QuotaService
├── ② 次卡手动确认检查（use_credit 参数） → 应移入 QuotaService
├── ③ 次卡自动选择策略                    → 已在 QuotaService，但中间件还含 UI 决策
├── ④ 用户确认流程判断                    → 应移入 QuotaService
├── ⑤ 响应格式生成（JSON 403/429 + Redirect + Session flash） → 应移入中间件，但简化
└── ⑥ 响应后扣减（terminate）            → 应委托 QuotaService
```

### 2.2 新建 DTO: `App\DTO\QuotaResolution`

```php
// app/DTO/QuotaResolution.php
namespace App\DTO;

/**
 * 配额检查结果 — 中间件与 Service 之间的唯一数据载体
 */
final readonly class QuotaResolution
{
    public function __construct(
        public bool $canProceed,
        public string $quotaKey,
        public string $source,              // 'quota' | 'credit' | ''
        public ?int $creditId = null,
        public ?string $creditName = null,
        public bool $autoSelected = false,
        // 拒绝时的上下文
        public ?string $denyReason = null,  // 'QUOTA_EXCEEDED' | 'QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE' | 'INVALID_CREDIT' | 'CREDIT_KEY_MISMATCH'
        public ?int $monthlyLimit = null,
        public ?int $monthlyUsed = null,
        public ?array $credits = null,      // 可用次卡列表（确认流程用）
    ) {}

    public function needsCreditConfirmation(): bool
    {
        return $this->denyReason === 'QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE';
    }

    public function isQuotaExceeded(): bool
    {
        return $this->denyReason === 'QUOTA_EXCEEDED';
    }
}
```

### 2.3 扩展 `QuotaService`：新增 `resolve()` 方法

```php
// app/Services/Membership/QuotaService.php — 新增方法

/**
 * 统一配额决策入口 — 中间件唯一调用点
 *
 * 决策链：
 * 1. 检查月配额 → 充足则放行
 * 2. 用户带 credit_id → 验证次卡有效性
 * 3. 自动选择次卡 → 无需确认的路由直接使用
 * 4. 需要确认的路由 → 返回 QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE
 * 5. 无次卡 → 返回 QUOTA_EXCEEDED
 */
public function resolve(User $user, string $quotaKey, array $input, string $routeName): QuotaResolution
{
    // Step 1: 用户指定次卡
    if (!empty($input['use_credit']) && !empty($input['credit_id'])) {
        return $this->resolveWithCredit($user, $quotaKey, (int) $input['credit_id']);
    }

    // Step 2: 月配额检查
    $result = $this->check($user, $quotaKey);
    if ($result['allowed']) {
        return new QuotaResolution(
            canProceed: true,
            quotaKey: $quotaKey,
            source: 'quota',
        );
    }

    // Step 3: 自动选择次卡（无需确认的路由）
    if (! $this->routeRequiresConfirmation($routeName)) {
        $autoResult = $this->checkWithAutoCredit($user, $quotaKey);
        if ($autoResult['allowed'] && $autoResult['credit_id']) {
            return new QuotaResolution(
                canProceed: true,
                quotaKey: $quotaKey,
                source: 'credit',
                creditId: $autoResult['credit_id'],
                creditName: $autoResult['credit_name'],
                autoSelected: true,
            );
        }
    }

    // Step 4: 需要确认的路由 + 有次卡 → 返回确认提示
    if ($result['reason'] === 'QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE') {
        return new QuotaResolution(
            canProceed: false,
            quotaKey: $quotaKey,
            source: '',
            denyReason: 'QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE',
            monthlyLimit: $result['monthly_limit'],
            monthlyUsed: $result['monthly_used'],
            credits: $result['credits'],
        );
    }

    // Step 5: 无次卡
    return new QuotaResolution(
        canProceed: false,
        quotaKey: $quotaKey,
        source: '',
        denyReason: 'QUOTA_EXCEEDED',
        monthlyLimit: $result['monthly_limit'],
        monthlyUsed: $result['monthly_used'],
    );
}

private function resolveWithCredit(User $user, string $quotaKey, int $creditId): QuotaResolution
{
    $result = $this->checkWithCredit($user, $quotaKey, $creditId);

    if ($result['allowed']) {
        return new QuotaResolution(
            canProceed: true,
            quotaKey: $quotaKey,
            source: 'credit',
            creditId: $result['credit_id'],
        );
    }

    return new QuotaResolution(
        canProceed: false,
        quotaKey: $quotaKey,
        source: '',
        denyReason: $result['reason'],
    );
}

private function routeRequiresConfirmation(string $routeName): bool
{
    return in_array($routeName, config('quota.credit_confirmation_routes', []), true);
}
```

### 2.4 扩展 `QuotaService`：新增 `deduct()` 方法

```php
/**
 * 响应成功后扣减配额 — terminate 委托调用
 */
public function deduct(QuotaResolution $resolution, int $userId, int $statusCode): void
{
    if (! $resolution->canProceed || $statusCode >= 400) {
        return;
    }

    if ($resolution->source === 'credit' && $resolution->creditId !== null) {
        $this->decrementCredit($resolution->creditId, $userId);
    } elseif ($resolution->source === 'quota') {
        $this->incrementQuotaUsage($userId, $resolution->quotaKey);
    }
}
```

### 2.5 瘦身后的 `CheckQuota` 中间件

```php
// app/Http/Middleware/CheckQuota.php — 重构后约 60 行
final class CheckQuota
{
    public function __construct(
        private readonly QuotaService $quotaService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $routeName = $request->route()?->getName()) {
            return $next($request);
        }

        $quotaKey = QuotaService::routeToQuotaKey($routeName);
        if (! $quotaKey) {
            return $next($request);
        }

        $resolution = $this->quotaService->resolve(
            $user, $quotaKey, $request->all(), $routeName
        );

        // 存入 request attributes，terminate 时使用
        $request->attributes->set('quota_resolution', $resolution);

        if (! $resolution->canProceed) {
            return $this->denyResponse($resolution, $request);
        }

        $response = $next($request);

        // 自动次卡提示
        if ($resolution->autoSelected
            && $response->getStatusCode() < 400
            && $request->hasSession()) {
            $quotaLabel = UserCredit::quotaLabel($resolution->quotaKey);
            $request->session()->flash(
                'info',
                "{$quotaLabel}本月免费次数已用完，已自动使用「{$resolution->creditName}」扣除 1 次。"
            );
        }

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $resolution = $request->attributes->get('quota_resolution');
        $user = $request->user();

        if ($resolution instanceof QuotaResolution && $user) {
            $this->quotaService->deduct($resolution, $user->id, $response->getStatusCode());
        }
    }

    private function denyResponse(QuotaResolution $r, Request $request): JsonResponse|RedirectResponse
    {
        $quotaLabel = UserCredit::quotaLabel($r->quotaKey);

        if ($r->needsCreditConfirmation()) {
            return $this->shouldReturnJson($request)
                ? response()->json([
                    'code' => 'QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE',
                    'message' => '本月免费次数已用完',
                    'data' => [
                        'quota_key' => $r->quotaKey,
                        'monthly_limit' => $r->monthlyLimit,
                        'monthly_used' => $r->monthlyUsed,
                        'credits' => $r->credits,
                        'upgrade_url' => route('user.membership.pricing'),
                        'credit_packs_url' => route('user.membership.credit-packs'),
                    ],
                ], 403)
                : redirect()->back()->withInput()->with(
                    'warning',
                    "{$quotaLabel} 本月免费次数已用完。可购买次卡继续使用，或升级套餐获取更多次数。"
                );
        }

        if ($r->isQuotaExceeded()) {
            return $this->shouldReturnJson($request)
                ? response()->json([
                    'code' => 'QUOTA_EXCEEDED',
                    'message' => '本月次数已用完，请升级套餐或购买次卡',
                    'data' => [
                        'quota_key' => $r->quotaKey,
                        'monthly_limit' => $r->monthlyLimit,
                        'monthly_used' => $r->monthlyUsed,
                        'upgrade_url' => route('user.membership.pricing'),
                        'credit_packs_url' => route('user.membership.credit-packs'),
                    ],
                ], 429)
                : redirect()->back()->withInput()->with(
                    'warning',
                    "{$quotaLabel} 本月次数已用完且无可用次卡，请升级套餐或购买次卡后再试。"
                );
        }

        // 其他拒绝原因（INVALID_CREDIT 等）
        $message = match ($r->denyReason) {
            'INVALID_CREDIT' => '次卡无效或已用完',
            'CREDIT_KEY_MISMATCH' => '次卡类型与当前操作不匹配',
            default => '无法使用次卡',
        };

        return response()->json(['code' => $r->denyReason, 'message' => $message], 403);
    }

    private function shouldReturnJson(Request $request): bool
    {
        if ($request->expectsJson() || $request->wantsJson() || $request->acceptsJson()) {
            return true;
        }
        $accept = strtolower((string) $request->header('Accept', ''));
        return str_contains($accept, 'text/event-stream')
            || $request->ajax()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }
}
```

### 2.6 迁移步骤

| 步骤 | 操作 | 风险 |
|------|------|------|
| 1 | 创建 `App\DTO\QuotaResolution` | 无 |
| 2 | 在 `QuotaService` 中新增 `resolve()` / `deduct()` / `resolveWithCredit()` / `routeRequiresConfirmation()` | 低（新增方法，不改动现有方法） |
| 3 | 编写 `QuotaService::resolve()` 的单元测试 | 无 |
| 4 | 重写 `CheckQuota` 中间件使用新 API | 中 — 需回归测试所有 AI 功能路由 |
| 5 | 运行现有测试 + 手工测试 AI 功能流程 | — |

---

## 3. Phase 2: AiConfigController 抽离

**优先级**: 🟠 高  
**预计工时**: 10h  
**前置依赖**: 无

### 3.1 当前代码分布（579 行）

| 方法 | 行数 | 去向 |
|------|------|------|
| `index()` | 45 | 保留（展示逻辑） |
| `update()` | 9 | 保留（路由分发） |
| `updateGlobalSettings()` | 24 | → `AiConfigUpdateService::updateGlobal()` |
| `updateProviderSettings()` | 41 | → `AiConfigUpdateService::updateProvider()` |
| `currentConfigSnapshot()` | 14 | → `AiConfigService`（已存在，扩展） |
| `buildProviderSavePayload()` | 23 | → `AiConfigUpdateService` |
| `buildProviderVerifyPayload()` | 23 | → `AiConfigUpdateService` |
| `logConfigChange()` | 68 | → `AiConfigUpdateService::logChange()` |
| `nullableWhenBlank()` | 8 | → `AiConfigUpdateService` 或保留为 trait |
| `stringOrFallback()` | 5 | 同上 |
| `booleanSettingValue()` | 5 | 同上 |
| `normalizeBooleanChangeValue()` | 6 | 同上 |
| `providerLabel()` | 8 | → `AiConfigUpdateService` |
| `willHaveAnyEnabledProvider()` | 20 | → `AiConfigUpdateService` |
| `isEnabledProviderInSnapshot()` | 8 | → `AiConfigService` |
| `collectVolcanoEndpointOptions()` | 26 | → `AiConfigDashboardService`（已存在） |
| `test()` | 68 | → `AiBenchmarkService::testProvider()` |
| `history()` | 8 | 保留 |
| `benchmark()` | 72 | → `AiBenchmarkService::run()` |

### 3.2 新建 Service: `AiBenchmarkService`

```php
// app/Services/Admin/AiBenchmarkService.php
namespace App\Services\Admin;

use App\Infrastructure\AI\AiManager;
use App\Models\UsageLog;

final class AiBenchmarkService
{
    /**
     * 对所有已启用的 Provider 运行基准测试
     *
     * @return array{results: array, recommended: string|null, tested_at: string}
     */
    public function run(): array
    {
        // 从 AiConfigController::benchmark() 第 506-578 行迁移
    }

    /**
     * 测试单个 Provider 的指定功能
     */
    public function testProvider(string $provider, string $testType): array
    {
        // 从 AiConfigController::test() 第 425-492 行迁移
    }
}
```

### 3.3 新建 Service: `AiConfigUpdateService`

```php
// app/Services/Admin/AiConfigUpdateService.php
namespace App\Services\Admin;

use App\Infrastructure\AI\Providers\ZhipuAiProvider;
use App\Models\AiConfigHistory;

final class AiConfigUpdateService
{
    public function __construct(
        private readonly AiConfigService $configService,
        private readonly AiConfigVerifier $configVerifier,
    ) {}

    /**
     * 保存全局 AI 配置
     * @return array{success: bool, message: string, scope: string}
     */
    public function updateGlobal(array $validated): array
    {
        // 从 AiConfigController::updateGlobalSettings() 迁移
    }

    /**
     * 保存 Provider 配置
     * @return array{success: bool, message: string, scope: string, provider?: string}
     */
    public function updateProvider(array $validated): array
    {
        // 从 AiConfigController::updateProviderSettings() 迁移
    }

    /**
     * 记录配置变更历史
     */
    public function logChange(array $oldConfig, array $newConfig, array $verifyResults): void
    {
        // 从 AiConfigController::logConfigChange() 迁移
    }

    // buildProviderSavePayload, buildProviderVerifyPayload, willHaveAnyEnabledProvider
    // booleanSettingValue, nullableWhenBlank, stringOrFallback 等辅助方法一并迁移
}
```

### 3.4 新建 FormRequest

```php
// app/Http/Requests/Admin/AiConfigGlobalUpdateRequest.php
class AiConfigGlobalUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'save_scope' => 'required|string|in:global',
            'default_provider' => 'required|string|in:deepseek,volcano,zhipu',
            'resume_optimize_primary_channel' => 'required|string|in:session',
        ];
    }
}

// app/Http/Requests/Admin/AiConfigProviderUpdateRequest.php
class AiConfigProviderUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'save_scope' => 'required|string|in:provider',
            'provider' => 'required|string|in:deepseek,volcano,zhipu',
            'deepseek_api_key' => 'nullable|string',
            'deepseek_model' => 'nullable|string',
            'deepseek_enabled' => 'nullable|boolean',
            'volcano_api_key' => 'nullable|string',
            'volcano_model' => 'nullable|string',
            'volcano_enabled' => 'nullable|boolean',
            'zhipu_api_key' => 'nullable|string',
            'zhipu_model' => 'nullable|string',
            'zhipu_enabled' => 'nullable|boolean',
        ];
    }
}
```

### 3.5 瘦身后的 AiConfigController（~80 行）

```php
class AiConfigController extends Controller
{
    public function __construct(
        private readonly AiConfigDashboardService $dashboardService,
        private readonly AiConfigUpdateService $updateService,
        private readonly AiBenchmarkService $benchmarkService,
    ) {}

    public function index(): View { /* 保留，不变 */ }

    public function update(Request $request): RedirectResponse
    {
        return match ($request->input('save_scope')) {
            'global' => $this->updateGlobal($request),
            'provider' => $this->updateProvider($request),
            default => redirect()->route('admin.ai-config.index')->with('error', '未知的保存范围'),
        };
    }

    private function updateGlobal(AiConfigGlobalUpdateRequest $request): RedirectResponse
    {
        $result = $this->updateService->updateGlobal($request->validated());
        return redirect()->route('admin.ai-config.index')
            ->with('success', $result['message'])
            ->with('saved_scope', 'global');
    }

    private function updateProvider(AiConfigProviderUpdateRequest $request): RedirectResponse
    {
        $result = $this->updateService->updateProvider($request->validated());
        return redirect()->route('admin.ai-config.index')
            ->with('success', $result['message'])
            ->with('saved_scope', 'provider')
            ->with('saved_provider', $result['provider'] ?? null);
    }

    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate([...]); // 简单验证保留
        return response()->json($this->benchmarkService->testProvider(...));
    }

    public function benchmark(): JsonResponse
    {
        return response()->json($this->benchmarkService->run());
    }

    public function history(): View { /* 保留，不变 */ }
}
```

---

## 4. Phase 3: SiteSettingController 抽离

**优先级**: 🟠 高  
**预计工时**: 8h  
**前置依赖**: 无

### 4.1 当前代码分布（344 行）

| 方法 | 行数 | 去向 |
|------|------|------|
| `index()` | 13 | 保留 |
| `update()` | 176 | → 拆分为 FormRequest + `SystemSettingUpdateService` |
| `deleteManagedPublicFile()` | 19 | → `SystemSettingUpdateService` |
| `clearCache()` | 15 | 保留（简单操作） |
| `export()` | 18 | 保留 |
| `sanitizeHeadSnippets()` | 39 | → `SystemSettingUpdateService` |
| `sanitizeHeadSnippet()` | 23 | → 复用 `AppServiceProvider::sanitizeHeadSnippet()` |

### 4.2 新建 FormRequest: `SiteSettingUpdateRequest`

```php
// app/Http/Requests/Admin/SiteSettingUpdateRequest.php
class SiteSettingUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // 从 SiteSettingController::update() 第 44-123 行迁移全部 60+ 验证规则
        ];
    }

    /**
     * GitHub Client ID 自定义校验
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $clientId = trim((string) ($this->input('auth_github_client_id') ?? ''));
            if ($clientId !== '' && preg_match('/^\d+$/', $clientId) === 1) {
                $validator->errors()->add(
                    'auth_github_client_id',
                    'GitHub 这里需要填写 OAuth App 的 Client ID，不能填写纯数字的 App ID。'
                );
            }
        });
    }
}
```

### 4.3 新建 Service: `SystemSettingUpdateService`

```php
// app/Services/Admin/SystemSettingUpdateService.php
namespace App\Services\Admin;

use App\Models\SystemSettingAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SystemSettingUpdateService
{
    public function __construct(
        private readonly SystemSettingService $settingService,
    ) {}

    /**
     * 保存网站设置
     * @return array{changes: array, message: string}
     */
    public function update(array $validated, Request $request): array
    {
        $currentSettings = $this->settingService->all();

        // 布尔字段归一化
        $validated = $this->normalizeBooleans($validated, $request);

        // 文件处理
        $validated = $this->handleFileUploads($validated, $request, $currentSettings);

        // Snippet 清洗
        $validated = $this->sanitizeSnippets($validated);

        // 保存
        $changes = $this->settingService->saveMany($validated);

        // 审计日志
        $this->writeAuditLogs($changes, $request);

        return [
            'changes' => $changes,
            'message' => $changes !== [] ? '网站设置已保存。' : '未检测到配置变更。',
        ];
    }

    private function normalizeBooleans(array $validated, Request $request): array { /* ... */ }
    private function handleFileUploads(array $validated, Request $request, array $currentSettings): array { /* ... */ }
    private function sanitizeSnippets(array $validated): array { /* ... */ }
    private function writeAuditLogs(array $changes, Request $request): void { /* ... */ }
    private function deleteManagedPublicFile(string $url): void { /* 从 Controller 迁移 */ }
}
```

### 4.4 瘦身后的 SiteSettingController（~60 行）

```php
final class SiteSettingController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $systemSettingService,
        private readonly SystemSettingUpdateService $updateService,
    ) {}

    public function index(): View { /* 保留 */ }

    public function update(SiteSettingUpdateRequest $request): RedirectResponse
    {
        $result = $this->updateService->update($request->validated(), $request);
        return redirect()->route('admin.site-settings.index')
            ->with('success', $result['message']);
    }

    public function clearCache(): JsonResponse { /* 保留 */ }
    public function export(): StreamedResponse { /* 保留 */ }

    public function sanitizeHeadSnippets(Request $request): JsonResponse
    {
        // 也可移入 updateService，但保留在 Controller 也可（逻辑简单）
    }
}
```

---

## 5. Phase 4: SystemOpsController 抽离

**优先级**: 🟡 中  
**预计工时**: 6h  
**前置依赖**: 无

### 5.1 当前代码分布（375 行）

| 方法 | 行数 | 去向 |
|------|------|------|
| `index()` | 12 | 保留 |
| `clearCache()` | 31 | 保留（简单 Artisan 调用） |
| `optimize()` | 18 | 保留 |
| `queueAction()` | 23 | 保留 |
| `queueStats()` | 7 | 保留 |
| `migrate()` | 18 | 保留 |
| `runCommand()` | 52 | 保留（含安全白名单校验） |
| `logs()` | 39 | → `SystemOpsService::parseLogs()` |
| `getSystemInfo()` | 14 | → `SystemOpsService` |
| `getQueueStats()` | 18 | → `SystemOpsService` |
| `getCacheStats()` | 11 | → `SystemOpsService` |
| `getDatabaseStats()` | 50 | → `SystemOpsService` |

### 5.2 新建 Service: `SystemOpsService`

```php
// app/Services/Admin/SystemOpsService.php
namespace App\Services\Admin;

final class SystemOpsService
{
    public function getSystemInfo(): array { /* 从 Controller 迁移 */ }
    public function getQueueStats(): array { /* 从 Controller 迁移 */ }
    public function getCacheStats(): array { /* 从 Controller 迁移 */ }
    public function getDatabaseStats(): array { /* 从 Controller 迁移 */ }
    public function parseLogs(int $maxLines = 500): array { /* 从 logs() 迁移 */ }
}
```

### 5.3 瘦身后的 SystemOpsController（~160 行）

Controller 仅保留 Artisan 命令调用和路由分发，数据采集全部委托 `SystemOpsService`。

---

## 6. Phase 5: FileManagerController 抽离

**优先级**: 🟡 中  
**预计工时**: 6h  
**前置依赖**: 无

### 6.1 当前代码分布（404 行）

| 方法 | 行数 | 去向 |
|------|------|------|
| `index()` | 16 | 保留（调用 Service） |
| `upload()` | 57 | → `FileManagerService::upload()` |
| `createFolder()` | 20 | → `FileManagerService::createFolder()` |
| `destroy()` | 22 | → `FileManagerService::delete()` |
| `download()` | 9 | 保留（简单代理） |
| `rename()` | 26 | → `FileManagerService::rename()` |
| `getFiles()` | 42 | → `FileManagerService` |
| `getBreadcrumbs()` | 17 | → `FileManagerService` |
| `getStats()` | 28 | → `FileManagerService` |
| `sanitizePath()` | 13 | → `FileManagerService` |
| `generateUniqueFilename()` | 21 | → `FileManagerService` |
| `sanitizeSvg()` | 21 | → `FileManagerService` |

### 6.2 新建 Service: `FileManagerService`

```php
// app/Services/Admin/FileManagerService.php
namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;

final class FileManagerService
{
    private const ALLOWED_MIME_TYPES = [ /* ... */ ];
    private const FORBIDDEN_EXTENSIONS = [ /* ... */ ];

    public function listFiles(string $path): array { /* getFiles + getBreadcrumbs + getStats */ }
    public function upload(array $files, string $path): array { /* 返回 [uploaded, failed] */ }
    public function createFolder(string $path, string $folderName): bool { /* ... */ }
    public function delete(string $path): bool { /* ... */ }
    public function rename(string $oldPath, string $newName): bool { /* ... */ }
    public function sanitizePath(string $path): string { /* ... */ }
    public function sanitizeSvg(UploadedFile $file): void { /* ... */ }
    public function generateUniqueFilename(string $path, string $originalName): string { /* ... */ }
}
```

### 6.3 瘦身后的 FileManagerController（~80 行）

Controller 仅处理 Request 验证和 Response 格式化，全部文件操作委托 Service。

---

## 7. Phase 6: DataExportController 抽离

**优先级**: 🟢 低（已有 CsvExportService）  
**预计工时**: 4h  
**前置依赖**: 无

### 7.1 当前代码分布（242 行）

| 方法 | 行数 | 去向 |
|------|------|------|
| `index()` | 35 | 保留 |
| `export()` | 10 | 保留（路由分发） |
| `preview()` | 15 | 保留 |
| `exportUsers()` | 30 | → `DataExportService::exportUsers()` |
| `exportResumes()` | 25 | → `DataExportService::exportResumes()` |
| `exportInterviews()` | 25 | → `DataExportService::exportInterviews()` |
| `exportApplications()` | 25 | → `DataExportService::exportApplications()` |
| `exportAiUsage()` | 25 | → `DataExportService::exportAiUsage()` |

### 7.2 新建 Service: `DataExportService`

将 5 个 export 方法迁移为 Service 方法，每个方法返回 `StreamedResponse`。Controller 仅做路由分发。

---

## 8. Phase 7: ResumeStreamController 抽离

**优先级**: 🟠 高（核心 AI 功能）  
**预计工时**: 8h  
**前置依赖**: Phase 1（CheckQuota 重构）

### 8.1 当前代码分布（484 行）

| 方法 | 行数 | 去向 |
|------|------|------|
| `prewarm()` | 7 | 保留 |
| `optimizeStream()` | ~280 | → `ResumeOptimizeStreamService::stream()` |

### 8.2 新建 FormRequest: `ResumeOptimizeStreamRequest`

将 `optimizeStream()` 前 58 行的验证规则抽取到 FormRequest。

### 8.3 新建 Service: `ResumeOptimizeStreamService`

```php
// app/Services/Resume/ResumeOptimizeStreamService.php
namespace App\Services\Resume;

use App\Models\Resume;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ResumeOptimizeStreamService
{
    /**
     * 执行简历优化流式输出
     * 从 ResumeStreamController::optimizeStream() 第 59-320 行迁移
     */
    public function stream(Resume $resume, array $validated): StreamedResponse
    {
        // 迁移全部 AI 调用 + 重试 + 结果解析逻辑
    }
}
```

### 8.4 瘦身后的 ResumeStreamController（~30 行）

```php
public function optimizeStream(ResumeOptimizeStreamRequest $request, Resume $resume): StreamedResponse
{
    $this->authorize('update', $resume);
    return $this->streamService->stream($resume, $request->validated());
}
```

---

## 9. Phase 8: ResumeOptimizeSessionController 抽离

**优先级**: 🟡 中  
**预计工时**: 6h  
**前置依赖**: Phase 1

### 9.1 当前代码分布（398 行）

| 方法 | 行数 | 去向 |
|------|------|------|
| `create()` | ~116 | → 验证抽到 FormRequest，业务逻辑扩展 `ResumeOptimizeSessionService` |
| 其他方法 | ~280 | 保留（大部分已使用 Service） |

### 9.2 新建 FormRequest: `ResumeOptimizeSessionCreateRequest`

### 9.3 扩展 `ResumeOptimizeSessionService`

将 `create()` 中的幂等检查 + 会话创建 + 配额标记逻辑移入已有的 `ResumeOptimizeSessionService`。

---

## 10. 执行顺序与依赖关系

```
Phase 1: CheckQuota 重构 (8h)
    ↓ (无依赖，可独立)
Phase 2: AiConfigController 抽离 (10h)
Phase 3: SiteSettingController 抽离 (8h)
    ↓ (可并行)
Phase 4: SystemOpsController 抽离 (6h)
Phase 5: FileManagerController 抽离 (6h)
Phase 6: DataExportController 抽离 (4h)
    ↓ (依赖 Phase 1)
Phase 7: ResumeStreamController 抽离 (8h)
Phase 8: ResumeOptimizeSessionController 抽离 (6h)
```

### 推荐执行顺序

| 批次 | Phase | 可并行 | 累计工时 |
|------|-------|--------|----------|
| 批次 1 | Phase 1 (CheckQuota) | — | 8h |
| 批次 2 | Phase 2 + 3 + 4 | ✅ 可并行 | 24h |
| 批次 3 | Phase 5 + 6 | ✅ 可并行 | 10h |
| 批次 4 | Phase 7 + 8 | ✅ 可并行 | 14h |
| **总计** | | | **56h** |

### 每个 Phase 内部步骤

```
1. 创建新 Service/FormRequest/DTO 文件
2. 从 Controller 迁移业务逻辑到 Service
3. 重写 Controller 使用新 Service
4. 编写 Service 单元测试
5. 运行已有功能测试确认无回归
6. 手工测试关键流程
7. 提交代码
```

---

## 11. 风险控制

### 11.1 回归风险矩阵

| Phase | 影响范围 | 风险等级 | 缓解措施 |
|-------|---------|---------|----------|
| Phase 1 (CheckQuota) | 所有 AI 功能路由 | 🔴 高 | 编写 QuotaResolution 单元测试 + 全量手工测试 |
| Phase 2 (AiConfig) | AI 配置管理 | 🟡 中 | AiBenchmarkService 单元测试 |
| Phase 3 (SiteSetting) | 网站设置保存 | 🟡 中 | SiteSettingUpdateRequest 验证测试 |
| Phase 4 (SystemOps) | 运维页面展示 | 🟢 低 | 数据读取，无写操作风险 |
| Phase 5 (FileManager) | 文件管理 | 🟡 中 | 上传/删除需仔细测试 |
| Phase 6 (DataExport) | 数据导出 | 🟢 低 | 只读操作 |
| Phase 7 (ResumeStream) | 简历优化核心 | 🔴 高 | SSE 流式输出需端到端测试 |
| Phase 8 (ResumeSession) | 优化会话 | 🟡 中 | 已有 Service 基础 |

### 11.2 安全检查清单

- [x] FormRequest 的 `authorize()` 方法正确返回权限判断（改用 inline validate，权限由中间件保障）
- [x] Service 中不直接使用 `auth()->id()`，由 Controller 传入
- [x] 文件操作 Service 保留路径清理（sanitizePath）逻辑
- [x] QuotaResolution DTO 不可变（readonly），防止中间件和 Service 之间状态不一致
- [x] `sanitizeHeadSnippet()` 统一使用 `AppServiceProvider` 中的版本，删除 Controller 中的重复实现

### 11.3 回滚策略

每个 Phase 独立提交 Git commit，如有问题可单独 revert：
```
git revert <phase-commit-hash>
```

关键 Phase（1、7）建议在独立分支开发，合并前通过全量测试。

---

## 12. 执行总结

### 12.1 实际执行结果

| Phase | 状态 | 关键变更 |
|-------|------|---------|
| Phase 1 (CheckQuota) | ✅ 完成 | 引入 `QuotaResolution` DTO，中间件从 232→~160 行；逻辑反转：`credit_confirmation_routes` → `credit_auto_select_routes`（默认所有路由需确认） |
| Phase 2 (AiConfig) | ✅ 完成 | 抽离 `AiBenchmarkService` + `AiConfigUpdateService`，控制器从 579→~120 行；使用 inline validate 替代 FormRequest |
| Phase 3 (SiteSetting) | ✅ 完成 | 抽离 `SystemSettingUpdateService`，控制器从 344→~170 行；`sanitizeHeadSnippet` 统一到 `AppServiceProvider` |
| Phase 4 (SystemOps) | ✅ 完成 | 抽离 `SystemOpsService`，数据采集逻辑下沉到 Service |
| Phase 5 (FileManager) | ✅ 完成 | 抽离 `FileManagerService`，控制器从 404→~80 行 |
| Phase 6 (DataExport) | ✅ 完成 | 抽离 `DataExportService`，控制器从 242→~80 行 |
| Phase 7 (ResumeStream) | ✅ 完成 | 抽离 `ResumeOptimizeStreamService::stream()`，控制器从 484→~70 行 |
| Phase 8 (ResumeSession) | ⏭ 跳过 | 评估后 `create()` 方法逻辑（验证+授权+日志+响应）属于控制器职责，已有 Service 基础，无需额外抽离 |

### 12.2 代码量变化

- **重构前总行数**: ~3,350 行（8 个文件）
- **重构后总行数**: ~740 行（8 个控制器）+ ~1,200 行（7 个新 Service）+ ~50 行（1 个 DTO）
- **控制器总行数减少**: ~2,610 行 → ~740 行（减少 72%）

### 12.3 重要设计决策

1. **信用确认逻辑反转**: `credit_confirmation_routes` → `credit_auto_select_routes`，默认所有路由需用户确认使用次卡，仅白名单路由自动选择
2. **FormRequest → inline validate**: Laravel 的 FormRequest 在控制器私有方法中无法自动解析，改为 `$request->validate()` 内联验证
3. **sanitizeHeadSnippet 统一**: 从 3 处重复实现（AppServiceProvider + blade partial + Controller）收敛到 `AppServiceProvider::sanitizeHeadSnippetPublic()` 单一来源
4. **视图模板清理**: 删除 `sanitize-snippet.blade.php` partial，布局模板中直接使用预清洗的变量

### 12.4 测试结果

- **重构相关测试**: 全部通过（SiteSettingControllerTest, ResumeStreamControllerTest 等）
- **预存失败测试**: 31 个，均为重构前已存在的问题：
  - `OpenAiCompatibleProviderPromptTest` (5): ReflectionException
  - `ResumeOptimizeFlowTest` (6): Resume::factory() 缺失 / getQuotaLimit() on null
  - `ResumeOptimizeSessionControllerTest` (4): getQuotaLimit() on null
  - `ApiIntegrationTest` (5): API 路由/中间件配置问题
  - 其他 (11): 各类环境配置问题

### 12.5 已清理的文件

- `app/Http/Requests/Admin/AiConfigGlobalUpdateRequest.php` — 已删除（未使用）
- `app/Http/Requests/Admin/AiConfigProviderUpdateRequest.php` — 已删除（未使用）
- `resources/views/partials/sanitize-snippet.blade.php` — 已删除（逻辑迁移到 AppServiceProvider）
