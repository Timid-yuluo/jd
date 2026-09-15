@extends('layouts.admin')

@section('title', '次卡商品')
@section('page-pretitle', '会员管理')
@section('page-title', '次卡商品管理')

@section('page-actions')
<a href="{{ route('admin.credit-packs.create') }}" class="btn btn-primary">
    <i class="ti ti-plus me-1"></i>新建次卡商品
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
                    <th>类型</th>
                    <th>次数</th>
                    <th>价格</th>
                    <th>有效期</th>
                    <th>排序</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($packs as $pack)
                    <tr>
                        <td><span class="badge bg-primary-lt">{{ $pack->slug }}</span></td>
                        <td class="fw-semibold">{{ $pack->name }}</td>
                        <td>
                            @if($pack->quota_key === null)
                                <span class="badge bg-azure-lt">通用</span>
                            @else
                                <span class="badge bg-secondary-lt">{{ $pack->quota_key }}</span>
                            @endif
                        </td>
                        <td>{{ $pack->credits }}次</td>
                        <td>¥{{ $pack->price / 100 }}</td>
                        <td>{{ $pack->validity_days > 0 ? $pack->validity_days . '天' : '永久' }}</td>
                        <td>{{ $pack->sort_order }}</td>
                        <td>
                            @if($pack->is_active)
                                <span class="badge bg-success-lt">启用</span>
                            @else
                                <span class="badge bg-secondary-lt">停用</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.credit-packs.edit', $pack) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.credit-packs.destroy', $pack) }}" class="d-inline" data-confirm-submit="确定删除？">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
