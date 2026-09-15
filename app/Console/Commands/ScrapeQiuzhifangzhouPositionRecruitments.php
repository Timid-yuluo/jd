<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ExternalRecruitment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ScrapeQiuzhifangzhouPositionRecruitments extends Command
{
    protected $signature = 'scrape:qiuzhifangzhou-position
        {--timeout=30 : HTTP 超时秒数}
        {--auto-approve=0 : 1=自动审核通过，0=待审核}
        {--backfill-existing=1 : 是否回填历史空投递链接（1=是，0=否）}';

    protected $description = '从 qiuzhifangzhou 的职位流接口抓取岗位并按规则区分校招/社招';

    private int $created = 0;

    private int $updated = 0;

    private int $failed = 0;

    public function handle(): int
    {
        $timeout = (int) $this->option('timeout');
        $autoApprove = (int) $this->option('auto-approve') === 1;
        $backfillExisting = (int) $this->option('backfill-existing') === 1;

        $this->info('开始抓取 qiuzhifangzhou 职位流信息...');

        try {
            $items = $this->fetchFromApi($timeout);
            if ($items === []) {
                $this->warn('接口返回为空，未获取到可导入岗位。');
            }

            foreach ($items as $item) {
                $this->saveItem($item, $autoApprove);
            }
        } catch (\Throwable $e) {
            $this->error('抓取失败: '.$e->getMessage());
            try {
                Log::warning('scrape:qiuzhifangzhou-position failed', ['error' => $e->getMessage()]);
            } catch (\Throwable) {
                // 日志目录无写权限时忽略，避免影响命令主流程返回
            }

            return self::FAILURE;
        }

        $this->info("抓取完成！创建: {$this->created}, 更新: {$this->updated}, 失败: {$this->failed}");

        if ($backfillExisting) {
            $backfilled = $this->backfillExistingApplyUrls();
            $this->info("历史空投递链接回填完成: {$backfilled} 条");
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchFromApi(int $timeout): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
            'Origin' => 'https://www.qiuzhifangzhou.com',
            'Referer' => 'https://www.qiuzhifangzhou.com/social',
        ])
            ->timeout($timeout)
            ->connectTimeout(10)
            ->retry(2, 1000)
            ->post('https://api.qiuzhifangzhou.com/api/position/initSearch', (object) []);

        if (! $response->successful()) {
            throw new \RuntimeException('HTTP '.$response->status());
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return [];
        }

        $items = [];
        foreach ((array) ($payload['positions'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $items[] = $this->mapApiRow($row);
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapApiRow(array $row): array
    {
        $rawId = (string) ($row['id'] ?? '');
        $company = trim((string) ($row['company'] ?? ''));
        $title = trim((string) ($row['title'] ?? ''));
        $direction = trim((string) ($row['direction'] ?? ''));
        $sourceType = trim((string) ($row['sourceType'] ?? ''));
        $location = implode(' / ', array_values(array_filter(array_map(
            static fn (mixed $x): string => trim((string) $x),
            (array) ($row['location'] ?? [])
        ))));
        $major = implode(' / ', array_values(array_filter(array_map(
            static fn (mixed $x): string => trim((string) $x),
            (array) ($row['major'] ?? [])
        ))));
        $tags = array_values(array_filter(array_map(
            static fn (mixed $x): string => trim((string) $x),
            (array) ($row['matchTag'] ?? [])
        )));
        $companyTags = array_values(array_filter(array_map(
            static fn (mixed $x): string => trim((string) $x),
            (array) ($row['companyTag'] ?? [])
        )));
        $graduationYears = array_map(static fn (mixed $x): int => (int) $x, (array) ($row['graduation'] ?? []));
        $graduationYears = array_values(array_filter($graduationYears, static fn (int $x): bool => $x > 0));
        $recruitmentType = $this->inferRecruitmentType($title, $direction, $graduationYears);
        $detailUrl = $this->buildPositionDetailUrl($rawId);
        $links = $this->resolveDirectLinks($row);

        return [
            'source_id' => 'qzf-position-'.($rawId !== '' ? $rawId : md5($company.'|'.$title)),
            'company' => $company,
            'title' => $title !== '' ? $title : $direction,
            'work_location' => $location,
            'industry' => trim((string) ($row['industry'] ?? '')),
            'positions' => trim(implode('；', array_filter([
                $direction !== '' ? '方向: '.$direction : null,
                $major !== '' ? '专业: '.$major : null,
                ! empty($graduationYears) ? '毕业届别: '.implode(',', $graduationYears) : null,
            ]))),
            'channel' => $sourceType !== '' ? $sourceType : 'position',
            'batch' => ! empty($graduationYears) ? implode(',', $graduationYears).'届' : null,
            'is_hot' => ((int) ($row['matchDegree'] ?? 0)) >= 85,
            'apply_url' => $links['apply_url'],
            'announcement_url' => $links['announcement_url'],
            'source_tags' => $tags !== [] ? array_values(array_unique(array_merge($tags, $companyTags))) : ($companyTags !== [] ? $companyTags : null),
            'deadline' => null,
            'apply_deadline_text' => null,
            'remarks' => trim(implode('；', array_filter([
                isset($row['postingDate']) ? '发布时间: '.(string) $row['postingDate'] : null,
                isset($row['degree']) ? '学历: '.(string) $row['degree'] : null,
                $sourceType !== '' ? '来源类型: '.$sourceType : null,
                $detailUrl !== null ? '方舟详情: '.$detailUrl : null,
            ]))),
            'raw_payload' => $row,
            'source_created_at' => isset($row['updateDate']) ? $this->parseDateTime((string) $row['updateDate']) : null,
            'source_updated_at' => isset($row['updateDate']) ? $this->parseDateTime((string) $row['updateDate']) : null,
            'recruitment_type' => $recruitmentType,
        ];
    }

    /**
     * @param  array<int, int>  $graduationYears
     */
    private function inferRecruitmentType(string $title, string $direction, array $graduationYears): string
    {
        $text = mb_strtolower($title.' '.$direction);
        if (
            str_contains($text, '校招')
            || str_contains($text, '应届')
            || str_contains($text, '实习')
            || str_contains($text, '毕业生')
            || str_contains($text, '管培生')
        ) {
            return ExternalRecruitment::TYPE_CAMPUS;
        }
        if (str_contains($text, '社招') || str_contains($text, '社会招聘')) {
            return ExternalRecruitment::TYPE_SOCIAL;
        }

        return ExternalRecruitment::TYPE_SOCIAL;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function saveItem(array $item, bool $autoApprove): void
    {
        $attrs = [
            'source_id' => (string) $item['source_id'],
            'source_name' => 'qiuzhifangzhou-position',
            'source_url' => 'https://www.qiuzhifangzhou.com/social',
            'announcement_url' => $item['announcement_url'],
            'company' => (string) $item['company'],
            'title' => (string) $item['title'],
            'work_location' => (string) $item['work_location'],
            'industry' => (string) $item['industry'],
            'positions' => (string) $item['positions'],
            'channel' => (string) $item['channel'],
            'batch' => $item['batch'],
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

    private function buildPositionDetailUrl(string $positionId): ?string
    {
        $id = trim($positionId);
        if ($id === '') {
            return null;
        }

        return 'https://www.qiuzhifangzhou.com/social?positionId='.urlencode($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{apply_url: ?string, announcement_url: ?string}
     */
    private function resolveDirectLinks(array $payload): array
    {
        /** @var array<int, array{key: string, url: string}> $urlPairs */
        $urlPairs = [];
        $this->collectUrlPairs($payload, '', $urlPairs);

        $applyUrl = null;
        $announcementUrl = null;
        foreach ($urlPairs as $pair) {
            $key = mb_strtolower($pair['key']);
            $url = $pair['url'];
            if ($this->isQzfDomainUrl($url)) {
                continue;
            }

            if ($announcementUrl === null && $this->isAnnouncementKey($key)) {
                $announcementUrl = $url;
            }

            if ($applyUrl === null && $this->isApplyKey($key)) {
                $applyUrl = $url;
            }
        }

        if ($applyUrl === null && $announcementUrl !== null) {
            $applyUrl = $announcementUrl;
        }

        return [
            'apply_url' => $applyUrl,
            'announcement_url' => $announcementUrl,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @param  array<int, array{key: string, url: string}>  $urlPairs
     */
    private function collectUrlPairs(array $data, string $prefix, array &$urlPairs): void
    {
        foreach ($data as $key => $value) {
            $field = $prefix.(string) $key;
            if (is_array($value)) {
                $this->collectUrlPairs($value, $field.'.', $urlPairs);

                continue;
            }
            if (! is_string($value)) {
                continue;
            }
            $url = $this->safeUrl($value);
            if ($url === null) {
                continue;
            }
            $urlPairs[] = [
                'key' => $field,
                'url' => $url,
            ];
        }
    }

    private function isAnnouncementKey(string $key): bool
    {
        return str_contains($key, 'notice')
            || str_contains($key, 'announce')
            || str_contains($key, 'gonggao')
            || str_contains($key, 'public');
    }

    private function isApplyKey(string $key): bool
    {
        return str_contains($key, 'apply')
            || str_contains($key, 'deliver')
            || str_contains($key, 'resume')
            || str_contains($key, 'position')
            || str_ends_with($key, 'url')
            || str_contains($key, 'link');
    }

    private function isQzfDomainUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = mb_strtolower($host);

        return $host === 'qiuzhifangzhou.com'
            || $host === 'www.qiuzhifangzhou.com'
            || $host === 'api.qiuzhifangzhou.com'
            || str_ends_with($host, '.qiuzhifangzhou.com');
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

    private function backfillExistingApplyUrls(): int
    {
        $updated = 0;

        ExternalRecruitment::query()
            ->where('source_name', 'qiuzhifangzhou-position')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$updated): void {
                foreach ($rows as $row) {
                    $payload = is_array($row->raw_payload) ? $row->raw_payload : [];
                    $links = $this->resolveDirectLinks($payload);
                    $nextApplyUrl = $links['apply_url'];
                    $nextAnnouncementUrl = $links['announcement_url'];

                    $shouldUpdateApply = $nextApplyUrl !== null
                        && (
                            (string) $row->apply_url === ''
                            || $this->isQzfDomainUrl((string) $row->apply_url)
                        );
                    $shouldUpdateAnnouncement = $nextAnnouncementUrl !== null
                        && (
                            (string) $row->announcement_url === ''
                            || $this->isQzfDomainUrl((string) $row->announcement_url)
                        );

                    if (! $shouldUpdateApply && ! $shouldUpdateAnnouncement) {
                        continue;
                    }
                    $attrs = [];
                    if ($shouldUpdateApply) {
                        $attrs['apply_url'] = $nextApplyUrl;
                    }
                    if ($shouldUpdateAnnouncement) {
                        $attrs['announcement_url'] = $nextAnnouncementUrl;
                    }
                    if ($attrs === []) {
                        continue;
                    }
                    $row->update($attrs);
                    $updated++;
                }
            });

        return $updated;
    }
}
