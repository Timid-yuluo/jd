@extends('layouts.user')

@section('title', '招聘推荐')

@section('page-pretitle', '岗位推荐')
@section('page-title', '招聘推荐')

@section('page-actions')
<div class="btn-list">
    <a href="{{ route('user.jobs.bookmarks') }}" class="btn btn-outline-warning">
        <i class="ti ti-star me-2"></i>我的收藏
    </a>
    <a href="{{ route('user.jobs.analyze') }}" class="btn btn-outline-primary">
        <i class="ti ti-sparkles me-2"></i>岗位匹配分析
    </a>
    <a href="{{ route('user.membership.pricing') }}" class="btn btn-primary">
        <i class="ti ti-crown me-2"></i>查看会员套餐
    </a>
</div>
@endsection

@section('content')
<div class="row row-cards mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar bg-primary-lt text-primary me-3">
                        <i class="ti ti-briefcase fs-2"></i>
                    </div>
                    <div>
                        <div class="text-secondary">可用岗位</div>
                        <div class="h2 mb-0">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar bg-success-lt text-success me-3">
                        <i class="ti ti-calendar fs-2"></i>
                    </div>
                    <div>
                        <div class="text-secondary">今日更新</div>
                        <div class="h2 mb-0 text-success">{{ $stats['today'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar bg-warning-lt text-warning me-3">
                        <i class="ti ti-category fs-2"></i>
                    </div>
                    <div>
                        <div class="text-secondary">行业覆盖</div>
                        <div class="h2 mb-0 text-warning">{{ $stats['industry_count'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar bg-info-lt text-info me-3">
                        <i class="ti ti-map-pin fs-2"></i>
                    </div>
                    <div>
                        <div class="text-secondary">地点覆盖</div>
                        <div class="h2 mb-0 text-info">{{ $stats['location_count'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <ul class="nav nav-pills gap-2">
            @foreach($typeTabs as $tabKey => $tab)
                <li class="nav-item">
                    <a href="{{ route('user.kanban.index', array_merge(request()->query(), ['recruitment_type' => $tabKey, 'page' => 1])) }}"
                       class="nav-link {{ $recruitmentType === $tabKey ? 'active' : '' }}">
                        {{ $tab['label'] }}（{{ $tab['count'] }}）
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">筛选岗位</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('user.kanban.index') }}" class="row g-2">
            <input type="hidden" name="recruitment_type" value="{{ $recruitmentType }}">
            <div class="col-md-3">
                <input type="text" class="form-control" name="keyword" value="{{ $keyword }}" placeholder="公司 / 岗位关键词">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="industry" value="{{ $industry }}" placeholder="行业">
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="location" value="{{ $location }}" placeholder="地点">
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select">
                    @foreach($sortOptions as $sortValue => $sortLabel)
                        <option value="{{ $sortValue }}" {{ $sort === $sortValue ? 'selected' : '' }}>{{ $sortLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <select name="per_page" class="form-select">
                    @foreach($allowedPerPage as $pageSize)
                        <option value="{{ $pageSize }}" {{ $perPage === $pageSize ? 'selected' : '' }}>{{ $pageSize }}/页</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-outline-primary">筛选</button>
            </div>
        </form>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="{{ route('user.kanban.index', ['recruitment_type' => $recruitmentType]) }}" class="btn btn-sm btn-outline-secondary">清空筛选</a>
            @foreach($hotIndustries as $hotIndustry)
                <a href="{{ route('user.kanban.index', array_merge(request()->query(), ['industry' => $hotIndustry, 'page' => 1])) }}"
                   class="btn btn-sm {{ $industry === $hotIndustry ? 'btn-primary' : 'btn-outline-primary' }}">
                    行业: {{ $hotIndustry }}
                </a>
            @endforeach
            @foreach($hotLocations as $hotLocation)
                <a href="{{ route('user.kanban.index', array_merge(request()->query(), ['location' => $hotLocation, 'page' => 1])) }}"
                   class="btn btn-sm {{ $location === $hotLocation ? 'btn-info' : 'btn-outline-info' }}">
                    城市: {{ $hotLocation }}
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-secondary small">
        共 {{ $recruitments->total() }} 条，当前显示 {{ $recruitments->firstItem() ?? 0 }}-{{ $recruitments->lastItem() ?? 0 }} 条
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>公司</th>
                    <th>岗位</th>
                    <th>类型</th>
                    <th>地点</th>
                    <th>行业</th>
                    <th>更新时间</th>
                    <th class="text-end">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recruitments as $item)
                    <tr>
                        <td class="fw-bold">{{ $item->company ?: '未命名公司' }}</td>
                        <td>{{ $item->title ?: ($item->positions ?: '未命名岗位') }}</td>
                        <td>
                            @php
                                $isCampus = $item->recruitment_type === 'campus'
                                    || str_contains((string) $item->channel, '校招')
                                    || str_contains((string) $item->title, '应届')
                                    || str_contains((string) $item->positions, '应届');
                            @endphp
                            <span class="badge {{ $isCampus ? 'bg-primary-lt text-primary' : 'bg-success-lt text-success' }}">
                                {{ $isCampus ? '校招' : '社招' }}
                            </span>
                        </td>
                        <td><i class="ti ti-map-pin me-1"></i>{{ $item->work_location ?: '-' }}</td>
                        <td>{{ $item->industry ?: '-' }}</td>
                        <td class="small text-secondary">{{ $item->imported_at?->format('Y-m-d H:i') ?: '-' }}</td>
                        <td class="text-end">
                            @php
                                $isQzfSource = str_starts_with((string) $item->source_name, 'qiuzhifangzhou-');
                                $isQzfUrl = static function (?string $url): bool {
                                    $url = trim((string) $url);
                                    if ($url === '') {
                                        return false;
                                    }
                                    $host = parse_url($url, PHP_URL_HOST);
                                    if (!is_string($host) || $host === '') {
                                        return false;
                                    }
                                    $host = mb_strtolower($host);
                                    return $host === 'qiuzhifangzhou.com'
                                        || $host === 'www.qiuzhifangzhou.com'
                                        || $host === 'api.qiuzhifangzhou.com'
                                        || str_ends_with($host, '.qiuzhifangzhou.com');
                                };
                                $applyLink = $item->apply_url ?: null;
                                $announcementLink = $item->announcement_url ?: null;
                                $sourceLink = $item->source_url ?: null;
                                if ($isQzfSource) {
                                    if ($applyLink !== null && $isQzfUrl($applyLink)) {
                                        $applyLink = null;
                                    }
                                    if ($announcementLink !== null && $isQzfUrl($announcementLink)) {
                                        $announcementLink = null;
                                    }
                                }
                                if ($applyLink === null) {
                                    $applyLink = $announcementLink ?: ($isQzfSource ? null : $sourceLink);
                                }
                            @endphp
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-warning btn-bookmark-kanban" title="收藏岗位"
                                    data-id="{{ $item->id }}"
                                    data-title="{{ $item->title ?: ($item->positions ?: '') }}"
                                    data-company="{{ $item->company ?: '' }}"
                                    data-jd="{{ base64_encode($item->job_description ?: $item->description ?: '') }}">
                                    <i class="ti ti-star me-1"></i>收藏
                                </button>
                                <a href="{{ route('user.jobs.analyze', ['from_external' => $item->id]) }}" class="btn btn-outline-primary" title="带入JD分析">
                                    <i class="ti ti-sparkles me-1"></i>分析
                                </a>
                                @if($applyLink)
                                    <a href="{{ $applyLink }}" target="_blank" rel="noopener" class="btn btn-outline-secondary" title="去投递">
                                        <i class="ti ti-external-link me-1"></i>投递
                                    </a>
                                @endif
                                @if($announcementLink && $announcementLink !== $applyLink)
                                    <a href="{{ $announcementLink }}" target="_blank" rel="noopener" class="btn btn-outline-secondary" title="查看公告">
                                        <i class="ti ti-bell me-1"></i>公告
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            暂无可展示的招聘推荐数据，请稍后再试。
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $recruitments->links() }}
</div>

<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-bookmark-kanban').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var title = this.dataset.title;
            var company = this.dataset.company;
            var jdBase64 = this.dataset.jd;
            var jd = '';
            try { jd = decodeURIComponent(escape(atob(jdBase64))); } catch(e) { jd = atob(jdBase64); }

            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            var self = this;

            self.disabled = true;
            self.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>收藏中';

            fetch('{{ route("user.jobs.bookmarks.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    title: title,
                    company: company,
                    job_description: jd,
                    external_recruitment_id: parseInt(id)
                })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    self.classList.remove('btn-outline-warning');
                    self.classList.add('btn-warning');
                    self.innerHTML = '<i class="ti ti-star-filled me-1"></i>已收藏';
                    self.disabled = true;
                } else {
                    self.disabled = false;
                    self.innerHTML = '<i class="ti ti-star me-1"></i>收藏';
                    alert(data.message || '收藏失败');
                }
            })
            .catch(function() {
                self.disabled = false;
                self.innerHTML = '<i class="ti ti-star me-1"></i>收藏';
                alert('网络错误，请重试');
            });
        });
    });
});
</script>
@endsection
