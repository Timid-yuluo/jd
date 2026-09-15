@extends('layouts.admin')

@section('title', '编辑投递记录')
@section('page-pretitle', '业务中心')
@section('page-title', '编辑投递记录')

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <form action="{{ route('admin.job-applications.update', $job_application) }}" method="POST" class="card">
            @csrf
            @method('PUT')
            <div class="card-header">
                <h3 class="card-title">投递信息</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">公司</label>
                        <div>
                            <input type="text" name="company" class="form-control @error('company') is-invalid @enderror" value="{{ old('company', $job_application->company) }}" required>
                            @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">岗位</label>
                        <div>
                            <input type="text" name="position" class="form-control @error('position') is-invalid @enderror" value="{{ old('position', $job_application->position) }}" required>
                            @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label required">状态</label>
                        <div>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="wishlist" {{ old('status', $job_application->status) === 'wishlist' ? 'selected' : '' }}>愿望单 (wishlist)</option>
                                <option value="applied" {{ old('status', $job_application->status) === 'applied' ? 'selected' : '' }}>已投递 (applied)</option>
                                <option value="written" {{ old('status', $job_application->status) === 'written' ? 'selected' : '' }}>笔试 (written)</option>
                                <option value="interview" {{ old('status', $job_application->status) === 'interview' ? 'selected' : '' }}>面试 (interview)</option>
                                <option value="offer" {{ old('status', $job_application->status) === 'offer' ? 'selected' : '' }}>Offer (offer)</option>
                                <option value="rejected" {{ old('status', $job_application->status) === 'rejected' ? 'selected' : '' }}>淘汰 (rejected)</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">投递渠道</label>
                        <div>
                            <input type="text" name="channel" class="form-control @error('channel') is-invalid @enderror" value="{{ old('channel', $job_application->channel) }}">
                            @error('channel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">截止日期</label>
                        <div>
                            <input type="date" name="deadline" class="form-control @error('deadline') is-invalid @enderror" value="{{ old('deadline', $job_application->deadline) }}">
                            @error('deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">备注</label>
                        <div>
                            <textarea name="note" class="form-control @error('note') is-invalid @enderror" rows="4">{{ old('note', $job_application->note) }}</textarea>
                            @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.job-applications.index') }}" class="btn btn-link">返回</a>
                    <button type="submit" class="btn btn-primary ms-auto">保存更改</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection