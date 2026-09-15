@extends('layouts.admin')

@section('title', '邮件模板')
@section('page-pretitle', '通知中心')
@section('page-title', '邮件模板管理')

@section('page-actions')
<a href="{{ route('admin.notifications.email-templates.create') }}" class="btn btn-primary">
    <i class="ti ti-plus me-1"></i>新建模板
</a>
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">模板列表</h3>
                <div class="card-actions">
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="ti ti-bell me-1"></i>返回通知中心
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>模板名称</th>
                            <th>标识符</th>
                            <th>邮件主题</th>
                            <th>状态</th>
                            <th>创建时间</th>
                            <th class="w-1">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $template)
                        <tr>
                            <td>
                                <div class="font-weight-medium">{{ $template->name }}</div>
                                @if($template->description)
                                <div class="text-secondary text-truncate" style="max-width: 300px;">
                                    {{ $template->description }}
                                </div>
                                @endif
                            </td>
                            <td><code>{{ $template->slug }}</code></td>
                            <td class="text-truncate" style="max-width: 250px;">{{ $template->subject }}</td>
                            <td>
                                @if($template->is_active)
                                    <span class="badge bg-success-lt text-success">启用</span>
                                @else
                                    <span class="badge bg-secondary-lt">禁用</span>
                                @endif
                            </td>
                            <td>{{ $template->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="{{ route('admin.notifications.email-templates.edit', $template) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.notifications.email-templates.destroy', $template) }}" method="POST" class="d-inline" data-app-confirm='确定要删除此模板吗？'>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">
                                <i class="ti ti-mail-off mb-2 d-block" style="font-size: 2rem;"></i>
                                暂无邮件模板
                                <br>
                                <a href="{{ route('admin.notifications.email-templates.create') }}" class="btn btn-sm btn-primary mt-2">创建第一个模板</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($templates->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $templates->links() }}
            </div>
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="alert alert-info" role="alert">
            <h4 class="alert-title"><i class="ti ti-info-circle me-1"></i>使用说明</h4>
            <p class="mb-0">邮件模板支持以下变量：</p>
            <ul class="mb-0 mt-1">
                <li><code>{{ '{' . '{ site_name }' . '}' }}</code> - 网站名称</li>
                <li><code>{{ '{' . '{ user_name }' . '}' }}</code> - 用户名称</li>
                <li><code>{{ '{' . '{ user_email }' . '}' }}</code> - 用户邮箱</li>
                <li><code>{{ '{' . '{ notification_title }' . '}' }}</code> - 通知标题</li>
                <li><code>{{ '{' . '{ notification_content }' . '}' }}</code> - 通知内容</li>
            </ul>
        </div>
    </div>
</div>
@endsection
