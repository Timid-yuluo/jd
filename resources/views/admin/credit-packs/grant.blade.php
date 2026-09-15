@extends('layouts.admin')

@section('title', '赠送次卡')
@section('page-pretitle', '会员管理')
@section('page-title', '赠送次卡')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">为用户赠送次卡</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-secondary small">用户</div>
                    <div class="fw-semibold">{{ $user->name }} ({{ $user->email }})</div>
                </div>

                <form method="POST" action="{{ route('admin.users.grant-credits.store', $user) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">适用类型</label>
                        <select name="quota_key" class="form-select">
                            <option value="">通用（可用于所有功能）</option>
                            @php
                                $quotaKeys = [
                                    'optimize_full' => '简历优化',
                                    'optimize_section' => '分段优化',
                                    'ats_score' => 'ATS评分',
                                    'keywords_extract' => '关键词提取',
                                    'import_document' => 'AI导入简历',
                                    'interview_sessions' => 'AI面试',
                                    'interview_evaluation' => '面试评估',
                                    'job_match' => '岗位匹配',
                                    'match_analysis' => '匹配分析',
                                ];
                            @endphp
                            @foreach($quotaKeys as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">次数</label>
                        <input type="number" name="credits" class="form-control" value="{{ old('credits', 10) }}" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">有效期（天，留空=永久）</label>
                        <input type="number" name="validity_days" class="form-control" value="{{ old('validity_days') }}" min="0">
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
