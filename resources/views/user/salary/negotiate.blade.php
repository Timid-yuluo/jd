@extends('layouts.user')

@section('title', '薪资谈判助手')
@section('page-pretitle', '薪资助手')
@section('page-title', 'AI 薪资谈判')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-message-chatbot me-2"></i>谈判信息输入
                </h3>
            </div>
            <div class="card-body">
                @if(session('error'))
                <div class="alert alert-danger">
                    <i class="ti ti-alert-circle me-2"></i>{{ session('error') }}
                </div>
                @endif

                <form action="{{ route('user.salary.negotiate.start') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">岗位名称 <span class="text-danger">*</span></label>
                        <input type="text" name="job_title" class="form-control" required
                               value="{{ old('job_title') }}" placeholder="如：高级前端工程师">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">公司名称</label>
                            <input type="text" name="company" class="form-control"
                                   value="{{ old('company') }}" placeholder="如：字节跳动">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">城市</label>
                            <input type="text" name="city" class="form-control"
                                   value="{{ old('city') }}" placeholder="如：北京">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">当前薪资（元/月） <span class="text-danger">*</span></label>
                            <input type="number" name="current_salary" class="form-control" required min="0"
                                   value="{{ old('current_salary') }}" placeholder="如：20000">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">期望薪资（元/月） <span class="text-danger">*</span></label>
                            <input type="number" name="target_salary" class="form-control" required min="0"
                                   value="{{ old('target_salary') }}" placeholder="如：30000">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">经验年限</label>
                        <select name="experience_years" class="form-select">
                            <option value="">请选择</option>
                            <option value="0-1" @selected(old('experience_years') === '0-1')>0-1 年</option>
                            <option value="1-3" @selected(old('experience_years') === '1-3')>1-3 年</option>
                            <option value="3-5" @selected(old('experience_years') === '3-5')>3-5 年</option>
                            <option value="5-10" @selected(old('experience_years') === '5-10')>5-10 年</option>
                            <option value="10+" @selected(old('experience_years') === '10+')>10 年以上</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">补充信息</label>
                        <textarea name="context" class="form-control" rows="3"
                                  placeholder="如：已有其他公司 Offer、有期权、特殊技能等">{{ old('context') }}</textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-sparkles me-1"></i>生成谈判策略
                        </button>
                        <a href="{{ route('user.salary.index') }}" class="btn btn-link">返回</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
