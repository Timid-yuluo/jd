@extends('layouts.admin')

@section('title', '反馈详情')
@section('page-pretitle', '意见反馈')
@section('page-title', '反馈详情 #' . $feedback->id)

@section('content')
<div
    data-url-update-status="{{ route('admin.feedbacks.update-status', $feedback) }}"
    data-url-update-note="{{ route('admin.feedbacks.update-note', $feedback) }}"
    data-url-reply="{{ route('admin.feedbacks.reply', $feedback) }}"
    data-url-delete="{{ route('admin.feedbacks.destroy', $feedback) }}"
    data-url-update-adoption="{{ route('admin.feedbacks.update-adoption', $feedback) }}"
    data-url-grant-reward="{{ route('admin.feedbacks.reward', $feedback) }}"
    data-url-feedbacks-index="{{ route('admin.feedbacks.index') }}"
    data-auth-name="{{ auth()->user()->name }}"
>
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col-auto">
            <a href="{{ route('admin.feedbacks.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-arrow-left me-1"></i> 返回列表
            </a>
        </div>
        <div class="col">
            <h2 class="page-title">反馈 #{{ $feedback->id }}</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        {{-- 反馈详情 --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">{{ $feedback->title }}</h3>
                <div class="card-actions">
                    <span class="badge bg-{{ $feedback->getStatusColor() }}-lt me-1">{{ $feedback->getStatusLabel() }}</span>
                    <span class="badge bg-{{ $feedback->getPriorityColor() }}-lt me-1">{{ $feedback->getPriorityLabel() }}</span>
                    <span class="badge bg-{{ $feedback->getAdoptionColor() }}-lt">{{ $feedback->getAdoptionLabel() }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge bg-{{ $feedback->category === 'bug' ? 'red' : ($feedback->category === 'suggestion' ? 'blue' : ($feedback->category === 'ux' ? 'yellow' : 'secondary')) }}-lt">{{ $feedback->getCategoryLabel() }}</span>
                </div>
                <div class="mb-3" style="white-space: pre-wrap;">{{ $feedback->content }}</div>

                @if($feedback->page_url)
                <div class="text-secondary small mt-3">
                    <i class="ti ti-link me-1"></i>反馈页面：
                    <a href="{{ $feedback->page_url }}" target="_blank" rel="noopener noreferrer nofollow" class="text-reset">{{ $feedback->page_name ?: $feedback->page_url }}</a>
                </div>
                @endif

                @if($feedback->metadata)
                <div class="text-secondary small mt-2">
                    @if(data_get($feedback->metadata, 'user_agent'))
                        <i class="ti ti-device-desktop me-1"></i>{{ \Illuminate\Support\Str::limit($feedback->metadata['user_agent'], 80) }}<br>
                    @endif
                    @if(data_get($feedback->metadata, 'screen'))
                        <i class="ti ti-dimensions me-1"></i>{{ $feedback->metadata['screen'] }}
                    @endif
                    @if(data_get($feedback->metadata, 'language'))
                        &nbsp;|&nbsp; {{ $feedback->metadata['language'] }}
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- 回复列表 --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">沟通记录 ({{ $feedback->replies->count() }})</h3>
            </div>
            <div class="card-body" id="repliesContainer">
                @foreach($feedback->replies as $reply)
                <div class="d-flex mb-3 {{ $reply->is_admin ? '' : 'flex-row-reverse' }}">
                    <div class="avatar avatar-sm me-3 {{ $reply->is_admin ? '' : 'ms-3 me-0' }}"
                         style="background: {{ $reply->is_admin ? '#206bc4' : '#0EA5E9' }}; color: #fff; font-weight: 600; font-size: 0.75rem; flex-shrink: 0;">
                        {{ mb_strtoupper(mb_substr($reply->user->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="{{ $reply->is_admin ? 'me-auto' : 'ms-auto' }}" style="max-width: 75%;">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-medium small">{{ $reply->user->name ?? '未知' }}</span>
                            @if($reply->is_admin)
                                <span class="badge bg-blue-lt">管理员</span>
                            @endif
                            <span class="text-secondary small">{{ $reply->created_at->format('m-d H:i') }}</span>
                        </div>
                        <div class="p-3 rounded {{ $reply->is_admin ? 'bg-primary-lt' : 'bg-info-lt' }}" style="white-space: pre-wrap;">{{ $reply->content }}</div>
                    </div>
                </div>
                @endforeach

                @if($feedback->replies->isEmpty())
                <div class="text-center text-secondary py-4">暂无回复</div>
                @endif
            </div>
        </div>

        {{-- 回复表单 --}}
        @canany(['feedbacks.reply', 'feedbacks.edit'])
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">回复反馈</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <textarea id="replyContent" class="form-control" rows="4" maxlength="5000" placeholder="输入回复内容..."></textarea>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notifyUser" checked>
                        <span class="form-check-label small">邮件通知用户</span>
                    </label>
                    <button type="button" class="btn btn-primary" id="replyBtn" data-action="submit-reply">
                        <i class="ti ti-send me-1"></i> 发送回复
                    </button>
                </div>
            </div>
        </div>
        @endcanany
    </div>

    <div class="col-lg-4">
        {{-- 信息面板 --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">反馈信息</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr><td class="text-secondary w-25">提交者</td><td>{{ $feedback->user ? $feedback->user->name : ($feedback->visitor_email ?: '匿名') }}</td></tr>
                    @if($feedback->user)<tr><td class="text-secondary">邮箱</td><td>{{ \Illuminate\Support\Str::mask(explode('@', $feedback->user->email)[0] ?? '', '*', 1) . '@' . (explode('@', $feedback->user->email)[1] ?? '') }}</td></tr>@endif
                    @if($feedback->visitor_email && !$feedback->user)<tr><td class="text-secondary">邮箱</td><td>{{ \Illuminate\Support\Str::mask(explode('@', $feedback->visitor_email)[0] ?? '', '*', 1) . '@' . (explode('@', $feedback->visitor_email)[1] ?? '') }}</td></tr>@endif
                    <tr><td class="text-secondary">采纳结果</td><td><span class="badge bg-{{ $feedback->getAdoptionColor() }}-lt">{{ $feedback->getAdoptionLabel() }}</span></td></tr>
                    @if($feedback->adopted_at)
                        <tr><td class="text-secondary">采纳时间</td><td>{{ $feedback->adopted_at->format('Y-m-d H:i') }}</td></tr>
                    @endif
                    @if($feedback->adoptedBy)
                        <tr><td class="text-secondary">采纳人</td><td>{{ $feedback->adoptedBy->name }}</td></tr>
                    @endif
                    <tr><td class="text-secondary">提交时间</td><td>{{ $feedback->created_at->format('Y-m-d H:i') }}</td></tr>
                    <tr><td class="text-secondary">更新时间</td><td>{{ $feedback->updated_at->format('Y-m-d H:i') }}</td></tr>
                    @if($feedback->satisfaction_score !== null)
                    <tr>
                        <td class="text-secondary">满意度</td>
                        <td>
                            @for($i = 1; $i <= 5; $i++)
                                <i class="ti {{ $i <= $feedback->satisfaction_score ? 'ti-star-filled' : 'ti-star' }}" style="color:{{ $i <= $feedback->satisfaction_score ? '#f59e0b' : '#d1d5db' }};"></i>
                            @endfor
                            <span class="ms-1 fw-medium">{{ $feedback->satisfaction_score }}/5</span>
                            @if($feedback->satisfaction_comment)
                                <br><small class="text-secondary">{{ $feedback->satisfaction_comment }}</small>
                            @endif
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- 操作面板 --}}
        @canany(['feedbacks.manage', 'feedbacks.edit', 'feedbacks.adopt', 'feedbacks.reward'])
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">操作</h3>
            </div>
            <div class="card-body">
                @canany(['feedbacks.manage', 'feedbacks.edit'])
                <div class="mb-3">
                    <label class="form-label small text-secondary">状态</label>
                    <select class="form-select form-select-sm" id="statusSelect" data-action="update-status">
                        <option value="pending" {{ $feedback->status === 'pending' ? 'selected' : '' }}>待处理</option>
                        <option value="processing" {{ $feedback->status === 'processing' ? 'selected' : '' }}>处理中</option>
                        <option value="replied" {{ $feedback->status === 'replied' ? 'selected' : '' }}>已回复</option>
                        <option value="closed" {{ $feedback->status === 'closed' ? 'selected' : '' }}>已关闭</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-secondary">优先级</label>
                    <select class="form-select form-select-sm" id="prioritySelect" data-action="update-status">
                        <option value="low" {{ $feedback->priority === 'low' ? 'selected' : '' }}>低</option>
                        <option value="medium" {{ $feedback->priority === 'medium' ? 'selected' : '' }}>中</option>
                        <option value="high" {{ $feedback->priority === 'high' ? 'selected' : '' }}>高</option>
                        <option value="urgent" {{ $feedback->priority === 'urgent' ? 'selected' : '' }}>紧急</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-secondary">内部备注</label>
                    <textarea id="adminNote" class="form-control form-control-sm" rows="3" placeholder="仅管理员可见...">{{ $feedback->admin_note }}</textarea>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-action="save-note">保存备注</button>
                </div>
                @endcanany
            </div>
        </div>

        @canany(['feedbacks.adopt', 'feedbacks.reward', 'feedbacks.edit'])
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">采纳与奖励</h3>
            </div>
            <div class="card-body">
                @canany(['feedbacks.adopt', 'feedbacks.edit'])
                <div class="mb-3">
                    <label class="form-label small text-secondary">采纳结果</label>
                    <select class="form-select form-select-sm" id="adoptionStatusSelect">
                        <option value="pending" {{ $feedback->adoption_status === 'pending' ? 'selected' : '' }}>待评估</option>
                        <option value="adopted" {{ $feedback->adoption_status === 'adopted' ? 'selected' : '' }}>已采纳</option>
                        <option value="rejected" {{ $feedback->adoption_status === 'rejected' ? 'selected' : '' }}>未采纳</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-secondary">采纳说明</label>
                    <textarea id="adoptionNote" class="form-control form-control-sm" rows="3" placeholder="这里的内容会在用户端展示">{{ $feedback->adoption_note }}</textarea>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" data-action="update-adoption">保存采纳结果</button>
                @endcanany

                @canany(['feedbacks.reward', 'feedbacks.edit'])
                <hr class="my-4">

                @if(!$feedback->user)
                    <div class="alert alert-warning mb-0">
                        匿名反馈暂不支持赠送次卡，请先通过回复沟通处理。
                    </div>
                @elseif($feedback->reward)
                    <div class="alert alert-success">
                        <div class="fw-semibold mb-1">奖励已发放</div>
                        <div class="small">奖励内容：{{ \App\Models\UserCredit::quotaLabel($feedback->reward->quota_key) }} x {{ $feedback->reward->credits }}</div>
                        <div class="small">发放时间：{{ $feedback->reward->granted_at?->format('Y-m-d H:i') }}</div>
                        <div class="small">操作人：{{ $feedback->reward->grantedBy?->name ?? '系统' }}</div>
                        @if($feedback->reward->validity_days)
                            <div class="small">有效期：{{ $feedback->reward->validity_days }} 天</div>
                        @endif
                        @if($feedback->reward->reason)
                            <div class="small mt-1">奖励原因：{{ $feedback->reward->reason }}</div>
                        @endif
                    </div>
                @else
                    <div class="mb-3">
                        <label class="form-label small text-secondary">奖励次卡类型</label>
                        <select class="form-select form-select-sm" id="rewardQuotaKey">
                            <option value="">通用次卡</option>
                            @foreach(\App\Models\UserCredit::quotaOptions() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-secondary">奖励次数</label>
                            <input type="number" min="1" max="9999" class="form-control form-control-sm" id="rewardCredits" value="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-secondary">有效期天数</label>
                            <input type="number" min="0" max="3650" class="form-control form-control-sm" id="rewardValidityDays" placeholder="留空=永久">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary">奖励原因</label>
                        <textarea id="rewardReason" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="例如：建议已采纳，感谢帮助我们优化面试流程"></textarea>
                    </div>
                    <button type="button" class="btn btn-sm btn-success w-100" id="rewardBtn" data-action="grant-reward">
                        <i class="ti ti-gift me-1"></i> 采纳并赠送次卡
                    </button>
                    <div class="text-secondary small mt-2">
                        发放后会自动标记为已采纳，且同一条反馈只允许奖励一次。
                    </div>
                @endif
                @endcanany
            </div>
        </div>
        @endcanany

        @if($feedback->auditLogs->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">审计日志</h3>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @foreach($feedback->auditLogs->take(12) as $auditLog)
                    <div class="timeline-item">
                        <div class="timeline-item-marker">
                            <div class="timeline-item-marker-text">{{ $auditLog->created_at->format('m-d') }}</div>
                            <div class="timeline-item-marker-indicator bg-primary"></div>
                        </div>
                        <div class="timeline-item-content">
                            <div class="fw-semibold">{{ $auditLog->action_label }}</div>
                            <div class="small text-secondary">
                                {{ $auditLog->changedByUser?->name ?? '系统' }} · {{ $auditLog->created_at->format('H:i') }}
                                @if($auditLog->ip_address)
                                    · {{ $auditLog->ip_address }}
                                @endif
                            </div>
                            @if($auditLog->old_values)
                                <div class="small mt-2"><span class="text-secondary">变更前：</span>{{ json_encode($auditLog->old_values, JSON_UNESCAPED_UNICODE) }}</div>
                            @endif
                            @if($auditLog->new_values)
                                <div class="small mt-1"><span class="text-secondary">变更后：</span>{{ json_encode($auditLog->new_values, JSON_UNESCAPED_UNICODE) }}</div>
                            @endif
                            @if($auditLog->context)
                                <div class="small mt-1"><span class="text-secondary">上下文：</span>{{ json_encode($auditLog->context, JSON_UNESCAPED_UNICODE) }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @can('feedbacks.delete')
        <div class="card">
            <div class="card-body">
                <button type="button" class="btn btn-outline-danger w-100" data-action="delete-feedback" data-app-confirm="确定要删除此反馈吗？此操作不可恢复。">
                    <i class="ti ti-trash me-1"></i> 删除反馈
                </button>
            </div>
        </div>
        @endcan
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/admin-feedbacks-show.js') }}"></script>
@endpush
