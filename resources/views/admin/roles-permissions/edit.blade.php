@extends('layouts.admin')

@section('title', '编辑角色')
@section('page-pretitle', '角色权限管理')
@section('page-title', '编辑角色: ' . $role->name)

@section('content')
<div data-url-admin-roles-permissions="{{ url('admin/roles-permissions') }}">
<div class="row">
    <div class="col-lg-8">
        <form action="{{ route('admin.roles-permissions.update', $role) }}" method="POST" class="card">
            @csrf
            @method('PUT')
            <div class="card-header">
                <h3 class="card-title">基本信息</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required">角色标识</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $role->name) }}" 
                           {{ in_array($role->name, ['super-admin', 'admin', 'editor']) ? 'readonly' : '' }} required>
                    @if(in_array($role->name, ['super-admin', 'admin', 'editor']))
                        <div class="form-hint text-warning">
                            <i class="ti ti-lock me-1"></i>系统预设角色标识不可修改
                        </div>
                    @else
                        <div class="form-hint">唯一标识，建议使用英文小写和连字符</div>
                    @endif
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">显示名称</label>
                    <input type="text" name="display_name" class="form-control" 
                           value="{{ old('display_name', $role->display_name ?? '') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">描述</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $role->description ?? '') }}</textarea>
                </div>
            </div>

            <div class="card-header border-top">
                <h3 class="card-title">权限分配</h3>
            </div>
            <div class="card-body">
                @if($role->name === 'super-admin')
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-1"></i>
                        super-admin 角色自动拥有所有权限，无需手动配置。
                    </div>
                @else
                    @foreach($groups as $key => $group)
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="ti {{ $group['icon'] }} me-2 text-primary"></i>
                            <h4 class="m-0">{{ $group['label'] }}</h4>
                            <label class="form-check ms-auto mb-0">
                                <input type="checkbox" class="form-check-input group-check-all" 
                                       data-group="{{ $key }}"
                                       {{ collect($group['permissions'])->every(fn($p) => in_array($p['name'], $rolePermissions)) ? 'checked' : '' }}>
                                <span class="form-check-label">全选</span>
                            </label>
                        </div>
                        <div class="row group-permissions" data-group="{{ $key }}">
                            @foreach($group['permissions'] as $permission)
                            <div class="col-md-6 col-lg-4 mb-2">
                                <label class="form-check">
                                    <input type="checkbox" name="permissions[]" 
                                           value="{{ $permission['name'] }}" 
                                           class="form-check-input permission-check"
                                           data-group="{{ $key }}"
                                           {{ in_array($permission['name'], old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ $permission['label'] }}</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @if(!$loop->last)
                        <hr class="my-4">
                    @endif
                    @endforeach
                @endif
            </div>

            <div class="card-footer text-end">
                <a href="{{ route('admin.roles-permissions.index') }}" class="btn btn-link">取消</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-check me-1"></i> 保存修改
                </button>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">角色信息</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">创建时间</div>
                        <div class="datagrid-content">{{ $role->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">最后更新</div>
                        <div class="datagrid-content">{{ $role->updated_at->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">当前用户数</div>
                        <div class="datagrid-content">
                            <a href="{{ route('admin.users.index', ['role' => $role->name]) }}" class="badge bg-blue-lt">
                                {{ $role->users->count() }} 位用户
                            </a>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Guard</div>
                        <div class="datagrid-content">{{ $role->guard_name }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if(!in_array($role->name, ['super-admin', 'admin', 'editor']))
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">危险操作</h3>
            </div>
            <div class="card-body">
                <button type="button" class="btn btn-outline-danger w-100" 
                        data-action="confirm-delete" data-role-id="{{ $role->id }}" data-role-name="{{ $role->name }}">
                    <i class="ti ti-trash me-1"></i> 删除此角色
                </button>
            </div>
        </div>
        @endif
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
                <p class="text-danger small mb-0 mt-2">此操作不可恢复！</p>
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
<script src="{{ asset('js/pages/admin-roles-permissions-edit.js') }}"></script>
@endpush

