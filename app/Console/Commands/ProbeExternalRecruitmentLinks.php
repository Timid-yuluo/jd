<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ExternalRecruitment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProbeExternalRecruitmentLinks extends Command
{
    protected $signature = 'links:probe-external-recruitments
        {--source=* : 指定来源（可多选）}
        {--limit=200 : 最多检测条数}
        {--timeout=12 : 单链接超时秒数}
        {--only-approved=1 : 1=仅检测已通过记录，0=检测全部状态}
        {--mark-pending=1 : 1=失效后回流待审核，0=仅输出不修改}
        {--dry-run=0 : 1=仅预览不落库}';

    protected $description = '巡检外部招聘投递链接可达性，将失效链接自动打回待审核池';

    private int $checked = 0;

    private int $valid = 0;

    private int $invalid = 0;

    private int $unknown = 0;

    private int $updated = 0;

    public function handle(): int
    {
        $sources = array_values(array_filter(array_map(
            static fn (mixed $x): string => trim((string) $x),
            (array) $this->option('source')
        )));
        $limit = max(1, (int) $this->option('limit'));
        $timeout = max(3, (int) $this->option('timeout'));
        $onlyApproved = (int) $this->option('only-approved') === 1;
        $markPending = (int) $this->option('mark-pending') === 1;
        $dryRun = (int) $this->option('dry-run') === 1;

        $query = ExternalRecruitment::query()
            ->whereNotNull('apply_url')
            ->where('apply_url', '!=', '')
            ->orderByDesc('imported_at')
            ->orderByDesc('id');

        if ($sources !== []) {
            $query->whereIn('source_name', $sources);
        }
        if ($onlyApproved) {
            $query->where('review_status', ExternalRecruitment::REVIEW_APPROVED);
        }

        $items = $query->limit($limit)->get();
        if ($items->isEmpty()) {
            $this->warn('未找到可检测的投递链接记录。');

            return self::SUCCESS;
        }

        $this->info('开始巡检投递链接可达性...');
        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        foreach ($items as $item) {
            $result = $this->probeLink((string) $item->apply_url, $timeout);
            $this->checked++;

            if ($result['state'] === 'valid') {
                $this->valid++;
            } elseif ($result['state'] === 'invalid') {
                $this->invalid++;
                $this->handleInvalidRecord($item, $result, $markPending, $dryRun);
            } else {
                $this->unknown++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("巡检完成：检测 {$this->checked}，有效 {$this->valid}，失效 {$this->invalid}，未知 {$this->unknown}，回流 {$this->updated}");

        return self::SUCCESS;
    }

    /**
     * @return array{state:string, reason:string}
     */
    private function probeLink(string $url, int $timeout): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
                ->timeout($timeout)
                ->connectTimeout(min(6, $timeout))
                ->withOptions([
                    'allow_redirects' => ['max' => 5, 'track_redirects' => true],
                ])
                ->head($url);

            $status = $response->status();
            if ($status >= 200 && $status < 400) {
                return ['state' => 'valid', 'reason' => "HTTP {$status}"];
            }
            if (in_array($status, [404, 410, 451], true)) {
                return ['state' => 'invalid', 'reason' => "HTTP {$status}"];
            }
            if ($status === 405) {
                return $this->probeWithGet($url, $timeout);
            }

            return ['state' => 'unknown', 'reason' => "HTTP {$status}"];
        } catch (\Throwable $e) {
            $msg = mb_strtolower($e->getMessage());
            if (str_contains($msg, 'could not resolve host') || str_contains($msg, 'name or service not known')) {
                return ['state' => 'invalid', 'reason' => 'DNS 解析失败'];
            }
            if (str_contains($msg, 'connection refused') || str_contains($msg, 'connection timed out')) {
                return ['state' => 'unknown', 'reason' => '连接失败/超时'];
            }

            return ['state' => 'unknown', 'reason' => '请求异常'];
        }
    }

    /**
     * @return array{state:string, reason:string}
     */
    private function probeWithGet(string $url, int $timeout): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
                'Range' => 'bytes=0-0',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
                ->timeout($timeout)
                ->connectTimeout(min(6, $timeout))
                ->withOptions([
                    'allow_redirects' => ['max' => 5, 'track_redirects' => true],
                ])
                ->get($url);

            $status = $response->status();
            if ($status >= 200 && $status < 400) {
                return ['state' => 'valid', 'reason' => "GET HTTP {$status}"];
            }
            if (in_array($status, [404, 410, 451], true)) {
                return ['state' => 'invalid', 'reason' => "GET HTTP {$status}"];
            }

            return ['state' => 'unknown', 'reason' => "GET HTTP {$status}"];
        } catch (\Throwable) {
            return ['state' => 'unknown', 'reason' => 'GET 异常'];
        }
    }

    /**
     * @param  array{state:string, reason:string}  $result
     */
    private function handleInvalidRecord(ExternalRecruitment $item, array $result, bool $markPending, bool $dryRun): void
    {
        if (! $markPending) {
            return;
        }

        $note = sprintf(
            '系统巡检：投递链接疑似失效（%s），已回流待审核。时间：%s',
            $result['reason'],
            now()->format('Y-m-d H:i:s')
        );

        if ($dryRun) {
            return;
        }

        $item->update([
            'review_status' => ExternalRecruitment::REVIEW_PENDING,
            'review_note' => $note,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
        $this->updated++;

        try {
            Log::info('external recruitment link invalid and moved to pending', [
                'id' => $item->id,
                'source_name' => $item->source_name,
                'apply_url' => $item->apply_url,
                'reason' => $result['reason'],
            ]);
        } catch (\Throwable) {
            // 日志目录无权限时忽略
        }
    }
}
