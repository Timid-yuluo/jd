@extends('layouts.admin')

@section('title', '投递详情')
@section('page-pretitle', '核心业务')
@section('page-title', '投递详情')

@section('page-actions')
<a href="{{ route('admin.job-applications.index') }}" class="btn btn-sm btn-outline-secondary">
    <i class="ti ti-arrow-left me-1"></i>返回列表
</a>
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        {{-- 投递进度 --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-route me-1"></i>投递进度</h3>
            </div>
            <div class="card-body">
                @php
                    $statusMap = [
                        'wishlist' => ['label' => '愿望单', 'class' => 'bg-secondary-lt', 'icon' => 'ti-heart', 'step' => 0],
                        'applied' => ['label' => '已投递', 'class' => 'bg-blue-lt', 'icon' => 'ti-send', 'step' => 1],
                        'written' => ['label' => '笔试', 'class' => 'bg-azure-lt', 'icon' => 'ti-pencil', 'step' => 2],
                        'interview' => ['label' => '面试', 'class' => 'bg-orange-lt', 'icon' => 'ti-message-circle', 'step' => 3],
                        'offer' => ['label' => 'Offer', 'class' => 'bg-green-lt', 'icon' => 'ti-trophy', 'step' => 4],
                        'rejected' => ['label' => '淘汰', 'class' => 'bg-red-lt', 'icon' => 'ti-x', 'step' => -1],
                    ];
                    $currentStep = $statusMap[$job_application->status] ?? ['label' => $job_application->status, 'class' => 'bg-secondary-lt', 'icon' => 'ti-question-mark', 'step' => -1];
                    $steps = ['wishlist', 'applied', 'written', 'interview', 'offer'];
                    $currentStepIndex = array_search($job_application->status, $steps);
                @endphp
                <ul class="steps steps-counter steps-blue mb-0">
                    @foreach($steps as $i => $step)
                        <li class="step-item {{ $currentStepIndex !== false && $i <= $currentStepIndex ? 'active' : '' }}">{{ $statusMap[$step]['label'] }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- 备注 --}}
        @if($job_application->note)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-notes me-1"></i>备注</h3>
            </div>
            <div class="card-body">
                <div style="white-space: pre-wrap;">{{ $job_application->note }}</div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        {{-- 基础信息 --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">基础信息</h3>
                <div class="card-actions">
                    <a href="{{ route('admin.job-applications.edit', $job_application) }}" class="btn btn-sm btn-primary"><i class="ti ti-edit me-1"></i>编辑</a>
                </div>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">公司</div>
                        <div class="datagrid-content">{{ $job_application->company }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">岗位</div>
                        <div class="datagrid-content">{{ $job_application->position }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">状态</div>
                        <div class="datagrid-content"><span class="badge {{ $currentStep['class'] }}">{{ $currentStep['label'] }}</span></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">投递渠道</div>
                        <div class="datagrid-content">{{ $job_application->channel ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">截止日期</div>
                        <div class="datagrid-content">{{ $job_application->deadline ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">创建时间</div>
                        <div class="datagrid-content">{{ $job_application->created_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 所属用户 --}}
        @if($job_application->user)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">所属用户</h3>
            </div>
            <div class="card-body text-center">
                <img src="{{ \Illuminate\Support\Str::avatarSvg($job_application->user->email, 64) }}" class="avatar avatar-lg mb-2" alt="">
                <div class="font-weight-medium">{{ $job_application->user->name }}</div>
                <div class="text-secondary small">{{ $job_application->user->email }}</div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.users.show', $job_application->user) }}" class="btn btn-outline-primary w-100 btn-sm">
                    <i class="ti ti-user me-1"></i>查看用户详情
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
