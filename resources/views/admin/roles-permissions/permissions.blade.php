@extends('layouts.admin')

@section('title', '权限列表')
@section('page-pretitle', '角色权限管理')
@section('page-title', '权限列表')

@section('page-actions')
<form method="POST" action="{{ route('admin.roles-permissions.sync-permissions') }}" class="d-inline" data-app-confirm="确定要同步权限吗？这将根据配置添加新的权限。">
    @csrf
    <button type="submit" class="btn btn-primary">
        <i class="ti ti-refresh me-1"></i> 同步权限
    </button>
</form>
<a href="{{ route('admin.roles-permissions.index') }}" class="btn btn-outline-primary ms-2">
    <i class="ti ti-arrow-left me-1"></i> 返回角色列表
</a>
@endsection

@section('content')
<div class="row row-cards">
    @foreach($groups as $key => $group)
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <i class="ti {{ $group['icon'] }} me-2 text-primary"></i>
                    <h3 class="card-title m-0">{{ $group['label'] }}</h3>
                    <span class="badge bg-blue-lt ms-auto">{{ count($group['permissions']) }}</span>
                </div>
            </div>
            <div class="list-group list-group-flush">
                @foreach($group['permissions'] as $permission)
                @php
                    $permModel = $allPermissions->get($permission['name']);
                    $usageCount = $permModel ? ($permissionStats[$permModel->id] ?? 0) : 0;
                @endphp
                <div class="list-group-item d-flex align-items-center justify-content-between py-2">
                    <div>
                        <div class="font-weight-medium">{{ $permission['label'] }}</div>
                        <div class="text-secondary small font-monospace">{{ $permission['name'] }}</div>
                    </div>
                    <div class="text-end">
                        @if($permModel)
                            <span class="badge bg-green-lt" title="被 {{ $usageCount }} 个角色使用">
                                {{ $usageCount }}
                            </span>
                        @else
                            <span class="badge bg-red-lt" title="权限未创建">未同步</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">权限统计</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="card card-sm bg-azure-lt">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="h1 mb-0 me-3">{{ $allPermissions->count() }}</div>
                            <div>
                                <div class="font-weight-medium">已创建权限</div>
                                <div class="text-secondary small">数据库中的权限总数</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-sm bg-green-lt">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="h1 mb-0 me-3">
                                {{ collect($groups)->flatMap(fn($g) => $g['permissions'])->count() }}
                            </div>
                            <div>
                                <div class="font-weight-medium">配置权限</div>
                                <div class="text-secondary small">系统定义的权限总数</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-sm bg-yellow-lt">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="h1 mb-0 me-3">
                                {{ collect($groups)->flatMap(fn($g) => $g['permissions'])->filter(fn($p) => !$allPermissions->has($p['name']))->count() }}
                            </div>
                            <div>
                                <div class="font-weight-medium">待同步权限</div>
                                <div class="text-secondary small">尚未创建的权限</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-sm bg-purple-lt">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="h1 mb-0 me-3">
                                {{ array_sum($permissionStats) }}
                            </div>
                            <div>
                                <div class="font-weight-medium">权限引用</div>
                                <div class="text-secondary small">角色-权限关联总数</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
