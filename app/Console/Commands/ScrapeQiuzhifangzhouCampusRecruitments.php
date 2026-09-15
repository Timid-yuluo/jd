<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ExternalRecruitment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ScrapeQiuzhifangzhouCampusRecruitments extends Command
{
    protected $signature = 'scrape:qiuzhifangzhou-campus
        {--days=90 : 向前抓取天数}
        {--timeout=30 : HTTP 超时秒数}
        {--auto-approve=0 : 1=自动审核通过，0=待审核}';

    protected $description = '从 qiuzhifangzhou.com/campus 抓取校招汇总并保存到数据库';

    private int $created = 0;

    private int $updated = 0;

    private int $failed = 0;

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $timeout = (int) $this->option('timeout');
        $autoApprove = (int) $this->option('auto-approve') === 1;

        $this->info('开始抓取 qiuzhifangzhou 校招信息...');

        try {
            $items = $this->fetchFromApi($days, $timeout);
            if ($items === []) {
                $this->warn('接口返回为空，未获取到可导入岗位。');
            }

            foreach ($items as $item) {
                $this->saveItem($item, $autoApprove);
            }
        } catch (\Throwable $e) {
            $this->error('抓取失败: '.$e->getMessage());
            Log::warning('scrape:qiuzhifangzhou-campus failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $this->info("抓取完成！创建: {$this->created}, 更新: {$this->updated}, 失败: {$this->failed}");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchFromApi(int $days, int $timeout): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
            'Origin' => 'https://www.qiuzhifangzhou.com',
            'Referer' => 'https://www.qiuzhifangzhou.com/campus',
        ])
            ->timeout($timeout)
            ->connectTimeout(10)
            ->retry(2, 1000)
            ->post('https://api.qiuzhifangzhou.com/api/campus/getCampusList', [
                'dateList' => $this->buildDateList($days),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('HTTP '.$response->status());
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return [];
        }

        $items = [];
        foreach ((array) ($payload['campusList'] ?? []) as $dayItem) {
            if (! is_array($dayItem)) {
                continue;
            }
            $md5 = (string) ($dayItem['md5'] ?? '');
            $day = (string) ($dayItem['date'] ?? '');
            foreach ((array) ($dayItem['datas'] ?? []) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $items[] = $this->mapApiRow($row, $day, $md5);
            }
        }

        return $items;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildDateList(int $days): array
    {
        $list = [];
        $today = now();
        for ($i = 0; $i < $days; $i++) {
            $list[] = [
                'date' => $today->copy()->subDays($i)->format('Y-m-d'),
                'md5' => '',
            ];
        }

        return $list;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapApiRow(array $row, string $date, string $md5): array
    {
        $rawId = (string) ($row['id'] ?? '');
        $company = trim((string) ($row['company'] ?? ''));
        $positions = trim((string) ($row['positions'] ?? ''));
        $batch = trim((string) ($row['batch'] ?? ''));
        $deadlineText = trim((string) ($row['deadline'] ?? ''));
        $sourceUrl = $this->safeUrl((string) ($row['sourceUrl'] ?? ''));
        $noticeUrl = $this->safeUrl((string) ($row['noticeUrl'] ?? ''));
        $applyUrl = $this->safeUrl((string) ($row['applyUrl'] ?? ''));

        $tags = array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            (array) ($row['typeTag'] ?? [])
        )));

        return [
            'source_id' => 'qzf-campus-'.($rawId !== '' ? $rawId : md5($company.'|'.$positions.'|'.$date)),
            'company' => $company,
            'title' => $positions,
            'work_location' => trim((string) ($row['locations'] ?? '')),
            'industry' => trim((string) ($row['industry'] ?? '')),
            'positions' => $positions,
            'channel' => str_contains($batch, '实习') ? '校招-实习' : '校招',
            'batch' => $batch,
            'is_hot' => (int) ($row['popular'] ?? 0) >= 2,
            'apply_url' => $applyUrl ?: $sourceUrl ?: $noticeUrl,
            'announcement_url' => $noticeUrl,
            'source_tags' => $tags !== [] ? $tags : null,
            'deadline' => $this->parseDateTime($deadlineText),
            'apply_deadline_text' => $deadlineText !== '' ? $deadlineText : null,
            'remarks' => trim(implode('；', array_filter([
                $date !== '' ? '更新日期: '.$date : null,
                $md5 !== '' ? '源MD5: '.$md5 : null,
                isset($row['urlType']) ? '来源类型: '.(string) $row['urlType'] : null,
            ]))),
            'raw_payload' => $row,
            'source_created_at' => $this->parseDateTime((string) ($row['createTime'] ?? '')),
            'source_updated_at' => $this->parseDateTime((string) ($row['createTime'] ?? '')),
            'recruitment_type' => $this->inferRecruitmentType($batch.' '.$positions),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function saveItem(array $item, bool $autoApprove): void
    {
        $attrs = [
            'source_id' => (string) $item['source_id'],
            'source_name' => 'qiuzhifangzhou-campus',
            'source_url' => 'https://www.qiuzhifangzhou.com/campus',
            'announcement_url' => $item['announcement_url'],
            'company' => (string) $item['company'],
            'title' => (string) $item['title'],
            'work_location' => (string) $item['work_location'],
            'industry' => (string) $item['industry'],
            'positions' => (string) $item['positions'],
            'channel' => (string) $item['channel'],
            'batch' => (string) $item['batch'],
            'is_hot' => (bool) $item['is_hot'],
            'apply_url' => $item['apply_url'],
            'source_tags' => $item['source_tags'],
            'deadline' => $item['deadline'],
            'apply_deadline_text' => $item['apply_deadline_text'],
            'remarks' => (string) $item['remarks'],
            'source_created_at' => $item['source_created_at'] ?? null,
            'source_updated_at' => $item['source_updated_at'] ?? null,
            'raw_payload' => $item['raw_payload'],
            'imported_at' => now(),
            'recruitment_type' => (string) $item['recruitment_type'],
        ];

        $existing = ExternalRecruitment::query()->where('source_id', $attrs['source_id'])->first();
        if ($existing) {
            if ($autoApprove) {
                $attrs['review_status'] = ExternalRecruitment::REVIEW_APPROVED;
                $attrs['reviewed_at'] = now();
            }
            $existing->update($attrs);
            $this->updated++;

            return;
        }

        $attrs['review_status'] = $autoApprove
            ? ExternalRecruitment::REVIEW_APPROVED
            : ExternalRecruitment::REVIEW_PENDING;
        $attrs['reviewed_at'] = $autoApprove ? now() : null;

        ExternalRecruitment::create($attrs);
        $this->created++;
    }

    private function parseDateTime(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
    }

    private function safeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return 'https://www.qiuzhifangzhou.com'.$url;
        }

        return null;
    }

    private function inferRecruitmentType(string $text): string
    {
        $normalized = mb_strtolower($text);
        if (str_contains($normalized, '社招') || str_contains($normalized, '社会招聘')) {
            return ExternalRecruitment::TYPE_SOCIAL;
        }

        return ExternalRecruitment::TYPE_CAMPUS;
    }
}
