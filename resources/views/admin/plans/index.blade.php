@extends('layouts.admin')

@section('title', '套餐管理')
@section('page-pretitle', '会员管理')
@section('page-title', '套餐管理')

@section('page-actions')
<a href="{{ route('admin.plans.create') }}" class="btn btn-primary">
    <i class="ti ti-plus me-1"></i>新建套餐
</a>
@endsection

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>标识</th>
                    <th>名称</th>
                    <th>月价</th>
                    <th>年价</th>
                    <th>排序</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plans as $plan)
                    <tr>
                        <td><span class="badge bg-primary-lt">{{ $plan->slug }}</span></td>
                        <td class="fw-semibold">{{ $plan->name }}</td>
                        <td>¥{{ $plan->price_monthly / 100 }}</td>
                        <td>¥{{ $plan->price_yearly / 100 }}</td>
                        <td>{{ $plan->sort_order }}</td>
                        <td>
                            @if($plan->is_active)
                                <span class="badge bg-success-lt">启用</span>
                            @else
                                <span class="badge bg-secondary-lt">停用</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-edit"></i>
                            </a>
                            @if($plan->slug !== 'free')
                                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="d-inline" data-confirm-submit="确定删除？">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
