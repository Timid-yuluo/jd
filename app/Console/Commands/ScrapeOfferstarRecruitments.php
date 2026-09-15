<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ExternalRecruitment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ScrapeOfferstarRecruitments extends Command
{
    protected $signature = 'scrape:offerstar
        {--page=1 : 起始页码}
        {--pages=0 : 抓取页数，0 表示全部}
        {--delay=1 : 每页请求间隔秒数}
        {--timeout=30 : HTTP 超时秒数}
        {--auto-approve=0 : 1=自动审核通过，0=待审核}';

    protected $description = '从 offerstar.cn 抓取招聘信息并保存到数据库';

    private int $created = 0;

    private int $updated = 0;

    private int $skipped = 0;

    private int $failed = 0;

    public function handle(): int
    {
        $startPage = (int) $this->option('page');
        $maxPages = (int) $this->option('pages');
        $delay = (int) $this->option('delay');
        $timeout = (int) $this->option('timeout');
        $autoApprove = (int) $this->option('auto-approve') === 1;

        $this->info('开始抓取 offerstar.cn 招聘信息...');

        $totalPages = $this->fetchTotalPages($timeout);
        if ($totalPages === 0) {
            $this->error('无法获取总页数，请检查网络连接');

            return self::FAILURE;
        }

        $endPage = $maxPages > 0
            ? min($startPage + $maxPages - 1, $totalPages)
            : $totalPages;

        $this->info("总页数: {$totalPages}，本次抓取: 第 {$startPage} ~ {$endPage} 页");

        $bar = $this->output->createProgressBar($endPage - $startPage + 1);
        $bar->setFormat(' %current%/%max% [%bar%] %percent%% | 已创建: %created% | 已更新: %updated% | 跳过: %skipped% | 失败: %failed%');
        $bar->setMessage((string) $this->created, 'created');
        $bar->setMessage((string) $this->updated, 'updated');
        $bar->setMessage((string) $this->skipped, 'skipped');
        $bar->setMessage((string) $this->failed, 'failed');
        $bar->start();

        for ($page = $startPage; $page <= $endPage; $page++) {
            try {
                $items = $this->fetchPage($page, $timeout);
                foreach ($items as $item) {
                    $this->saveItem($item, $autoApprove);
                }
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("第 {$page} 页抓取失败: {$e->getMessage()}");
                Log::warning('scrape:offerstar page failed', [
                    'page' => $page,
                    'error' => $e->getMessage(),
                ]);
                $this->failed++;
            }

            $bar->setMessage((string) $this->created, 'created');
            $bar->setMessage((string) $this->updated, 'updated');
            $bar->setMessage((string) $this->skipped, 'skipped');
            $bar->setMessage((string) $this->failed, 'failed');
            $bar->advance();

            if ($page < $endPage && $delay > 0) {
                usleep($delay * 1000000);
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("抓取完成！创建: {$this->created}, 更新: {$this->updated}, 跳过: {$this->skipped}, 失败: {$this->failed}");

        return self::SUCCESS;
    }

    private function fetchTotalPages(int $timeout): int
    {
        try {
            $html = $this->fetchHtml(1, $timeout);
            // HTML 中有 <span> 标签：共 <span>18248</span> 条...共 <span>913</span> 页
            if (preg_match('/共\s*<[^>]*>(\d+)<[^>]*>\s*条.*?共\s*<[^>]*>(\d+)<[^>]*>\s*页/s', $html, $m)) {
                $this->info("共 {$m[1]} 条记录，{$m[2]} 页");

                return (int) $m[2];
            }
            // fallback: 尝试无标签匹配
            if (preg_match('/共\s*(\d+)\s*条.*?共\s*(\d+)\s*页/s', strip_tags($html), $m)) {
                $this->info("共 {$m[1]} 条记录，{$m[2]} 页");

                return (int) $m[2];
            }
            $this->warn('无法匹配总页数，HTML 长度: '.strlen($html));
        } catch (\Throwable $e) {
            $this->warn("获取总页数失败: {$e->getMessage()}");
        }

        return 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchPage(int $page, int $timeout): array
    {
        $html = $this->fetchHtml($page, $timeout);

        return $this->parseItems($html);
    }

    private function fetchHtml(int $page, int $timeout): string
    {
        $url = "https://www.offerstar.cn/recruitment?current={$page}&pageSize=20";

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
        ])
            ->timeout($timeout)
            ->connectTimeout(10)
            ->retry(3, 1000)
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()} for page {$page}");
        }

        return $response->body();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseItems(string $html): array
    {
        $items = [];

        if (! preg_match_all('/\{\\\\?"_id\\\\?":\\\\?"[a-f0-9]+\\\\?".*?\\\\?"searchText\\\\?":\\\\?"[^"]*\\\\?"\}/s', $html, $matches)) {
            return $items;
        }

        foreach ($matches[0] as $raw) {
            $json = stripcslashes($raw);
            $data = json_decode($json, true);
            if (! is_array($data) || empty($data['_id'])) {
                continue;
            }
            $items[] = $data;
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function saveItem(array $item, bool $autoApprove): void
    {
        $sourceId = (string) $item['_id'];
        $sourceUrl = isset($item['detailUrl']) && is_string($item['detailUrl']) && trim($item['detailUrl']) !== ''
            ? (string) $item['detailUrl']
            : null;

        $attrs = [
            'source_id' => $sourceId,
            'source_name' => 'offerstar',
            'source_url' => $sourceUrl,
            'announcement_url' => $sourceUrl,
            'company' => (string) ($item['company'] ?? ''),
            'title' => (string) ($item['title'] ?? ''),
            'work_location' => (string) ($item['workLocation'] ?? ''),
            'industry' => (string) ($item['industry'] ?? ''),
            'positions' => (string) ($item['positions'] ?? ''),
            'channel' => (string) ($item['channel'] ?? ''),
            'batch' => (string) ($item['batch'] ?? ''),
            'is_hot' => (bool) ($item['isHot'] ?? false),
            'apply_url' => (string) ($item['referralMethod'] ?? ''),
            'position_tags' => $item['normalizedPositionTags'] ?? null,
            'source_tags' => $item['normalizedPositionTags'] ?? null,
            'work_locations' => $item['normalizedWorkLocations'] ?? null,
            'deadline' => isset($item['deadline']) ? $this->parseTimestamp($item['deadline']) : null,
            'apply_deadline_text' => isset($item['deadline']) ? (string) $item['deadline'] : null,
            'fingerprint' => (string) ($item['fingerprint'] ?? ''),
            'remarks' => (string) ($item['remarks'] ?? ''),
            'source_created_at' => $this->parseTimestamp($item['createTime'] ?? null),
            'source_updated_at' => $this->parseTimestamp($item['updateTime'] ?? null),
            'raw_payload' => $item,
            'imported_at' => now(),
            'recruitment_type' => $this->inferRecruitmentType($item),
        ];

        $existing = ExternalRecruitment::where('source_id', $sourceId)->first();

        if ($existing) {
            if ($autoApprove) {
                $attrs['review_status'] = ExternalRecruitment::REVIEW_APPROVED;
                $attrs['reviewed_at'] = now();
            }
            $existing->update($attrs);
            $this->updated++;
        } else {
            if ($autoApprove) {
                $attrs['review_status'] = ExternalRecruitment::REVIEW_APPROVED;
                $attrs['reviewed_at'] = now();
            } else {
                $attrs['review_status'] = ExternalRecruitment::REVIEW_PENDING;
            }
            ExternalRecruitment::create($attrs);
            $this->created++;
        }
    }

    private function parseTimestamp(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $ts = (int) $value;
        if ($ts <= 0) {
            return null;
        }

        return date('Y-m-d H:i:s', (int) ($ts / 1000));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function inferRecruitmentType(array $item): string
    {
        $text = mb_strtolower(
            implode(' ', array_filter([
                (string) ($item['channel'] ?? ''),
                (string) ($item['title'] ?? ''),
                (string) ($item['positions'] ?? ''),
                (string) ($item['remarks'] ?? ''),
            ]))
        );

        if (str_contains($text, '校招') || str_contains($text, '应届') || str_contains($text, 'campus')) {
            return ExternalRecruitment::TYPE_CAMPUS;
        }
        if (str_contains($text, '社招') || str_contains($text, '社会招聘') || str_contains($text, 'experienced') || str_contains($text, 'social')) {
            return ExternalRecruitment::TYPE_SOCIAL;
        }

        return ExternalRecruitment::TYPE_SOCIAL;
    }
}
