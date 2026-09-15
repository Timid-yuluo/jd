@extends('layouts.admin')

@section('title', '意见反馈')
@section('page-pretitle', '内容管理')
@section('page-title', '意见反馈')

@section('content')
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col-auto">
            <h2 class="page-title">意见反馈</h2>
        </div>
    </div>
</div>

<div class="row row-cards mb-4">
    <div class="col-sm-6 col-lg-2">
        <a href="{{ route('admin.feedbacks.index') }}" class="card card-sm card-link">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-primary text-white avatar"><i class="ti ti-messages"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($counts['total']) }}</div><div class="text-secondary">全部</div></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-2">
        <a href="{{ route('admin.feedbacks.index', ['status' => 'pending']) }}" class="card card-sm card-link">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-warning text-white avatar"><i class="ti ti-clock"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($counts['pending']) }}</div><div class="text-secondary">待处理</div></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-2">
        <a href="{{ route('admin.feedbacks.index', ['status' => 'processing']) }}" class="card card-sm card-link">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-info text-white avatar"><i class="ti ti-loader"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($counts['processing']) }}</div><div class="text-secondary">处理中</div></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-2">
        <a href="{{ route('admin.feedbacks.index', ['status' => 'replied']) }}" class="card card-sm card-link">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-success text-white avatar"><i class="ti ti-check"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($counts['replied']) }}</div><div class="text-secondary">已回复</div></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-2">
        <a href="{{ route('admin.feedbacks.index', ['status' => 'closed']) }}" class="card card-sm card-link">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-secondary text-white avatar"><i class="ti ti-circle-x"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($counts['closed']) }}</div><div class="text-secondary">已关闭</div></div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">反馈列表</h3>
        <div class="card-actions">
            <form method="GET" id="filterForm" class="d-flex gap-2">
                <input type="text" name="search" id="searchInput" class="form-control form-control-sm" placeholder="搜索标题/内容" value="{{ request('search') }}" style="width:160px">
                <select name="status" class="form-select form-select-sm" style="width:120px" data-auto-submit>
                    <option value="">全部状态</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>待处理</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>处理中</option>
                    <option value="replied" {{ request('status') === 'replied' ? 'selected' : '' }}>已回复</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>已关闭</option>
                </select>
                <select name="category" class="form-select form-select-sm" style="width:120px" data-auto-submit>
                    <option value="">全部类型</option>
                    <option value="bug" {{ request('category') === 'bug' ? 'selected' : '' }}>Bug 报告</option>
                    <option value="suggestion" {{ request('category') === 'suggestion' ? 'selected' : '' }}>功能建议</option>
                    <option value="ux" {{ request('category') === 'ux' ? 'selected' : '' }}>体验问题</option>
                    <option value="other" {{ request('category') === 'other' ? 'selected' : '' }}>其他</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-search"></i></button>
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter text-nowrap">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>类型</th>
                    <th>标题</th>
                    <th>用户</th>
                    <th>页面</th>
                    <th>状态</th>
                    <th>优先级</th>
                    <th>回复</th>
                    <th>时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($feedbacks as $fb)
                <tr>
                    <td>{{ $fb->id }}</td>
                    <td>
                        @if($fb->category === 'bug')
                            <span class="badge bg-red-lt"><i class="ti ti-bug me-1"></i>Bug</span>
                        @elseif($fb->category === 'suggestion')
                            <span class="badge bg-blue-lt"><i class="ti ti-bulb me-1"></i>建议</span>
                        @elseif($fb->category === 'ux')
                            <span class="badge bg-yellow-lt"><i class="ti ti-eyeglass me-1"></i>体验</span>
                        @else
                            <span class="badge bg-secondary-lt"><i class="ti ti-dots me-1"></i>其他</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.feedbacks.show', $fb) }}" class="text-reset fw-medium">
                            {{ Str::limit($fb->title, 40) }}
                        </a>
                    </td>
                    <td>
                        @if($fb->user)
                            <span>{{ $fb->user->name }}</span>
                        @elseif($fb->visitor_email)
                            <span class="text-secondary">{{ Str::limit($fb->visitor_email, 20) }}</span>
                        @else
                            <span class="text-secondary">匿名</span>
                        @endif
                    </td>
                    <td>
                        @if($fb->page_name)
                            <span class="text-secondary" title="{{ $fb->page_url }}">{{ Str::limit($fb->page_name, 20) }}</span>
                        @else
                            <span class="text-secondary">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $fb->getStatusColor() }}-lt">{{ $fb->getStatusLabel() }}</span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $fb->getPriorityColor() }}-lt">{{ $fb->getPriorityLabel() }}</span>
                    </td>
                    <td>
                        <span class="badge bg-secondary-lt">{{ $fb->replies_count }}</span>
                    </td>
                    <td class="text-secondary">{{ $fb->created_at->format('m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.feedbacks.show', $fb) }}" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-eye"></i> 查看
                        </a>
                    </td>
                </tr>
                @endforeach
                @if($feedbacks->isEmpty())
                <tr><td colspan="10" class="text-center text-secondary py-4">暂无反馈数据</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex align-items-center">
        <p class="text-secondary m-0">共 {{ $feedbacks->total() }} 条</p>
        <div class="ms-auto">{{ $feedbacks->links() }}</div>
    </div>
</div>
@endsection

@push('scripts')
('scripts')
<script src="{{ asset('js/pages/admin-feedbacks-index.js') }}"></script>

@endpush
