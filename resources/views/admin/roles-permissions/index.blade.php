@extends('layouts.admin')

@section('title', '角色权限管理')
@section('page-pretitle', '系统管理')
@section('page-title', '角色权限管理')

@section('page-actions')
<a href="{{ route('admin.roles-permissions.create') }}" class="btn btn-primary">
    <i class="ti ti-plus me-1"></i> 创建角色
</a>
<a href="{{ route('admin.roles-permissions.permissions') }}" class="btn btn-outline-primary ms-2">
    <i class="ti ti-shield me-1"></i> 权限列表
</a>
@endsection

@section('content')
<div data-url-admin-roles-permissions="{{ url('admin/roles-permissions') }}">
<div class="row row-cards mb-3">
    <div class="col-md-4">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-azure-lt avatar">
                            <i class="ti ti-users-group"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ $stats['total_roles'] }} 个角色</div>
                        <div class="text-secondary">系统中定义的角色数量</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-green-lt avatar">
                            <i class="ti ti-shield-check"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ $stats['total_permissions'] }} 个权限</div>
                        <div class="text-secondary">系统中定义的权限数量</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-purple-lt avatar">
                            <i class="ti ti-crown"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ $stats['super_admins'] }} 位超管</div>
                        <div class="text-secondary">拥有全部权限的用户</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">角色列表</h3>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter text-nowrap">
            <thead>
                <tr>
                    <th>角色名称</th>
                    <th>用户数量</th>
                    <th>权限数量</th>
                    <th>系统预设</th>
                    <th class="w-1">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-sm bg-azure-lt me-2">
                                {{ strtoupper(substr($role->name, 0, 1)) }}
                            </span>
                            <div>
                                <div class="font-weight-medium">{{ $role->name }}</div>
                                <div class="text-secondary small">Guard: {{ $role->guard_name }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('admin.users.index', ['role' => $role->name]) }}" class="badge bg-blue-lt">
                            {{ $role->users_count }} 位用户
                        </a>
                    </td>
                    <td>
                        <span class="badge bg-green-lt">{{ $role->permissions_count }} 个权限</span>
                    </td>
                    <td>
                        @if(in_array($role->name, ['super-admin', 'admin', 'editor']))
                            <span class="badge bg-yellow-lt">
                                <i class="ti ti-lock me-1"></i>系统预设
                            </span>
                        @else
                            <span class="badge bg-secondary-lt">自定义</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-list flex-nowrap">
                            <a href="{{ route('admin.roles-permissions.edit', $role) }}" class="btn btn-white btn-sm">
                                <i class="ti ti-edit me-1"></i> 编辑
                            </a>
                            @if(!in_array($role->name, ['super-admin', 'admin', 'editor']))
                            <button type="button" class="btn btn-white btn-sm text-danger" 
                                    data-action="confirm-delete" data-role-id="{{ $role->id }}" data-role-name="{{ $role->name }}">
                                <i class="ti ti-trash me-1"></i> 删除
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-secondary">
                        <i class="ti ti-inbox fs-2 mb-2 d-block"></i>
                        暂无角色数据
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger fs-1 mb-3"></i>
                <h4 class="mb-2">确认删除？</h4>
                <p class="text-secondary mb-0">确定要删除角色 <strong id="deleteRoleName"></strong> 吗？</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">取消</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">确认删除</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/admin-roles-permissions-index.js') }}"></script>
@endpush

