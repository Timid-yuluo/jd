@extends('layouts.admin')

@section('title', '简历详情')
@section('page-pretitle', '核心业务')
@section('page-title', '简历详情')

@section('page-actions')
<a href="{{ route('admin.resumes.index') }}" class="btn btn-sm btn-outline-secondary">
    <i class="ti ti-arrow-left me-1"></i>返回列表
</a>
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">简历信息</h3>
                <div class="card-actions">
                    <form action="{{ route('admin.resumes.ats-score', $resume) }}" method="POST" class="d-inline" data-app-confirm='确定要使用 AI 为该简历进行 ATS 评分吗？'>
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="ti ti-star me-1"></i>ATS 评分</button>
                    </form>
                    <form action="{{ route('admin.resumes.optimize', $resume) }}" method="POST" class="d-inline" data-app-confirm='确定要使用 AI 优化该简历吗？'>
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-purple"><i class="ti ti-sparkles me-1"></i>AI 优化</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">标题</div>
                        <div class="datagrid-content">{{ $resume->title }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">目标岗位</div>
                        <div class="datagrid-content">{{ $resume->target_job ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">ATS 分数</div>
                        <div class="datagrid-content">
                            @if($resume->ats_score > 0)
                                <span class="badge bg-green-lt text-success">{{ $resume->ats_score }}</span>
                            @else
                                <span class="badge bg-secondary-lt">未评</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">创建时间</div>
                        <div class="datagrid-content">{{ $resume->created_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">简历原始内容</h3>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded mb-0" style="white-space: pre-wrap; word-wrap: break-word;">{{ $resume->content_raw ?? '暂无内容' }}</pre>
            </div>
        </div>

        @if($resume->optimized_text)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">AI 优化内容</h3>
            </div>
            <div class="card-body">
                <pre class="bg-azure-lt p-3 rounded mb-0" style="white-space: pre-wrap; word-wrap: break-word;">{{ $resume->optimized_text }}</pre>
            </div>
        </div>
        @endif

        @if(!empty($resume->highlights))
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">AI 提取亮点</h3>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    @foreach($resume->highlights as $highlight)
                        <li>{{ $highlight }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        {{-- 用户信息 --}}
        @if($resume->user)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">所属用户</h3>
            </div>
            <div class="card-body text-center">
                <img src="{{ \Illuminate\Support\Str::avatarSvg($resume->user->email, 64) }}" class="avatar avatar-lg mb-2" alt="">
                <div class="font-weight-medium">{{ $resume->user->name }}</div>
                <div class="text-secondary small">{{ $resume->user->email }}</div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.users.show', $resume->user) }}" class="btn btn-outline-primary w-100 btn-sm">
                    <i class="ti ti-user me-1"></i>查看用户详情
                </a>
            </div>
        </div>
        @endif

        {{-- 快捷操作 --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">快捷操作</h3>
            </div>
            <div class="list-group list-group-flush">
                <form action="{{ route('admin.resumes.ats-score', $resume) }}" method="POST" data-app-confirm='确定要使用 AI 为该简历进行 ATS 评分吗？'>
                    @csrf
                    <button type="submit" class="list-group-item list-group-item-action">
                        <i class="ti ti-star me-2 text-warning"></i>重新 ATS 评分
                    </button>
                </form>
                <form action="{{ route('admin.resumes.optimize', $resume) }}" method="POST" data-app-confirm='确定要使用 AI 优化该简历吗？'>
                    @csrf
                    <button type="submit" class="list-group-item list-group-item-action">
                        <i class="ti ti-sparkles me-2 text-purple"></i>AI 优化简历
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
