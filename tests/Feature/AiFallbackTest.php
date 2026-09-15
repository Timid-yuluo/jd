<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\AiServiceUnavailableException;
use App\Infrastructure\AI\AiManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * AI 服务降级与 Fallback 逻辑测试
 *
 * 覆盖场景：
 * - 异常分类（rate_limited / timeout / connection / non_retryable）
 * - shouldRetryWithFallback 判断逻辑
 * - shouldRetrySameDriverAfterBackoff（zhipu 限流重试）
 * - resolveBackoffMilliseconds（指数退避 + Retry-After 头）
 * - 熔断器状态（circuit open / record success/failure）
 * - orderedDrivers 过滤熔断中的驱动
 */
class AiFallbackTest extends TestCase
{
    use RefreshDatabase;

    private AiManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(AiManager::class);

        // 配置 fallback chain
        Config::set('ai.fallback_chain', ['openai', 'zhipu']);
        Config::set('ai.rate_limit.zhipu_max_attempts', 2);
        Config::set('ai.rate_limit.initial_backoff_ms', 100);
        Config::set('ai.rate_limit.max_backoff_ms', 1000);
        Config::set('ai.rate_limit.multiplier', 2);
    }

    public function test_is_rate_limit_exception_detects_429_status(): void
    {
        $this->assertTrue($this->manager->isRateLimitException(new \Exception('HTTP 429 Too Many Requests')));
        $this->assertTrue($this->manager->isRateLimitException(new \Exception('rate limit exceeded')));
        $this->assertTrue($this->manager->isRateLimitException(new \Exception('请求频率过高')));
        $this->assertTrue($this->manager->isRateLimitException(new \Exception('限流')));
    }

    public function test_is_rate_limit_exception_returns_false_for_non_rate_limit(): void
    {
        $this->assertFalse($this->manager->isRateLimitException(new \Exception('Invalid API key')));
        $this->assertFalse($this->manager->isRateLimitException(new \Exception('Internal server error')));
    }

    public function test_should_retry_with_fallback_for_timeout(): void
    {
        $this->assertTrue($this->manager->shouldRetryWithFallback(new \Exception('Request timeout')));
        $this->assertTrue($this->manager->shouldRetryWithFallback(new \Exception('Connection refused')));
        $this->assertTrue($this->manager->shouldRetryWithFallback(new \Exception('stream request failed')));
    }

    public function test_should_retry_with_fallback_for_rate_limit(): void
    {
        $this->assertTrue($this->manager->shouldRetryWithFallback(new \Exception('HTTP 429 rate limit')));
    }

    public function test_should_not_retry_with_fallback_for_auth_errors(): void
    {
        $this->assertFalse($this->manager->shouldRetryWithFallback(new \Exception('Invalid API key')));
        $this->assertFalse($this->manager->shouldRetryWithFallback(new \Exception('Unauthorized')));
    }

    public function test_classify_exception_returns_rate_limited_type(): void
    {
        $result = $this->manager->classifyException(new \Exception('HTTP 429 rate limit'));
        $this->assertSame('rate_limited', $result['type']);
        $this->assertTrue($result['retryable']);
    }

    public function test_classify_exception_returns_timeout_type(): void
    {
        $result = $this->manager->classifyException(new \Exception('Request timed out'));
        $this->assertSame('timeout', $result['type']);
        $this->assertTrue($result['retryable']);
    }

    public function test_classify_exception_returns_non_retryable_for_auth_error(): void
    {
        $result = $this->manager->classifyException(new \Exception('Invalid API key'));
        $this->assertFalse($result['retryable']);
    }

    public function test_should_retry_same_driver_for_zhipu_rate_limit(): void
    {
        $exception = new \Exception('HTTP 429 rate limit');

        // 第一次尝试应重试
        $this->assertTrue($this->manager->shouldRetrySameDriverAfterBackoff('zhipu', $exception, 1));
        // 第二次尝试（达到 max_attempts=2）不应重试
        $this->assertFalse($this->manager->shouldRetrySameDriverAfterBackoff('zhipu', $exception, 2));
    }

    public function test_should_not_retry_same_driver_for_non_zhipu(): void
    {
        $exception = new \Exception('HTTP 429 rate limit');
        $this->assertFalse($this->manager->shouldRetrySameDriverAfterBackoff('openai', $exception, 1));
    }

    public function test_should_not_retry_same_driver_for_non_rate_limit(): void
    {
        $exception = new \Exception('Connection timeout');
        $this->assertFalse($this->manager->shouldRetrySameDriverAfterBackoff('zhipu', $exception, 1));
    }

    public function test_resolve_backoff_uses_exponential_backoff(): void
    {
        $exception = new \Exception('Connection timeout');

        // config/ai.php 对 initial_backoff_ms 有 max(200, ...) 下限保护
        // 测试设置 initial_backoff_ms=100 实际会被 max(200, 100)=200 覆盖
        // 第一次重试：200ms（initial_backoff_ms 下限）
        $backoff1 = $this->manager->resolveBackoffMilliseconds($exception, 1);
        $this->assertSame(200, $backoff1);

        // 第二次重试：400ms（200 * 2^1）
        $backoff2 = $this->manager->resolveBackoffMilliseconds($exception, 2);
        $this->assertSame(400, $backoff2);

        // 第三次重试：800ms（200 * 2^2）
        $backoff3 = $this->manager->resolveBackoffMilliseconds($exception, 3);
        $this->assertSame(800, $backoff3);
    }

    public function test_resolve_backoff_respects_max_limit(): void
    {
        $exception = new \Exception('HTTP 429 rate limit');

        // 第十次重试应被限制在 max_backoff_ms=1000
        $backoff = $this->manager->resolveBackoffMilliseconds($exception, 10);
        $this->assertLessThanOrEqual(1000, $backoff);
    }

    public function test_circuit_breaker_opens_after_threshold_failures(): void
    {
        $driver = 'zhipu';
        $scenario = 'circuit_test';
        $exception = new \Exception('HTTP 429 rate limit');

        Config::set('job-matching.circuit_breaker_threshold', 3);
        Config::set('job-matching.circuit_breaker_ttl_seconds', 60);

        // 清理可能存在的缓存
        \Illuminate\Support\Facades\Cache::forget('ai:circuit:counter:' . $scenario . ':' . $driver);
        \Illuminate\Support\Facades\Cache::forget('ai:circuit:open:' . $scenario . ':' . $driver);

        // 记录 3 次失败应触发熔断（threshold=3）
        $this->manager->recordDriverFailure($driver, $scenario, $exception);
        $this->manager->recordDriverFailure($driver, $scenario, $exception);
        $this->assertFalse($this->manager->isDriverCircuitOpen($driver, $scenario));

        $this->manager->recordDriverFailure($driver, $scenario, $exception);
        $this->assertTrue($this->manager->isDriverCircuitOpen($driver, $scenario));
    }

    public function test_record_driver_success_clears_circuit(): void
    {
        $driver = 'zhipu';
        $scenario = 'circuit_success_test';
        $exception = new \Exception('HTTP 429 rate limit');

        Config::set('job-matching.circuit_breaker_threshold', 1);
        Config::set('job-matching.circuit_breaker_ttl_seconds', 60);

        \Illuminate\Support\Facades\Cache::forget('ai:circuit:counter:' . $scenario . ':' . $driver);
        \Illuminate\Support\Facades\Cache::forget('ai:circuit:open:' . $scenario . ':' . $driver);

        $this->manager->recordDriverFailure($driver, $scenario, $exception);
        $this->assertTrue($this->manager->isDriverCircuitOpen($driver, $scenario));

        $this->manager->recordDriverSuccess($driver, $scenario);
        $this->assertFalse($this->manager->isDriverCircuitOpen($driver, $scenario));
    }

    public function test_ordered_drivers_filters_circuit_open_drivers(): void
    {
        $driver = 'zhipu';
        $scenario = 'default';
        $exception = new \Exception('HTTP 429 rate limit');

        Config::set('job-matching.circuit_breaker_threshold', 1);
        Config::set('job-matching.circuit_breaker_ttl_seconds', 60);

        \Illuminate\Support\Facades\Cache::forget('ai:circuit:counter:' . $scenario . ':' . $driver);
        \Illuminate\Support\Facades\Cache::forget('ai:circuit:open:' . $scenario . ':' . $driver);

        // 熔断 zhipu 驱动
        $this->manager->recordDriverFailure($driver, $scenario, $exception);
        $this->assertTrue($this->manager->isDriverCircuitOpen($driver, $scenario));

        $drivers = $this->manager->orderedDrivers();

        // zhipu 应被过滤掉（前提是还有其他驱动可用，否则降级返回全部）
        // 如果 fallback_chain 配置了 openai，zhipu 应被过滤
        if (in_array('openai', $drivers)) {
            $this->assertNotContains('zhipu', $drivers);
        } else {
            // 所有驱动均熔断时降级返回全部，zhipu 可能仍在列表中
            $this->assertContains($driver, $drivers);
        }
    }

    public function test_ai_service_unavailable_exception_stores_context(): void
    {
        $exception = new AiServiceUnavailableException(
            'AI 服务不可用',
            'resume_optimize_failed',
            ['driver' => 'zhipu', 'error' => 'timeout']
        );

        $this->assertSame('AI 服务不可用', $exception->getMessage());
        $this->assertSame('resume_optimize_failed', $exception->event());
        $this->assertSame(['driver' => 'zhipu', 'error' => 'timeout'], $exception->context());
    }
}
