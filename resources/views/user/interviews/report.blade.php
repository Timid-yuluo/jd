@extends('layouts.user')

@section('title', '面试报告 - ' . $interview->position)

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <a href="{{ route('user.interviews.index') }}" class="btn btn-link text-secondary p-0"><i class="ti ti-arrow-left me-1"></i>返回</a>
                <h2 class="page-title">面试报告</h2>
                <div class="text-secondary">{{ $interview->position }}@if($interview->company) · {{ $interview->company }}@endif</div>
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-outline-warning" data-action="share-experience" data-interview-id="{{ $interview->id }}" data-position="{{ $interview->position }}"><i class="ti ti-share me-1"></i>分享经验</button>
                <a href="{{ route('user.interviews.create', ['position' => $interview->position, 'company' => $interview->company]) }}" class="btn btn-outline-success"><i class="ti ti-refresh me-1"></i>再来一次</a>
                <a href="{{ route('user.interviews.show', $interview) }}" class="btn btn-outline-secondary"><i class="ti ti-arrows-maximize me-1"></i>回顾面试</a>
                <a href="{{ route('user.interviews.report-pdf', $interview) }}" class="btn btn-outline-info" target="_blank"><i class="ti ti-file-type-pdf me-1"></i>PDF</a>
                <button class="btn btn-outline-primary" onclick="window.print()"><i class="ti ti-printer me-1"></i>打印</button>
            </div>
        </div>
    </div>
</div>

