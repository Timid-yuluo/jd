<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\DTO\QuotaResolution;
use App\Enums\QuotaDenyReason;
use App\Models\UserCredit;
use App\Services\Membership\QuotaService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * 配额检查中间件 — 重构后仅做拦截/标记，业务决策委托 QuotaService::resolve()
 *
 * 中间件职责：
 * 1. 调用 QuotaService::resolve() 获取决策结果
 * 2. 不可放行时返回拒绝响应
 * 3. 可放行时将 QuotaResolution 存入 request attributes
 * 4. 响应成功时扣减配额（幂等保护防止重复扣减）
 *
 * 幂等性保护：
 * - 基于请求指纹（用户ID+路由+参数哈希）生成唯一键
 * - 扣减前检查是否已处理，避免重试导致重复扣减
 */
final class CheckQuota
{
    /**
     * 幂等键 TTL（秒）：5 分钟内重复请求不重复扣减
     */
    private const IDEMPOTENCY_TTL = 300;

    public function __construct(
        private readonly QuotaService $quotaService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (! $routeName) {
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

        // 兼容旧的 request attributes，确保 markQuotaConsumptionSuccess / resolveQuotaConsumptionContext 正常工作
        $request->attributes->set('quota_source', $resolution->source);
        $request->attributes->set('quota_key', $resolution->quotaKey);
        $request->attributes->set('quota_consume', false);
        if ($resolution->source === 'credit' && $resolution->creditId !== null) {
            $request->attributes->set('credit_id', $resolution->creditId);
        }

        $response = $next($request);

        // 在 handle 中直接处理配额扣减，避免 terminate() 在进程异常终止时不执行
        // 仅当控制器显式标记成功（quota_consume = true）且响应成功时扣减
        $shouldConsume = (bool) $request->attributes->get('quota_consume', false);
        if ($shouldConsume && $response->getStatusCode() < 400) {
            // 原子性幂等检查：Cache::add 仅在 key 不存在时写入并返回 true
            $idempotencyKey = $this->idempotencyKey($user->id, $routeName, $request);
            if (Cache::add($idempotencyKey, true, self::IDEMPOTENCY_TTL)) {
                $this->quotaService->deduct($resolution, $user->id, $response->getStatusCode());
            }
        }

        // 自动次卡提示
        if ($resolution->autoSelected
            && $shouldConsume
            && $response->getStatusCode() < 400
            && $request->hasSession()) {
            $quotaLabel = UserCredit::quotaLabel($resolution->quotaKey);
            $creditName = $resolution->creditName ?? '次卡';
            $request->session()->flash('info', "{$quotaLabel}本月免费次数已用完，已自动使用「{$creditName}」扣除 1 次。");
        }

        return $response;
    }

    /**
     * 响应发送后的清理逻辑
     *
     * 配额扣减已移至 handle() 中执行以确保可靠性，
     * terminate() 仅保留作为兼容钩子，不再执行核心逻辑。
     */
    public function terminate(Request $request, Response $response): void
    {
        // 配额扣减已在 handle() 中完成，此处 intentionally left empty
    }

    /**
     * 生成幂等性键：基于用户、路由和请求参数指纹
     *
     * 排除文件上传参数（UploadedFile 无法序列化且不同文件相同参数会产生相同指纹）。
     * 仅对非文件参数计算指纹，确保含文件请求不会被误判为重复。
     */
    private function idempotencyKey(int $userId, string $routeName, Request $request): string
    {
        $params = array_filter(
            $request->all(),
            static fn (mixed $value): bool => ! $value instanceof \Illuminate\Http\UploadedFile,
        );
        ksort($params);
        $fingerprint = md5(json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return "quota:idempotency:{$userId}:{$routeName}:{$fingerprint}";
    }

    /**
     * 根据拒绝原因生成响应
     */
    private function denyResponse(QuotaResolution $r, Request $request): JsonResponse|RedirectResponse
    {
        $quotaLabel = UserCredit::quotaLabel($r->quotaKey);
        $user = $request->user();

        // 套餐升级引导上下文：当前套餐、配额重置时间
        $upgradeContext = $this->buildUpgradeContext($user);

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
                        'current_plan' => $upgradeContext['current_plan'],
                        'quota_resets_at' => $upgradeContext['quota_resets_at'],
                    ],
                ], 200, ['X-Quota-Status' => 'exceeded'])
                : redirect()->back()->withInput()->with(
                    'warning',
                    "{$quotaLabel} 本月免费次数已用完（{$upgradeContext['current_plan']}，{$upgradeContext['quota_resets_at']} 重置）。可购买次卡继续使用，或升级套餐获取更多次数。"
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
                        'current_plan' => $upgradeContext['current_plan'],
                        'quota_resets_at' => $upgradeContext['quota_resets_at'],
                    ],
                ], 429, ['X-Quota-Status' => 'exceeded'])
                : redirect()->back()->withInput()->with(
                    'warning',
                    "{$quotaLabel} 本月次数已用完且无可用次卡（{$upgradeContext['current_plan']}，{$upgradeContext['quota_resets_at']} 重置）。请升级套餐或购买次卡后再试。"
                );
        }

        $message = match ($r->denyReason) {
            QuotaDenyReason::InvalidCredit->value => QuotaDenyReason::InvalidCredit->label(),
            QuotaDenyReason::CreditKeyMismatch->value => QuotaDenyReason::CreditKeyMismatch->label(),
            default => '无法使用次卡',
        };

        return response()->json([
            'code' => $r->denyReason,
            'message' => $message,
        ], 422, ['X-Quota-Status' => 'exceeded']);
    }

    /**
     * 构建套餐升级引导上下文：当前套餐名称 + 配额重置时间
     *
     * @return array{current_plan: string, quota_resets_at: string}
     */
    private function buildUpgradeContext(?\App\Models\User $user): array
    {
        if ($user === null) {
            return ['current_plan' => '免费版', 'quota_resets_at' => '下月1日'];
        }

        $plan = $user->currentPlan();
        $planName = $plan?->name ?? '免费版';

        // 配额按月重置，下月1日 00:00
        $resetDate = now()->startOfMonth()->addMonth();
        $resetText = $resetDate->format('m月d日');

        return [
            'current_plan' => $planName,
            'quota_resets_at' => $resetText,
        ];
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
