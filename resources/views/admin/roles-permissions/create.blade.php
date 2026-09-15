@extends('layouts.admin')

@section('title', '创建角色')
@section('page-pretitle', '角色权限管理')
@section('page-title', '创建角色')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <form action="{{ route('admin.roles-permissions.store') }}" method="POST" class="card">
            @csrf
            <div class="card-header">
                <h3 class="card-title">基本信息</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required">角色标识</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                           placeholder="例如: content-manager" value="{{ old('name') }}" required>
                    <div class="form-hint">唯一标识，建议使用英文小写和连字符</div>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">显示名称</label>
                    <input type="text" name="display_name" class="form-control" 
                           placeholder="例如: 内容管理员" value="{{ old('display_name') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">描述</label>
                    <textarea name="description" class="form-control" rows="2" 
                              placeholder="角色的职责描述">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="card-header border-top">
                <h3 class="card-title">权限分配</h3>
            </div>
            <div class="card-body">
                @foreach($groups as $key => $group)
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-2">
                        <i class="ti {{ $group['icon'] }} me-2 text-primary"></i>
                        <h4 class="m-0">{{ $group['label'] }}</h4>
                        <label class="form-check ms-auto mb-0">
                            <input type="checkbox" class="form-check-input group-check-all" 
                                   data-group="{{ $key }}">
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
                                       {{ in_array($permission['name'], old('permissions', [])) ? 'checked' : '' }}>
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
            </div>

            <div class="card-footer text-end">
                <a href="{{ route('admin.roles-permissions.index') }}" class="btn btn-link">取消</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> 创建角色
                </button>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">权限说明</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h5 class="alert-title">关于权限继承</h5>
                    <p class="mb-0">角色之间相互独立，权限不继承。super-admin 角色拥有所有权限且无法修改。</p>
                </div>
                <div class="alert alert-warning">
                    <h5 class="alert-title">权限组合建议</h5>
                    <ul class="mb-0">
                        <li><strong>内容管理员:</strong> 简历、面试、投递管理权限</li>
                        <li><strong>系统运维:</strong> 系统设置、运维操作权限</li>
                        <li><strong>审计人员:</strong> 日志查看、审计查看权限</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/admin-roles-permissions-create.js') }}"></script>
@endpush