<div class="page-body"><div class="container-xl">
    @php
        $report = is_array($interview->report) ? $interview->report : [];
        $growthPath = $report['fresh_grad_growth_path'] ?? [];
        $isFreshGrad = !empty($growthPath['is_fresh_grad']);
        $dims = collect($report['dimension_scores'] ?? []);
        $questions = $interview->questions()->orderBy('round_no')->get();
        $answeredQuestions = $questions->where('answer', '!=', null);
        $answeredCount = $answeredQuestions->count();
        $totalCount = $interview->question_count ?? $questions->count();
        $totalScore = $answeredCount > 0 ? round((float) $answeredQuestions->avg('score'), 1) : null;
        $scoreClass = $totalScore === null ? 'secondary' : ($totalScore >= 7 ? 'success' : ($totalScore >= 5 ? 'warning' : 'danger'));
        $profileLabels = ['fresh_graduate' => '应届生', 'no_experience' => '转岗', 'junior' => '1-3年', 'experienced' => '3年+'];
    @endphp

    {{-- 综合评分 + 面试概览 --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="text-secondary small mb-1">综合评分</div>
                    <div class="display-3 fw-bold text-{{ $scoreClass }}">{{ $totalScore !== null ? $totalScore : '暂无' }}</div>
                    <div class="text-secondary small">{{ $totalScore !== null ? '/ 10 分' : '完成答题后出分' }}</div>
                    <div class="mt-2 small text-secondary">{{ $answeredCount }}/{{ $totalCount }} 题已答 · 面试时长约 {{ $answeredCount * 5 }}-{{ $answeredCount * 15 }} 分钟</div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="text-secondary small">面试类型</div>
                            <div class="fw-medium">{{ match($interview->type) { 'technical' => '技术面试', 'behavioral' => '行为面试', 'sales' => '销售/BD', 'management' => '管理岗', 'creative' => '创意/设计', 'finance' => '金融/财务', 'retail' => '零售/电商', 'manufacturing' => '制造/工业', 'service' => '服务行业', 'media' => '传媒/广告', 'education' => '教育/培训', 'deep' => '深度面谈', default => '综合面试' } }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-secondary small">求职身份</div>
                            <div class="fw-medium">{{ $profileLabels[$interview->candidate_profile ?? ''] ?? '应届生' }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-secondary small">面试难度</div>
                            <div class="fw-medium">{{ match($interview->difficulty ?? 'medium') { 'easy' => '简单', 'hard' => '困难', default => '中等' } }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-secondary small">面试日期</div>
                            <div class="fw-medium">{{ $interview->created_at->format('m-d H:i') }}</div>
                        </div>
                        @if($interview->resume)
                        <div class="col-12">
                            <div class="text-secondary small">关联简历</div>
                            <a href="{{ route('user.resumes.show', $interview->resume) }}" class="text-decoration-none">{{ $interview->resume->title }}</a>
                            @if($interview->resume->target_job)<span class="text-secondary small"> · 目标：{{ $interview->resume->target_job }}</span>@endif
                        </div>
                        @endif
                        <div class="col-6">
                            <div class="text-secondary small">面试模式</div>
                            <div class="fw-medium">{{ $interview->mode === 'voice' ? '语音面试' : '文字面试' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary small">面试语言</div>
                            <div class="fw-medium">{{ $interview->language === 'en' ? 'English' : '中文' }}</div>
                        </div>
                        @if($interview->tech_keywords)
                        <div class="col-12">
                            <div class="text-secondary small">技术关键词</div>
                            <div>@foreach(explode(',', $interview->tech_keywords) as $kw)<span class="badge bg-primary-lt text-primary me-1 mb-1">{{ trim($kw) }}</span>@endforeach</div>
                        </div>
                        @endif
                        @if($interview->job_description)
                        <div class="col-12">
                            <div class="text-secondary small">岗位 JD (@if($interview->mode === 'voice')语音@else文字@endif)</div>
                            <div class="small text-muted" style="white-space:pre-wrap;max-height:80px;overflow:auto;">{{ \Illuminate\Support\Str::limit($interview->job_description, 200) }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-3">
            {{-- 能力维度得分 --}}
            <div class="card">
                <div class="card-header"><h3 class="card-title">能力维度得分</h3></div>
                <div class="card-body">
                    @if($dims->isNotEmpty())
                        @foreach($dims as $row)
                            @php $s = (int)($row['score'] ?? 0); $c = $s >= 7 ? 'success' : ($s >= 5 ? 'warning' : 'danger'); @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>{{ $row['dimension'] ?? '' }}</span>
                                    <span class="fw-bold text-{{ $c }}">{{ $s }}/10</span>
                                </div>
                                <div class="progress" style="height:6px"><div class="progress-bar bg-{{ $c }}" style="width:{{ $s*10 }}%"></div></div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-secondary text-center py-3 small">暂无维度评分数据</div>
                    @endif
                </div>
            </div>

            {{-- 优势与不足 --}}
            @php $strengths = collect($report['strengths'] ?? []); $improvements = collect($report['improvements'] ?? []); @endphp
            @if($strengths->isNotEmpty() || $improvements->isNotEmpty())
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">优势与不足</h3></div>
                <div class="card-body">
                    @if($strengths->isNotEmpty())
                        <div class="mb-3"><div class="text-success small fw-medium mb-1"><i class="ti ti-trending-up me-1"></i>优势</div>
                            @foreach($strengths as $s)<div class="d-flex align-items-start gap-1 mb-1 small"><span class="text-success mt-1">•</span><span>{{ $s }}</span></div>@endforeach
                        </div>
                    @endif
                    @if($improvements->isNotEmpty())
                        <div><div class="text-warning small fw-medium mb-1"><i class="ti ti-trending-down me-1"></i>需改进</div>
                            @foreach($improvements as $i)<div class="d-flex align-items-start gap-1 mb-1 small"><span class="text-warning mt-1">•</span><span>{{ $i }}</span></div>@endforeach
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- 回答框架建议 --}}
            @php $guidance = $report['answer_guidance'] ?? []; @endphp
            @if(!empty($guidance))
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title"><i class="ti ti-lightbulb text-warning me-1"></i>回答框架建议</h3></div>
                <div class="card-body">
                    @foreach($guidance as $g)
                        <div class="mb-3 pb-3 @if(!$loop->last) border-bottom @endif">
                            <div class="fw-medium small mb-1">{{ $g['title'] ?? '' }}</div>
                            <span class="badge bg-primary-lt text-primary mb-2">{{ $g['framework'] ?? 'STAR 法则' }}</span>
                            @if(!empty($g['tips']))
                                <ul class="small mb-0 ps-3 text-secondary">
                                    @foreach($g['tips'] as $t)<li class="mb-1">{{ $t }}</li>@endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- JD 匹配度 --}}
            @php $jd = $report['jd_alignment'] ?? []; @endphp
            @if(!empty($jd['enabled']))
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">JD 匹配度分析</h3></div>
                <div class="card-body">
                    <div class="row g-2 text-center small mb-3">
                        <div class="col"><div class="fw-bold fs-3 text-{{ ($jd['match_score']??0)>=7?'success':'warning' }}">{{ $jd['match_score']??0 }}</div><div class="text-muted">综合/10</div></div>
                        <div class="col"><div class="fw-bold fs-3">{{ $jd['keyword_hit_rate']??0 }}%</div><div class="text-muted">关键词</div></div>
                        <div class="col"><div class="fw-bold fs-3">{{ $jd['evidence_score']??0 }}</div><div class="text-muted">量化/10</div></div>
                    </div>
                    @if(!empty($jd['matched_keywords']))
                        <div class="mb-2"><span class="small text-success">✓ 已覆盖</span>
                            @foreach($jd['matched_keywords'] as $kw)<span class="badge bg-success-lt text-success me-1 mb-1">{{ $kw }}</span>@endforeach
                        </div>
                    @endif
                    @if(!empty($jd['missing_keywords']))
                        <div><span class="small text-warning">✗ 待补强</span>
                            @foreach($jd['missing_keywords'] as $kw)<span class="badge bg-warning-lt text-warning me-1 mb-1">{{ $kw }}</span>@endforeach
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- 应届生成长指南 --}}
            @if($isFreshGrad)
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title"><i class="ti ti-road text-primary me-1"></i>应届生成长指南</h3></div>
                <div class="card-body">
                    @if(!empty($growthPath['overall']))
                        <div class="alert alert-info py-2 small mb-3">{{ $growthPath['overall'] }}</div>
                    @endif
                    @if(!empty($growthPath['stages']))
                        <div class="mb-3">
                            @foreach($growthPath['stages'] as $s)
                                <div class="d-flex gap-2 mb-2">
                                    <span class="badge bg-{{ ($s['score']??0)>=7?'success':(($s['score']??0)>=5?'warning':'danger') }} rounded-circle" style="min-width:26px;height:26px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">R{{ $s['round']??'' }}</span>
                                    <div>
                                        <div class="small fw-medium">{{ $s['stage']??'' }}<span class="text-muted ms-1">({{ $s['score']??'-' }}分)</span></div>
                                        <div class="small text-secondary">{{ $s['advice']??'' }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if(!empty($growthPath['elevator_pitch']))
                        @php $ep = $growthPath['elevator_pitch']; @endphp
                        <div class="mb-3"><span class="badge bg-primary-lt text-primary mb-2">自我介绍模板（电梯演讲）</span>
                            <div class="border-start border-3 border-primary ps-2 mb-1 small"><span class="text-muted">开场</span> {{ $ep['hook']??'' }}</div>
                            <div class="border-start border-3 border-success ps-2 mb-1 small"><span class="text-muted">亮点1</span> {{ $ep['value_1']??'' }}</div>
                            <div class="border-start border-3 border-warning ps-2 mb-1 small"><span class="text-muted">亮点2</span> {{ $ep['value_2']??'' }}</div>
                            <div class="border-start border-3 border-info ps-2 small"><span class="text-muted">收尾</span> {{ $ep['close']??'' }}</div>
                            @if(!empty($ep['tip']))<div class="alert py-1 small mt-2 mb-0" style="background:#eff6ff">💡 {{ $ep['tip'] }}</div>@endif
                        </div>
                    @endif
                    @if(!empty($growthPath['resources']))
                        <div class="mb-2"><span class="small fw-medium">📚 推荐学习</span></div>
                        @foreach(array_slice($growthPath['resources'], 0, 5) as $r)
                            <div class="d-flex align-items-center gap-2 small py-1 border-bottom">
                                <i class="ti ti-{{ $r['icon']??'bookmark' }} text-muted"></i>
                                <span>{{ $r['name'] }}</span>
                                <span class="badge ms-auto" style="font-size:.6rem;background:#e5e7eb;color:#374151;">{{ $r['type']??'' }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- 逐题复盘 --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">逐题复盘</h3></div>
                <div class="card-body p-0">
                    @foreach($questions as $q)
                        <div class="p-3 @if(!$loop->last) border-bottom @endif" id="question-{{ $q->id }}">
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <span class="badge bg-secondary">第{{ $q->round_no }}题</span>
                                @if($q->dimension)<span class="badge bg-info-lt text-info">{{ $q->dimension }}</span>@endif
                                @if($q->difficulty_level)<span class="badge {{ match($q->difficulty_level) { 'easy' => 'bg-success-lt text-success', 'hard' => 'bg-danger-lt text-danger', default => 'bg-warning-lt text-warning' } }}">{{ match($q->difficulty_level) { 'easy' => '简单', 'hard' => '困难', default => '中等' } }}</span>@endif
                                @if($q->tags && is_array($q->tags)) @foreach($q->tags as $tag)<span class="badge bg-azure-lt text-azure">{{ $tag }}</span>@endforeach @endif
                                @if($q->score !== null)<span class="badge bg-{{ $q->score>=7?'success':($q->score>=5?'warning':'danger') }}-lt text-{{ $q->score>=7?'success':($q->score>=5?'warning':'danger') }}">{{ $q->score }}/10</span>@endif
                                @if($q->answer)<span class="badge bg-success-lt text-success">已答</span>@else<span class="badge bg-secondary-lt">未答</span>@endif
                                <button type="button" class="btn btn-sm btn-link p-0 ms-auto question-fav-btn" data-question-id="{{ $q->id }}" title="收藏此题">
                                    <i class="ti ti-star text-secondary"></i>
                                </button>
                            </div>

                            <div class="fw-medium mb-2">{{ $q->question }}</div>

                            @if($q->answer)
                                <div class="small bg-light rounded p-2 mb-2" style="white-space:pre-wrap;">{{ $q->answer }}</div>

                                @if(!empty($q->feedback['comment']))
                                    <div class="small mb-1"><span class="text-success fw-medium">点评：</span><span class="text-secondary">{{ $q->feedback['comment'] }}</span></div>
                                @endif
                                @if(!empty($q->feedback['suggestion']))
                                    <div class="small mb-1"><span class="text-warning fw-medium">建议：</span><span class="text-secondary">{{ $q->feedback['suggestion'] }}</span></div>
                                @endif
                                @if(!empty($q->answer_model_guidance))
                                    @php $mg = is_array($q->answer_model_guidance) ? $q->answer_model_guidance : (is_string($q->answer_model_guidance) ? json_decode($q->answer_model_guidance, true) : []); @endphp
                                    @if(!empty($mg['framework']))
                                        <div class="small mt-2 p-2 rounded" style="background:#f0fdf4">
                                            <span class="badge bg-success-lt text-success mb-1">回答框架：{{ $mg['framework'] }}</span>
                                            @if(!empty($mg['key_points']))<br>@foreach($mg['key_points'] as $kp)<span class="text-secondary small">• {{ $kp }}</span><br>@endforeach @endif
                                        </div>
                                    @endif
                                @endif
                            @else
                                <div class="small text-muted text-center py-2 bg-light rounded">此题未作答</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 教练寄语 --}}
            @if(!empty($growthPath['coaching']))
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title"><i class="ti ti-messages text-primary me-1"></i>教练寄语</h3></div>
                <div class="card-body">
                    @foreach($growthPath['coaching'] as $msg)<div class="small text-secondary mb-1">💬 {{ $msg }}</div>@endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div></div>
@endsection

@section('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.question-fav-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var qId = this.dataset.questionId;
            var icon = this.querySelector('i');
            fetch('{{ url("/user/questions") }}/' + qId + '/favorite', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.favorited) {
                    icon.className = 'ti ti-star-filled text-warning';
                    btn.title = '取消收藏';
                } else {
                    icon.className = 'ti ti-star text-secondary';
                    btn.title = '收藏此题';
                }
            }).catch(function() {});
        });
    });

    // 分享面试经验
    document.querySelectorAll('[data-action="share-experience"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var interviewId = this.dataset.interviewId;
            var position = this.dataset.position;
            var text = '我在 HQ35 面试模拟器完成了「' + position + '」岗位的面试练习，感觉收获很大！推荐大家也来试试 👉 ' + window.location.origin;
            if (navigator.share) {
                navigator.share({ title: '面试经验分享', text: text }).catch(function(){});
            } else {
                var ta = document.createElement('textarea');
                ta.value = text;
                ta.style.cssText = 'position:fixed;left:-9999px;';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                var notify = typeof window.appNotify === 'function' ? window.appNotify : function(msg){ alert(msg); };
                notify('分享链接已复制到剪贴板', 'success');
            }
        });
    });
});
</script>
@endsection
