@extends('layouts.user')

@section('title', 'ATS 评分报告 - ' . $resume->title)

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <a href="{{ route('user.resumes.show', $resume) }}" class="btn btn-link text-secondary p-0">
                        <i class="ti ti-arrow-left me-1"></i>返回
                    </a>
                </div>
                <h2 class="page-title">ATS 评分报告</h2>
                <div class="mt-1 text-secondary">{{ $resume->title }}</div>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-outline-primary" data-print>
                    <i class="ti ti-printer me-1"></i>打印报告
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div id="ats-loading-overlay" style="display:none;">
        <div class="ats-loading-card">
            <div class="ats-spinner"></div>
            <div class="ats-loading-text">正在分析你的简历</div>
            <div class="ats-loading-sub">六维评分 · 分模块评分 · 改进建议</div>
            <div class="ats-loading-bar"><div class="ats-loading-bar-fill"></div></div>
        </div>
    </div>

    <style>
    #ats-loading-overlay{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;opacity:0;animation:atsFadeIn .2s ease forwards}
    @keyframes atsFadeIn{to{opacity:1}}
    .ats-loading-card{background:var(--tblr-bg-surface,#fff);border-radius:14px;padding:40px 48px;text-align:center;box-shadow:0 16px 48px rgba(0,0,0,.2);transform:translateY(10px);animation:atsSlideUp .3s .1s ease forwards}
    @keyframes atsSlideUp{to{transform:translateY(0)}}
    .ats-spinner{width:48px;height:48px;margin:0 auto 20px;border:4px solid var(--tblr-border-color,#e5e7eb);border-top-color:#2563eb;border-radius:50%;animation:atsSpin .8s linear infinite}
    @keyframes atsSpin{to{transform:rotate(360deg)}}
    .ats-loading-text{font-size:16px;font-weight:600;color:var(--tblr-body-color,#111827);margin-bottom:6px}
    .ats-loading-sub{font-size:12px;color:var(--tblr-text-muted,#9ca3af);margin-bottom:20px}
    .ats-loading-bar{width:200px;height:4px;background:var(--tblr-border-color,#e5e7eb);border-radius:2px;overflow:hidden;margin:0 auto}
    .ats-loading-bar-fill{width:30%;height:100%;background:#2563eb;border-radius:2px;animation:atsBarMove 2s ease-in-out infinite}
    @keyframes atsBarMove{0%{width:10%;margin-left:0}50%{width:40%;margin-left:30%}100%{width:10%;margin-left:90%}}
    </style>

    <div class="container-xl">
        @php
            $score = is_numeric($resume->ats_score) ? (int) $resume->ats_score : null;
            $atsMeta = is_array($resume->content_structured ?? null) && is_array($resume->content_structured['ats'] ?? null) ? $resume->content_structured['ats'] : [];
            $level = $atsMeta['level'] ?? ($score === null ? '未评分' : ($score >= 85 ? '优秀' : ($score >= 72 ? '良好' : ($score >= 60 ? '合格' : '待提升'))));
            $summary = $atsMeta['summary'] ?? '';
            $scoreColor = $score === null ? 'secondary' : ($score >= 85 ? 'success' : ($score >= 72 ? 'info' : ($score >= 60 ? 'warning' : 'danger')));
            $levelColor = $scoreColor;

            $defaultBreakdown = [
                'keyword_match' => ['label' => '关键词与硬技能匹配', 'score' => 0, 'max_score' => 20, 'percent' => 0, 'deduction_reason' => '暂无分项数据，请先进行 ATS 评分。'],
                'quantified_results' => ['label' => '量化成果与数据支撑', 'score' => 0, 'max_score' => 20, 'percent' => 0, 'deduction_reason' => '暂无分项数据，请先进行 ATS 评分。'],
                'star_structure' => ['label' => 'STAR结构与逻辑清晰度', 'score' => 0, 'max_score' => 15, 'percent' => 0, 'deduction_reason' => '暂无分项数据，请先进行 ATS 评分。'],
                'professional_format' => ['label' => '专业表达与格式规范', 'score' => 0, 'max_score' => 15, 'percent' => 0, 'deduction_reason' => '暂无分项数据，请先进行 ATS 评分。'],
                'relevance_focus' => ['label' => '内容相关性与聚焦度', 'score' => 0, 'max_score' => 15, 'percent' => 0, 'deduction_reason' => '暂无分项数据，请先进行 ATS 评分。'],
                'competitive_edge' => ['label' => '竞争力与差异化亮点', 'score' => 0, 'max_score' => 15, 'percent' => 0, 'deduction_reason' => '暂无分项数据，请先进行 ATS 评分。'],
            ];
            $breakdownItems = !empty($breakdown) ? array_merge($defaultBreakdown, $breakdown) : $defaultBreakdown;

            $defaultSuggestions = [
                ['dimension' => '', 'priority' => 'high', 'text' => '补齐目标岗位的核心关键词，并在项目/经历中自然体现关键技术栈。'],
                ['dimension' => '', 'priority' => 'high', 'text' => '用可量化的结果重写项目成果，例如性能提升、效率提升、业务指标增长。'],
                ['dimension' => '', 'priority' => 'medium', 'text' => '减少与目标岗位无关的内容，突出最相关的经历和能力。'],
                ['dimension' => '', 'priority' => 'medium', 'text' => '将经历描述重构为 STAR 结构，增强逻辑性与可读性。'],
            ];
            $suggestionItems = !empty($suggestions) ? $suggestions : $defaultSuggestions;
            $displayTargetJob = filled($scoredTargetJob ?? null) ? $scoredTargetJob : $resume->target_job;

            $dimensionIcons = [
                'keyword_match' => 'ti-tag',
                'quantified_results' => 'ti-chart-bar',
                'star_structure' => 'ti-list-numbers',
                'professional_format' => 'ti-spellcheck',
                'relevance_focus' => 'ti-target',
                'competitive_edge' => 'ti-trophy',
            ];

            $dimensionShortLabels = [
                'keyword_match' => '关键词',
                'quantified_results' => '量化成果',
                'star_structure' => 'STAR结构',
                'professional_format' => '专业表达',
                'relevance_focus' => '内容聚焦',
                'competitive_edge' => '竞争力',
            ];
        @endphp

        @if($isOutdated)
            @php
                $outdatedDays = $scoredAt ? max(0, (int) $scoredAt->diffInDays(now())) : null;
            @endphp
            <div class="alert alert-warning mb-4">
                <div class="d-flex">
                    <div><i class="ti ti-alert-triangle me-2"></i></div>
                    <div>
                        该 ATS 报告生成后你已更新过简历内容
                        @if($outdatedDays !== null && $outdatedDays > 0)
                            （{{ $outdatedDays }} 天前）
                        @endif
                        ，建议重新执行 ATS 评分以获得最新结果。
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body text-center py-4">
                        <div class="display-1 fw-bold text-{{ $scoreColor }}">{{ $score ?? 'N/A' }}</div>
                        <div class="text-secondary mb-2">/ 100</div>
                        <span class="badge bg-{{ $levelColor }} fs-6 mb-3">{{ $level }}</span>
                        @if($summary)
                            <div class="text-secondary small mt-2 px-3">{{ $summary }}</div>
                        @endif
                        @if($percentile !== null && $score !== null)
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <i class="ti ti-users text-primary"></i>
                                    <span class="small">你超过了 <strong class="text-primary">{{ $percentile }}%</strong> 的求职者</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if(count($history) > 1)
                @php
                    $first = (int) ($history[0]['score'] ?? 0);
                    $last = (int) (end($history)['score'] ?? 0);
                    $delta = $last - $first;
                    $deltaIcon = $delta > 0 ? 'ti-trending-up text-success' : ($delta < 0 ? 'ti-trending-down text-danger' : 'ti-minus text-secondary');
                    $deltaText = $delta > 0 ? "+{$delta}" : ($delta < 0 ? "{$delta}" : '0');
                @endphp
                <div class="card mt-4">
                    <div class="card-header cursor-pointer" data-bs-toggle="collapse" data-bs-target="#trendCollapse" aria-expanded="false" style="cursor:pointer;">
                        <h3 class="card-title">
                            <i class="ti {{ $deltaIcon }} me-2"></i>分数趋势
                            <span class="badge bg-secondary-lt ms-2" style="font-size:.7rem;">{{ count($history) }} 次</span>
                        </h3>
                        <div class="card-actions">
                            <span class="text-secondary small me-2">累计 {{ $delta > 0 ? '+' : '' }}{{ $delta }} 分</span>
                            <i class="ti ti-chevron-down"></i>
                        </div>
                    </div>
                    <div id="trendCollapse" class="collapse">
                        <div class="card-body">
                            <div style="position:relative;height:160px;">
                                <canvas id="trendChart"></canvas>
                            </div>
                            <div class="mt-3">
                                <div class="table-responsive">
                                    <table class="table table-sm table-vcenter mb-0" style="font-size:.8rem;">
                                        <thead>
                                            <tr>
                                                <th class="text-secondary">#</th>
                                                <th class="text-secondary">时间</th>
                                                <th class="text-secondary">分数</th>
                                                <th class="text-secondary">等级</th>
                                                <th class="text-secondary">变化</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(array_reverse($history) as $i => $h)
                                                @php
                                                    $hScore = (int) ($h['score'] ?? 0);
                                                    $hLevel = $hScore >= 85 ? '优秀' : ($hScore >= 72 ? '良好' : ($hScore >= 60 ? '合格' : '待提升'));
                                                    $hColor = $hScore >= 85 ? 'success' : ($hScore >= 72 ? 'info' : ($hScore >= 60 ? 'warning' : 'danger'));
                                                    $hDate = isset($h['scored_at']) ? \Illuminate\Support\Carbon::parse($h['scored_at'])->format('m/d H:i') : '';
                                                    $prevIdx = count($history) - $i - 2;
                                                    $prevScore = $prevIdx >= 0 ? (int) ($history[$prevIdx]['score'] ?? 0) : null;
                                                    $hDelta = $prevScore !== null ? $hScore - $prevScore : null;
                                                @endphp
                                                <tr>
                                                    <td class="text-secondary">{{ count($history) - $i }}</td>
                                                    <td>{{ $hDate }}</td>
                                                    <td><strong class="text-{{ $hColor }}">{{ $hScore }}</strong></td>
                                                    <td><span class="badge bg-{{ $hColor }}-lt" style="font-size:.65rem;">{{ $hLevel }}</span></td>
                                                    <td>
                                                        @if($hDelta !== null)
                                                            @if($hDelta > 0)
                                                                <span class="text-success">+{{ $hDelta }}</span>
                                                            @elseif($hDelta < 0)
                                                                <span class="text-danger">{{ $hDelta }}</span>
                                                            @else
                                                                <span class="text-secondary">-</span>
                                                            @endif
                                                        @else
                                                            <span class="text-secondary">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">评分等级说明</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-secondary">85-100</span>
                                <span class="text-success fw-medium">优秀</span>
                            </div>
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar bg-success" style="width: 85%"></div>
                            </div>
                            <div class="text-muted small mt-1">具备强竞争力，可直接投递头部企业</div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-secondary">72-84</span>
                                <span class="text-info fw-medium">良好</span>
                            </div>
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar bg-info" style="width: 72%"></div>
                            </div>
                            <div class="text-muted small mt-1">整体合格，部分维度有提升空间</div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-secondary">60-71</span>
                                <span class="text-warning fw-medium">合格</span>
                            </div>
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar bg-warning" style="width: 60%"></div>
                            </div>
                            <div class="text-muted small mt-1">达到基本门槛，建议优化后再投递</div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-secondary">0-59</span>
                                <span class="text-danger fw-medium">待提升</span>
                            </div>
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar bg-danger" style="width: 40%"></div>
                            </div>
                            <div class="text-muted small mt-1">存在明显短板，需要重点优化</div>
                        </div>
                    </div>
                </div>

                @if($displayTargetJob)
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">评分目标岗位</h3>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <i class="ti ti-briefcase text-secondary me-2"></i>
                                <span>{{ $displayTargetJob }}</span>
                            </div>
                            @php $usedKeywords = $atsMeta['used_jd_keywords'] ?? []; @endphp
                            @if(!empty($usedKeywords))
                                <div class="mt-2">
                                    <div class="small text-secondary mb-1">参考 JD 关键词：</div>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($usedKeywords as $kw)
                                            <span class="badge bg-primary-lt text-primary" style="font-size:.7rem;">{{ $kw }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if($scoredAt)
                                <div class="text-secondary small mt-2">评分时间：{{ $scoredAt->format('Y-m-d H:i') }}</div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- P1-4: 评分前后对比 --}}
                @if($previousAts !== null && $score !== null)
                    @php
                        $prevScore = (int) ($previousAts['score'] ?? 0);
                        $scoreDelta = $score - $prevScore;
                        $deltaIcon = $scoreDelta > 0 ? 'ti-arrow-up text-success' : ($scoreDelta < 0 ? 'ti-arrow-down text-danger' : 'ti-minus text-secondary');
                        $deltaLabel = $scoreDelta > 0 ? "+{$scoreDelta}" : ($scoreDelta < 0 ? "{$scoreDelta}" : '0');
                        $deltaBg = $scoreDelta > 0 ? 'bg-success-lt' : ($scoreDelta < 0 ? 'bg-danger-lt' : 'bg-secondary-lt');
                    @endphp
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-arrows-exchange me-1"></i>评分对比</h3>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-around text-center">
                                <div>
                                    <div class="text-secondary small mb-1">上次评分</div>
                                    <div class="h2 mb-0">{{ $prevScore }}</div>
                                </div>
                                <div>
                                    <span class="badge {{ $deltaBg }} fs-5 px-3 py-2">
                                        <i class="ti {{ $deltaIcon }} me-1"></i>{{ $deltaLabel }}
                                    </span>
                                </div>
                                <div>
                                    <div class="text-secondary small mb-1">本次评分</div>
                                    <div class="h2 mb-0 text-{{ $scoreColor }}">{{ $score }}</div>
                                </div>
                            </div>
                            @if(abs($scoreDelta) > 0 && !empty($breakdownItems))
                                <div class="mt-3 pt-3 border-top">
                                    <div class="small text-secondary mb-2">维度变化：</div>
                                    @foreach($breakdownItems as $dimKey => $dimItem)
                                        @php
                                            $curDimScore = (int) ($dimItem['score'] ?? 0);
                                            $dimMax = max(1, (int) ($dimItem['max_score'] ?? 1));
                                            $dimLabel = $dimensionShortLabels[$dimKey] ?? ($dimItem['label'] ?? $dimKey);
                                        @endphp
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small">{{ $dimLabel }}</span>
                                            <span class="small fw-medium">{{ $curDimScore }}/{{ $dimMax }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- P1-6: 岗位类别基准分 --}}
                @if($jobBenchmark !== null && $score !== null)
                    @php
                        $benchDiff = $score - $jobBenchmark['avg_score'];
                        $benchIcon = $benchDiff >= 0 ? 'ti-arrow-up text-success' : 'ti-arrow-down text-danger';
                        $benchLabel = $benchDiff >= 0 ? "+{$benchDiff}" : "{$benchDiff}";
                    @endphp
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-chart-dots-3 me-1"></i>同岗位基准</h3>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-primary-lt me-2">{{ $jobBenchmark['category'] }}</span>
                                <span class="text-secondary small">基于 {{ $jobBenchmark['total'] }} 份简历</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-around text-center">
                                <div>
                                    <div class="text-secondary small mb-1">你的分数</div>
                                    <div class="h3 mb-0 text-{{ $scoreColor }}">{{ $score }}</div>
                                </div>
                                <div>
                                    <i class="ti {{ $benchIcon }} fs-3"></i>
                                    <div class="small {{ $benchDiff >= 0 ? 'text-success' : 'text-danger' }}">{{ $benchLabel }}</div>
                                </div>
                                <div>
                                    <div class="text-secondary small mb-1">岗位平均</div>
                                    <div class="h3 mb-0">{{ $jobBenchmark['avg_score'] }}</div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small text-secondary mb-1">
                                    <span>{{ $jobBenchmark['min_score'] }}</span>
                                    <span>{{ $jobBenchmark['max_score'] }}</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    @php $avgPct = max(0, min(100, $jobBenchmark['avg_score'])); @endphp
                                    <div class="progress-bar bg-info" style="width: {{ $avgPct }}%"></div>
                                    @if($score > $jobBenchmark['avg_score'])
                                        <div class="position-absolute bg-{{ $scoreColor }}" style="left: {{ $score }}%; top: 0; bottom: 0; width: 3px; border-radius: 1px;"></div>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between small mt-1">
                                    <span class="text-secondary">最低</span>
                                    <span class="text-info">平均 {{ $jobBenchmark['avg_score'] }}</span>
                                    <span class="text-secondary">最高</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">下一步动作</h3>
                    </div>
                    <div class="card-body">
                        @php
                            $lowestModule = null;
                            $lowestPercent = 101;
                            if (!empty($moduleScores)) {
                                foreach ($moduleScores as $mKey => $mItem) {
                                    $mMax = max(1, (int) ($mItem['max_score'] ?? 10));
                                    $mPct = (int) round(((int) ($mItem['score'] ?? 0)) / $mMax * 100);
                                    if ($mPct < $lowestPercent) {
                                        $lowestPercent = $mPct;
                                        $lowestModule = $mKey;
                                    }
                                }
                            }
                            $lowestDimension = null;
                            $lowestDimPercent = 101;
                            foreach ($breakdownItems as $dKey => $dItem) {
                                $dMax = max(1, (int) ($dItem['max_score'] ?? 1));
                                $dPct = (int) round(((int) ($dItem['score'] ?? 0)) / $dMax * 100);
                                if ($dPct < $lowestDimPercent) {
                                    $lowestDimPercent = $dPct;
                                    $lowestDimension = $dKey;
                                }
                            }
                        @endphp
                        <div class="d-grid gap-2">
                            @if($lowestDimension)
                                <a href="{{ route('user.resumes.editor', $resume) }}#dim-{{ $lowestDimension }}" class="btn btn-warning w-100">
                                    <i class="ti ti-wand me-2"></i>一键优化低分项
                                    @if($lowestModule && isset($moduleScores[$lowestModule]))
                                        <span class="ms-1 small">({{ $moduleScores[$lowestModule]['label'] ?? '' }})</span>
                                    @endif
                                </a>
                            @endif
                            <form action="{{ route('user.resumes.ats-score', $resume) }}" method="POST" data-quota-loading-text="评分中...">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100 submit-btn" @if(!$isFirstScore && $dailyRemaining <= 0) disabled @endif>
                                    <i class="ti ti-refresh me-2"></i>{{ $isFirstScore ? '开始 ATS 评分' : '重新 ATS 评分' }}
                                    @if(!$isFirstScore)
                                        <span class="ms-1 small opacity-75">({{ $dailyRemaining > 0 ? "今日剩余 {$dailyRemaining} 次" : '今日次数已用完' }})</span>
                                    @endif
                                </button>
                            </form>
                            <a href="{{ route('user.resumes.optimize-view', $resume) }}" class="btn btn-outline-primary">
                                <i class="ti ti-sparkles me-2"></i>前往 AI 优化
                            </a>
                            <a href="{{ route('user.kanban.index') }}" class="btn btn-outline-success">
                                <i class="ti ti-briefcase me-2"></i>查看招聘推荐
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">六维评分明细</h3>
                        <div class="card-subtitle">总分 100 分，各维度独立评分</div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-5 text-center mb-3 mb-md-0">
                                <canvas id="radarChart" width="280" height="280"></canvas>
                            </div>
                            <div class="col-md-7">
                                <div class="row g-3">
                                    @foreach($breakdownItems as $key => $item)
                                        @php
                                            $dimensionScore = (int) ($item['score'] ?? 0);
                                            $dimensionMax = max(1, (int) ($item['max_score'] ?? 1));
                                            $dimensionPercent = (int) ($item['percent'] ?? round(($dimensionScore / $dimensionMax) * 100));
                                            $barColor = $dimensionPercent >= 80 ? 'success' : ($dimensionPercent >= 60 ? 'warning' : 'danger');
                                            $icon = $dimensionIcons[$key] ?? 'ti-chart-dots';
                                        @endphp
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="d-flex align-items-center">
                                                    <i class="ti {{ $icon }} text-{{ $barColor }} me-2"></i>
                                                    <span class="fw-medium small">{{ $item['label'] ?? '分项评分' }}</span>
                                                </div>
                                                <div class="text-{{ $barColor }} fw-bold">{{ $dimensionScore }}/{{ $dimensionMax }}</div>
                                            </div>
                                            <div class="progress mb-1" style="height: 6px;">
                                                <div class="progress-bar bg-{{ $barColor }}" style="width: {{ $dimensionPercent }}%"></div>
                                            </div>
                                            <div class="small text-secondary" style="line-height: 1.4;">{{ $item['deduction_reason'] ?? '暂无说明' }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if(!empty($moduleScores))
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">分模块评分</h3>
                        <div class="card-subtitle">各模块独立评分，精准定位薄弱环节</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @php
                                $moduleIcons = [
                                    'education' => 'ti-school',
                                    'experience' => 'ti-briefcase',
                                    'project' => 'ti-code',
                                    'skill' => 'ti-certificate',
                                    'certificate' => 'ti-award',
                                ];
                            @endphp
                            @foreach($moduleScores as $mKey => $mItem)
                                @php
                                    $mScore = (int) ($mItem['score'] ?? 0);
                                    $mMax = max(1, (int) ($mItem['max_score'] ?? 10));
                                    $mPercent = (int) round(($mScore / $mMax) * 100);
                                    $mColor = $mPercent >= 80 ? 'success' : ($mPercent >= 60 ? 'warning' : 'danger');
                                    $mIcon = $moduleIcons[$mKey] ?? 'ti-file';
                                @endphp
                                <div class="col-md-4 col-sm-6">
                                    <div class="border rounded p-3 text-center h-100">
                                        <i class="ti {{ $mIcon }} text-{{ $mColor }} mb-2" style="font-size: 1.5rem;"></i>
                                        <div class="fw-medium small mb-1">{{ $mItem['label'] ?? $mKey }}</div>
                                        <div class="h3 mb-1 text-{{ $mColor }}">{{ $mScore }}<span class="text-secondary small">/{{ $mMax }}</span></div>
                                        <div class="progress mb-2" style="height: 4px;">
                                            <div class="progress-bar bg-{{ $mColor }}" style="width: {{ $mPercent }}%"></div>
                                        </div>
                                        @if(!empty($mItem['comment']))
                                            <div class="small text-secondary" style="font-size: .7rem; line-height: 1.4;">{{ $mItem['comment'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">AI 改进建议</h3>
                        <div class="card-subtitle">按优先级排序，建议优先处理高优先级项</div>
                    </div>
                    <div class="card-body">
                        @php
                            $sortedSuggestions = collect($suggestionItems)->sortBy(function ($s) {
                                return $s['priority'] === 'high' ? 0 : ($s['priority'] === 'medium' ? 1 : 2);
                            })->values();

                            $dimensionToModule = [
                                'keyword_match' => 'objective',
                                'quantified_results' => 'experience',
                                'star_structure' => 'experience',
                                'professional_format' => 'personal',
                                'relevance_focus' => 'experience',
                                'competitive_edge' => 'project',
                            ];
                        @endphp
                        <div class="list-group list-group-flush">
                            @foreach($sortedSuggestions as $index => $suggestion)
                                @php
                                    $sugText = is_array($suggestion) ? ($suggestion['text'] ?? '') : (string) $suggestion;
                                    $sugPriority = is_array($suggestion) ? ($suggestion['priority'] ?? 'medium') : 'medium';
                                    $sugDimension = is_array($suggestion) ? ($suggestion['dimension'] ?? '') : '';
                                    $priorityBadge = $sugPriority === 'high' ? 'danger' : ($sugPriority === 'medium' ? 'warning' : 'secondary');
                                    $priorityLabel = $sugPriority === 'high' ? '高' : ($sugPriority === 'medium' ? '中' : '低');
                                    $dimensionLabel = '';
                                    if ($sugDimension !== '' && isset($breakdownItems[$sugDimension])) {
                                        $dimensionLabel = $breakdownItems[$sugDimension]['label'] ?? '';
                                    }
                                    $anchorModule = $dimensionToModule[$sugDimension] ?? '';
                                @endphp
                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-start">
                                        <span class="badge bg-{{ $priorityBadge }} me-3 mt-1">{{ $priorityLabel }}</span>
                                        <div class="flex-fill">
                                            <div class="d-flex align-items-center mb-1">
                                                <h4 class="h6 mb-0">改进项 {{ $index + 1 }}</h4>
                                                @if($dimensionLabel)
                                                    <span class="badge bg-{{ $priorityBadge }}-subtle text-{{ $priorityBadge }} ms-2" style="font-size: 10px;">{{ $dimensionLabel }}</span>
                                                @endif
                                            </div>
                                            <p class="text-secondary mb-1 small">{{ $sugText }}</p>
                                            @if($sugDimension)
                                                <a href="{{ route('user.resumes.editor', $resume) }}#dim-{{ $sugDimension }}" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: .7rem;">
                                                    <i class="ti ti-arrow-right me-1"></i>前往编辑
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">简历内容预览</h3>
                    </div>
                    <div class="card-body">
                        <div class="p-3 bg-light rounded font-monospace text-wrap" style="white-space: pre-wrap; max-height: 300px; overflow-y: auto;">
                            {{ $resume->content_raw ?? '暂无内容' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
(function() {
    var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var labelColor = isDark ? '#94a3b8' : '#64748b';

    var breakdownData = {!! json_encode(collect($breakdownItems)->mapWithKeys(function($item, $key) use ($dimensionShortLabels) {
        $score = (int) ($item['score'] ?? 0);
        $max = max(1, (int) ($item['max_score'] ?? 1));
        $percent = (int) round(($score / $max) * 100);
        return [$key => ['label' => $dimensionShortLabels[$key] ?? $key, 'percent' => $percent, 'score' => $score, 'max' => $max]];
    })->values()) !!};

    var radarLabels = breakdownData.map(function(d) { return d.label; });
    var radarValues = breakdownData.map(function(d) { return d.percent; });

    var radarEl = document.getElementById('radarChart');
    if (radarEl) {
        new ApexCharts(radarEl, {
            chart: { type: 'radar', height: 300, background: 'transparent', toolbar: { show: false } },
            series: [{ name: '得分率', data: radarValues }],
            xaxis: { categories: radarLabels, labels: { style: { colors: labelColor, fontSize: '11px' } } },
            yaxis: { min: 0, max: 100, tickAmount: 5, labels: { show: false } },
            stroke: { width: 2, colors: ['rgba(37, 99, 235, 0.8)'] },
            fill: { colors: ['rgba(37, 99, 235, 0.15)'] },
            markers: { size: 4, colors: ['rgba(37, 99, 235, 1)'], strokeWidth: 2, strokeColors: '#fff' },
            plotOptions: { radar: { polygons: { strokeColors: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)' } } },
            legend: { show: false },
            tooltip: {
                custom: function(opts) {
                    var d = breakdownData[opts.dataPointIndex];
                    return '<div style="padding:6px 10px">' + d.label + ': ' + d.score + '/' + d.max + ' (' + d.percent + '%)</div>';
                }
            },
            theme: { mode: isDark ? 'dark' : 'light' }
        }).render();
    }

    @if(count($history) > 1)
    var historyData = {!! json_encode(collect($history)->map(function($h) {
        return [
            'score' => (int) ($h['score'] ?? 0),
            'date' => isset($h['scored_at']) ? \Illuminate\Support\Carbon::parse($h['scored_at'])->format('m/d') : '',
        ];
    })->values()) !!};

    var trendChart = null;
    var trendCollapse = document.getElementById('trendCollapse');
    if (trendCollapse) {
        trendCollapse.addEventListener('shown.bs.collapse', function() {
            if (!trendChart) {
                var trendEl = document.getElementById('trendChart');
                if (trendEl) {
                    trendChart = new ApexCharts(trendEl, {
                        chart: { type: 'line', height: 200, background: 'transparent', toolbar: { show: false } },
                        series: [{ name: 'ATS 分数', data: historyData.map(function(d) { return d.score; }) }],
                        xaxis: { categories: historyData.map(function(d) { return d.date; }), labels: { style: { colors: labelColor, fontSize: '10px' } } },
                        yaxis: { min: 0, max: 100, labels: { style: { colors: labelColor, fontSize: '10px' } } },
                        stroke: { width: 2, curve: 'smooth' },
                        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
                        colors: ['rgba(37, 99, 235, 0.8)'],
                        markers: {
                            size: 5,
                            strokeWidth: 2,
                            strokeColors: '#fff',
                            colors: historyData.map(function(d) {
                                if (d.score >= 85) return '#22c55e';
                                if (d.score >= 72) return '#3b82f6';
                                if (d.score >= 60) return '#f59e0b';
                                return '#ef4444';
                            })
                        },
                        grid: { borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)' },
                        legend: { show: false },
                        tooltip: { y: { formatter: function(val) { return '分数: ' + val; } } },
                        theme: { mode: isDark ? 'dark' : 'light' }
                    }).render();
                }
            }
        });
    }
    @endif
})();

document.addEventListener('submit', function(e) {
    if (e.target.matches('form[action*="ats-score"]')) {
        var overlay = document.getElementById('ats-loading-overlay');
        if (overlay) overlay.style.display = 'flex';
    }
});
</script>
@endsection
