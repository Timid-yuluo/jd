@extends('layouts.admin')

@section('title', '编辑次卡商品')
@section('page-pretitle', '会员管理')
@section('page-title', '编辑次卡商品')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('admin.credit-packs.update', $creditPack) }}">
            @csrf @method('PUT')
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">商品标识 (slug)</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $creditPack->slug) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">商品名称</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $creditPack->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">适用类型 (quota_key)</label>
                        <select name="quota_key" class="form-select">
                            <option value="" {{ old('quota_key', $creditPack->quota_key) === '' ? 'selected' : '' }}>通用（可用于所有功能）</option>
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
                                <option value="{{ $key }}" {{ old('quota_key', $creditPack->quota_key) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">次数</label>
                            <input type="number" name="credits" class="form-control" value="{{ old('credits', $creditPack->credits) }}" min="1" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">价格（分）</label>
                            <input type="number" name="price" class="form-control" value="{{ old('price', $creditPack->price) }}" min="0" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">有效期（天，0=永久）</label>
                            <input type="number" name="validity_days" class="form-control" value="{{ old('validity_days', $creditPack->validity_days) }}" min="0">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">排序</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $creditPack->sort_order) }}" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $creditPack->is_active) ? 'checked' : '' }}>
                            <span class="form-check-label">启用</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">更新</button>
                <a href="{{ route('admin.credit-packs.index') }}" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
</div>
@endsection
