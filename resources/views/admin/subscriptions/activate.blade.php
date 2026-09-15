@extends('layouts.admin')

@section('title', '赠送开通套餐')
@section('page-pretitle', '会员管理')
@section('page-title', '赠送开通套餐')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">为用户赠送开通套餐</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-secondary small">用户</div>
                    <div class="fw-semibold">{{ $user->name }} ({{ $user->email }})</div>
                </div>

                <form method="POST" action="{{ route('admin.users.activate-plan.store', $user) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">选择套餐</label>
                        <select name="plan_id" class="form-select" required>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }}（¥{{ $plan->price_monthly / 100 }}/月）</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">计费周期</label>
                        <select name="billing_cycle" class="form-select" required>
                            <option value="monthly">月付</option>
                            <option value="yearly">年付</option>
                        </select>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">确认赠送</button>
                        <a href="#" data-action="go-back" class="btn btn-outline-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
