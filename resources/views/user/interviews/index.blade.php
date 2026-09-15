@extends('layouts.user')

@section('title', 'AI面试')

@section('page-pretitle', '面试管理')
@section('page-title', 'AI面试')

@section('page-actions')
<a href="{{ route('user.interviews.calendar') }}" class="btn btn-outline-info me-2">
    <i class="ti ti-calendar me-1"></i>面试日历
</a>
<a href="{{ route('user.question-favorites.index') }}" class="btn btn-outline-warning me-2">
    <i class="ti ti-star me-1"></i>错题本
</a>
<a href="{{ route('user.interviews.create') }}" class="btn btn-primary">
    <i class="ti ti-plus me-2"></i>开始面试
</a>
@endsection

@section('content')
<div class="row">
    @php
        $planSummary = is_array($interviewPlanSummary ?? null) ? $interviewPlanSummary : [];
        $customQuestionsEnabled = (bool) ($planSummary['customQuestionsEnabled'] ?? false);
        $interviewMaxQuestions = (int) ($planSummary['interviewMaxQuestions'] ?? config('interview.max_questions', 5));
         $interviewSessionQuota = is_array($planSummary['interviewSessionQuotaCheck'] ?? null) ? $planSummary['interviewSessionQuotaCheck'] : [];
        $interviewSessionAutoCreditCheck = is_array($planSummary['interviewSessionAutoCreditCheck'] ?? null) ? $planSummary['interviewSessionAutoCreditCheck'] : [];
        $sessionQuotaNotice = is_array($planSummary['interviewSessionNotice'] ?? null) ? $planSummary['interviewSessionNotice'] : null;
        $sessionAutoCreditReady = (($sessionQuotaNotice['credit_available'] ?? false) === true)
            && (($interviewSessionAutoCreditCheck['allowed'] ?? false) === true);
        $sessionAutoCreditName = (string) ($interviewSessionAutoCreditCheck['credit_name'] ?? '次卡');
        $hasCreditContinuation = (($sessionQuotaNotice['credit_available'] ?? false) === true);
        $formatQuotaSummary = static function (array $quota): string {
            $limit = (int) ($quota['monthly_limit'] ?? 0);
            $used = (int) ($quota['monthly_used'] ?? 0);
            if ($limit === -1) {
                return '不限';
            }

            $remaining = max(0, $limit - $used);

            return "本月已用 {$used}/{$limit}，剩余 {$remaining}";
        };
    @endphp
    <div class="col-12 mb-3">
        <div class="alert alert-info py-2 mb-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small">
                    <span class="me-3">
                        <i class="ti ti-chart-donut-2 me-1"></i>
                        AI 面试场次：{{ $formatQuotaSummary($interviewSessionQuota) }}
                    </span>
                    <span class="me-3">
                        <i class="ti ti-file-text me-1"></i>
                        JD 定制题目：
                        <span class="badge {{ $customQuestionsEnabled ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning' }}">
                            {{ $customQuestionsEnabled ? '已开通' : '未开通' }}
                        </span>
                    </span>
                    <span>
                        <i class="ti ti-list-numbers me-1"></i>
                        单场最多 {{ $interviewMaxQuestions }} 题
                    </span>
                </div>
                <div class="text-secondary small">
                    创建面试消耗场次，提交回答不再消耗额度。
                </div>
            </div>
        </div>
    </div>
    @if($sessionQuotaNotice)
        <div class="col-12 mb-3">
            <div class="alert alert-warning py-2 mb-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small">
                        @if($sessionQuotaNotice)
                            <div><i class="ti ti-alert-triangle me-1"></i>{{ $sessionQuotaNotice['message'] }}</div>
                            @if($sessionAutoCreditReady)
                                <div class="small text-secondary mt-1">开始面试时，系统会先检查免费次数；若已用完，将弹出次卡确认框，默认优先推荐 AI 面试专用次卡，其次推荐通用次卡。当前推荐：{{ $sessionAutoCreditName }}。</div>
                            @elseif(($sessionQuotaNotice['credit_available'] ?? false) === true)
                                <div class="small text-secondary mt-1">开始面试时，系统会先检查免费次数；若已用完，将弹出次卡确认框，并继续检查 AI 面试专用次卡或通用次卡，确认后再创建面试。</div>
                            @endif
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('user.membership.credit-packs') }}" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-ticket me-1"></i>{{ $hasCreditContinuation ? '购买次卡/通用卡' : '购买次卡' }}
                        </a>
                        <a href="{{ route('user.membership.pricing') }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-crown me-1"></i>升级套餐
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if($interviews->isEmpty())
        <div class="col-12">
            <div class="empty-page">
                <div class="empty-page-icon">
                    <i class="ti ti-message-chatbot"></i>
                </div>
                <h3 class="empty-page-title">暂无面试记录</h3>
                <p class="empty-page-text">开始一次AI模拟面试，提升你的面试技巧。</p>
                <a href="{{ route('user.interviews.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i>开始面试
                </a>
            </div>
        </div>
    @else
        <div class="col-12">
            <form method="GET" class="mb-3">
                <div class="row g-2">
                    <div class="col-auto">
                        <select name="status" class="form-select" data-auto-submit>
                            <option value="">全部状态</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>待开始</option>
                            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>进行中</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>已完成</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="type" class="form-select" data-auto-submit>
                            <option value="">全部类型</option>
                            @php
                                $typeLabels = ['technical'=>'技术面试','behavioral'=>'行为面试','mixed'=>'综合面试','sales'=>'销售/BD','management'=>'管理岗','creative'=>'创意/设计','finance'=>'金融/财务','retail'=>'零售/电商','manufacturing'=>'制造/工业','service'=>'服务行业','media'=>'传媒/广告','education'=>'教育/培训','deep'=>'深度面谈'];
                            @endphp
                            @foreach($typeLabels as $k => $v)
                                <option value="{{ $k }}" {{ ($filters['type'] ?? request('type')) === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="candidate_profile" class="form-select" data-auto-submit>
                            <option value="">全部身份</option>
                            <option value="fresh_graduate" {{ ($filters['candidate_profile'] ?? request('candidate_profile')) === 'fresh_graduate' ? 'selected' : '' }}>应届生</option>
                            <option value="no_experience" {{ ($filters['candidate_profile'] ?? request('candidate_profile')) === 'no_experience' ? 'selected' : '' }}>无经验转岗</option>
                            <option value="junior" {{ ($filters['candidate_profile'] ?? request('candidate_profile')) === 'junior' ? 'selected' : '' }}>1-3年经验</option>
                            <option value="experienced" {{ ($filters['candidate_profile'] ?? request('candidate_profile')) === 'experienced' ? 'selected' : '' }}>3年+经验</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="jd_mode" class="form-select" data-auto-submit>
                            <option value="">全部面试</option>
                            <option value="with_jd" {{ ($filters['jd_mode'] ?? request('jd_mode')) === 'with_jd' ? 'selected' : '' }}>仅含JD</option>
                            <option value="without_jd" {{ ($filters['jd_mode'] ?? request('jd_mode')) === 'without_jd' ? 'selected' : '' }}>未填JD</option>
                        </select>
                    </div>
                </div>
            </form>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <form id="compareForm" action="{{ route('user.interviews.compare') }}" method="GET">
                        <div class="d-flex justify-content-end mb-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary d-none" id="compareBtn"><i class="ti ti-columns me-1"></i>对比选中</button>
                        </div>
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th style="width:30px;"><input type="checkbox" id="compareAll" class="form-check-input"></th>
                                    <th>简历</th>
                                    <th>公司</th>
                                    <th>职位</th>
                                    <th>身份</th>
                                    <th>类型</th>
                                    <th>模式</th>
                                    <th>状态</th>
                                    <th>时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($interviews as $interview)
                                    <tr>
                                        <td>
                                            @if($interview->status === 'completed')
                                                <input type="checkbox" name="ids[]" value="{{ $interview->id }}" class="form-check-input compare-check">
                                            @endif
                                        </td>
                                        <td>
                                            @if($interview->resume)
                                                <a href="{{ route('user.resumes.show', $interview->resume) }}">
                                                    {{ $interview->resume->title }}
                                                </a>
                                            @else
                                                已删除
                                            @endif
                                        </td>
                                        <td>{{ $interview->company ?: '-' }}</td>
                                        <td>{{ $interview->position }}</td>
                                        <td>
                                            @php
                                                $profileLabel = match ($interview->candidate_profile ?? 'fresh_graduate') {
                                                    'no_experience' => '无经验转岗',
                                                    'junior' => '1-3年经验',
                                                    'experienced' => '3年+经验',
                                                    default => '应届生',
                                                };
                                            @endphp
                                            <span class="badge bg-secondary-lt text-secondary">{{ $profileLabel }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $typeLabels = ['technical'=>'技术面试','behavioral'=>'行为面试','mixed'=>'综合面试','sales'=>'销售/BD','management'=>'管理岗','creative'=>'创意/设计','finance'=>'金融/财务','retail'=>'零售/电商','manufacturing'=>'制造/工业','service'=>'服务行业','media'=>'传媒/广告','education'=>'教育/培训','deep'=>'深度面谈'];
                                                $typeColors = ['technical'=>'primary','behavioral'=>'info','mixed'=>'warning','sales'=>'success','management'=>'danger','creative'=>'primary','finance'=>'warning','retail'=>'success','manufacturing'=>'secondary','service'=>'info','media'=>'primary','education'=>'info','deep'=>'secondary'];
                                                $typeIcons = ['technical'=>'ti ti-code','behavioral'=>'ti ti-users','mixed'=>'ti ti-adjustments','sales'=>'ti ti-chart-bar','management'=>'ti ti-building','creative'=>'ti ti-palette','finance'=>'ti ti-report-money','retail'=>'ti ti-shopping-cart','manufacturing'=>'ti ti-tool','service'=>'ti ti-headset','media'=>'ti ti-speakerphone','education'=>'ti ti-book','deep'=>'ti ti-brain'];
                                                $tKey = $interview->type ?? 'mixed';
                                            @endphp
                                            <span class="badge bg-{{ $typeColors[$tKey] ?? 'secondary' }}-lt text-{{ $typeColors[$tKey] ?? 'secondary' }} rounded-pill">
                                                <i class="{{ $typeIcons[$tKey] ?? 'ti ti-message-circle' }} me-1" style="font-size:.7rem;"></i>{{ $typeLabels[$tKey] ?? $tKey }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($interview->mode === 'voice')
                                                <span class="badge bg-success-lt text-success"><i class="ti ti-microphone me-1"></i>语音</span>
                                            @else
                                                <span class="badge bg-secondary-lt text-secondary"><i class="ti ti-keyboard me-1"></i>文字</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($interview->status)
                                                @case('pending')
                                                    <span class="badge bg-secondary">待开始</span>
                                                @break
                                                @case('in_progress')
                                                    <span class="badge bg-warning">进行中</span>
                                                @break
                                                @case('completed')
                                                    <span class="badge bg-success">已完成</span>
                                                @break
                                            @endswitch
                                            @if($interview->is_practice)
                                                <span class="badge bg-info-lt text-info ms-1">练习</span>
                                            @endif
                                        </td>
                                        <td class="text-secondary">{{ $interview->created_at->diffForHumans() }}</td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                @if($interview->status === 'pending')
                                                    <a href="{{ route('user.interviews.session', $interview) }}" class="btn btn-primary btn-sm">
                                                        <i class="ti ti-player-play me-1"></i>开始
                                                    </a>
                                                @elseif($interview->status === 'completed')
                                                    <a href="{{ route('user.interviews.report', $interview) }}" class="btn btn-success btn-sm">
                                                        <i class="ti ti-chart-bar me-1"></i>报告
                                                    </a>
                                                @else
                                                    <a href="{{ route('user.interviews.session', $interview) }}" class="btn btn-outline-primary btn-sm">
                                                        <i class="ti ti-eye me-1"></i>继续
                                                    </a>
                                                @endif
                                                
                                                {{-- 语音面试显示二维码按钮 --}}
                                                @if($interview->mode === 'voice' && $interview->status !== 'completed')
                                                    <button type="button" class="btn btn-outline-info btn-sm" data-show-qrcode="{{ $interview->id }}">
                                                        <i class="ti ti-qrcode me-1"></i>扫码
                                                    </button>
                                                @endif
                                                
                                                <form action="{{ route('user.interviews.destroy', $interview) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" data-app-confirm="确定要删除这条面试记录吗？">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </form>
                    </div>
                    @if($interviews->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $interviews->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

{{-- 二维码弹窗 --}}
<div class="modal modal-blur fade" id="qrCodeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-microphone me-2 text-success"></i>语音面试 - 扫码参与</h5>
                <button type="button" class="btn-close" data-hide-qrcode aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-secondary mb-3">使用手机扫描二维码，即可在手机上进行语音面试</p>
                <div id="qrcode-container" class="d-flex justify-content-center mb-3">
                    <div id="qrcode"></div>
                </div>
                <div class="alert alert-info">
                    <i class="ti ti-info-circle me-2"></i>
                    二维码有效期为24小时，请尽快扫码参与
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-hide-qrcode>关闭</button>
            </div>
        </div>
    </div>
</div>
@endsection

@php
    $qrcodeJsVersion = @filemtime(public_path('js/vendor/qrcode.min.js')) ?: 1;
@endphp

@push('scripts')
<script src="{{ asset('js/vendor/qrcode.min.js') }}?v={{ $qrcodeJsVersion }}"></script>
@endpush
@push('scripts')
<script src="{{ asset('js/pages/user-interviews-index.js') }}"></script>
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
document.addEventListener('DOMContentLoaded', function() {
    var checks = document.querySelectorAll('.compare-check');
    var btn = document.getElementById('compareBtn');
    var allCheck = document.getElementById('compareAll');
    function updateBtn() {
        var checked = document.querySelectorAll('.compare-check:checked');
        if (btn) {
            checked.length >= 2 ? btn.classList.remove('d-none') : btn.classList.add('d-none');
        }
    }
    checks.forEach(function(c) { c.addEventListener('change', updateBtn); });
    if (allCheck) {
        allCheck.addEventListener('change', function() {
            checks.forEach(function(c) { c.checked = allCheck.checked; });
            updateBtn();
        });
    }
});
</script>
@endpush
