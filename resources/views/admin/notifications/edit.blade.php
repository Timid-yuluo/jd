@extends('layouts.admin')

@section('title', '编辑通知')
@section('page-pretitle', '通知中心')
@section('page-title', '编辑通知')

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">通知内容</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.notifications.update', $notification) }}" method="POST" id="notification-form">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label required">通知标题</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $notification->title) }}" required maxlength="120">
                        <div class="form-hint">最多 120 个字符</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">通知类型</label>
                        <div class="form-selectgroup">
                            <label class="form-selectgroup-item">
                                <input type="radio" name="type" value="announcement" class="form-selectgroup-input" {{ old('type', $notification->type) === 'announcement' ? 'checked' : '' }}>
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-speakerphone text-blue me-1"></i>系统公告
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="type" value="feature" class="form-selectgroup-input" {{ old('type', $notification->type) === 'feature' ? 'checked' : '' }}>
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-rocket text-green me-1"></i>功能更新
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="type" value="maintenance" class="form-selectgroup-input" {{ old('type', $notification->type) === 'maintenance' ? 'checked' : '' }}>
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-tool text-yellow me-1"></i>维护通知
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="type" value="warning" class="form-selectgroup-input" {{ old('type', $notification->type) === 'warning' ? 'checked' : '' }}>
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-alert-triangle text-red me-1"></i>重要提醒
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">通知内容</label>
                        <textarea name="content" id="content-editor" class="form-control" rows="10" required maxlength="5000" data-tinymce data-tinymce-height="320">{{ old('content', $notification->content) }}</textarea>
                        <div class="form-hint">支持富文本格式，最多 5000 个字符</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">发送渠道</label>
                        <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                            <label class="form-selectgroup-item flex-fill mb-2">
                                <input type="radio" name="channel" value="site" class="form-selectgroup-input" {{ old('channel', $notification->channel) === 'site' ? 'checked' : '' }} data-toggle="channel-options">
                                <div class="form-selectgroup-label d-flex align-items-center p-3">
                                    <div class="me-3">
                                        <span class="form-selectgroup-check"></span>
                                    </div>
                                    <div>
                                        <span class="fw-bold"><i class="ti ti-browser me-1 text-blue"></i>仅站内通知</span>
                                        <div class="text-secondary small">仅发送站内消息，不发送邮件</div>
                                    </div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-fill mb-2">
                                <input type="radio" name="channel" value="email" class="form-selectgroup-input" {{ old('channel', $notification->channel) === 'email' ? 'checked' : '' }} data-toggle="channel-options">
                                <div class="form-selectgroup-label d-flex align-items-center p-3">
                                    <div class="me-3">
                                        <span class="form-selectgroup-check"></span>
                                    </div>
                                    <div>
                                        <span class="fw-bold"><i class="ti ti-mail me-1 text-green"></i>仅邮件</span>
                                        <div class="text-secondary small">仅发送邮件通知</div>
                                    </div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="channel" value="both" class="form-selectgroup-input" {{ old('channel', $notification->channel) === 'both' ? 'checked' : '' }} data-toggle="channel-options">
                                <div class="form-selectgroup-label d-flex align-items-center p-3">
                                    <div class="me-3">
                                        <span class="form-selectgroup-check"></span>
                                    </div>
                                    <div>
                                        <span class="fw-bold"><i class="ti ti-bell me-1 text-purple"></i>站内 + 邮件</span>
                                        <div class="text-secondary small">同时发送站内通知和邮件</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- 邮件模板选择 --}}
                    <div id="email-template-options" class="mb-3 {{ old('channel', $notification->channel) === 'site' ? 'd-none' : '' }}">
                        <label class="form-label">邮件模板</label>
                        <select name="email_template_id" class="form-select @error('email_template_id') is-invalid @enderror">
                            <option value="">-- 使用默认模板 --</option>
                            @foreach($emailTemplates as $template)
                            <option value="{{ $template->id }}" {{ old('email_template_id', $notification->email_template_id) == $template->id ? 'selected' : '' }}>
                                {{ $template->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('email_template_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">发送目标</label>
                        <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                            <label class="form-selectgroup-item flex-fill mb-2">
                                <input type="radio" name="target_type" value="all" class="form-selectgroup-input" {{ old('target_type', $notification->target_type) === 'all' ? 'checked' : '' }} data-toggle="target-options">
                                <div class="form-selectgroup-label d-flex align-items-center p-3">
                                    <div class="me-3">
                                        <span class="form-selectgroup-check"></span>
                                    </div>
                                    <div>
                                        <span class="fw-bold">所有用户</span>
                                        <div class="text-secondary small">通知将发送给所有注册用户</div>
                                    </div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-fill mb-2">
                                <input type="radio" name="target_type" value="roles" class="form-selectgroup-input" {{ old('target_type', $notification->target_type) === 'roles' ? 'checked' : '' }} data-toggle="target-options">
                                <div class="form-selectgroup-label d-flex align-items-center p-3">
                                    <div class="me-3">
                                        <span class="form-selectgroup-check"></span>
                                    </div>
                                    <div>
                                        <span class="fw-bold">指定角色</span>
                                        <div class="text-secondary small">仅发送给特定角色的用户</div>
                                    </div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="target_type" value="users" class="form-selectgroup-input" {{ old('target_type', $notification->target_type) === 'users' ? 'checked' : '' }} data-toggle="target-options">
                                <div class="form-selectgroup-label d-flex align-items-center p-3">
                                    <div class="me-3">
                                        <span class="form-selectgroup-check"></span>
                                    </div>
                                    <div>
                                        <span class="fw-bold">指定用户</span>
                                        <div class="text-secondary small">仅发送给选定的用户</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- 角色选择 --}}
                    <div id="roles-options" class="mb-3 {{ old('target_type', $notification->target_type) === 'roles' ? '' : 'd-none' }}">
                        <label class="form-label">选择角色</label>
                        <div class="form-selectgroup form-selectgroup-pills">
                            @foreach ($roles as $key => $label)
                            <label class="form-selectgroup-item">
                                <input type="checkbox" name="target_roles[]" value="{{ $key }}" class="form-selectgroup-input" {{ in_array($key, old('target_roles', $notification->target_roles ?? [])) ? 'checked' : '' }}>
                                <span class="form-selectgroup-label">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- 用户选择 --}}
                    <div id="users-options" class="mb-3 {{ old('target_type', $notification->target_type) === 'users' ? '' : 'd-none' }}">
                        <label class="form-label">选择用户</label>
                        <select name="target_users[]" class="form-select" multiple size="8">
                            @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ in_array($user->id, old('target_users', $notification->target_users ?? [])) ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                            @endforeach
                        </select>
                        <div class="form-hint">按住 Ctrl/Cmd 键可多选</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">发送方式</label>
                        <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                            @php
                                $currentSendType = $notification->isSent() ? 'sent' : ($notification->scheduled_at ? 'later' : 'draft');
                            @endphp
                            <label class="form-selectgroup-item flex-fill mb-2">
                                <input type="radio" name="send_type" value="draft" class="form-selectgroup-input" {{ old('send_type', $currentSendType) === 'draft' ? 'checked' : '' }} data-toggle="send-type-options">
                                <div class="form-selectgroup-label d-flex align-items-center p-3">
                                    <div class="me-3">
                                        <span class="form-selectgroup-check"></span>
                                    </div>
                                    <div>
                                        <span class="fw-bold"><i class="ti ti-pencil me-1 text-secondary"></i>保存为草稿</span>
                                        <div class="text-secondary small">稍后手动发送</div>
                                    </div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-fill mb-2">
                                <input type="radio" name="send_type" value="now" class="form-selectgroup-input" {{ old('send_type', $currentSendType) === 'now' ? 'checked' : '' }} data-toggle="send-type-options">
                                <div class="form-selectgroup-label d-flex align-items-center p-3">
                                    <div class="me-3">
                                        <span class="form-selectgroup-check"></span>
                                    </div>
                                    <div>
                                        <span class="fw-bold"><i class="ti ti-send me-1 text-blue"></i>立即发送</span>
                                        <div class="text-secondary small">保存后立即发送给目标用户</div>
                                    </div>
                                </div>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="send_type" value="later" class="form-selectgroup-input" {{ old('send_type', $currentSendType) === 'later' ? 'checked' : '' }} data-toggle="send-type-options">
                                <div class="form-selectgroup-label d-flex align-items-center p-3">
                                    <div class="me-3">
                                        <span class="form-selectgroup-check"></span>
                                    </div>
                                    <div>
                                        <span class="fw-bold"><i class="ti ti-clock me-1 text-green"></i>定时发送</span>
                                        <div class="text-secondary small">在指定时间自动发送</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- 定时发送时间选择 --}}
                    <div id="scheduled-time-options" class="mb-3 {{ old('send_type', $currentSendType) === 'later' ? '' : 'd-none' }}">
                        <label class="form-label required">发送时间</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control @error('scheduled_at') is-invalid @enderror" value="{{ old('scheduled_at', $notification->scheduled_at?->format('Y-m-d\TH:i')) }}">
                        <div class="form-hint">选择未来的时间</div>
                        @error('scheduled_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-footer">
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-link">取消</a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="ti ti-device-floppy me-1"></i>保存通知
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">说明</h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="ti ti-info-circle text-info me-1"></i> 此通知创建时间：{{ $notification->created_at->format('Y-m-d H:i:s') }}</li>
                    <li class="mb-2"><i class="ti ti-info-circle text-info me-1"></i> 不勾选「立即发送」将保持为草稿</li>
                    <li><i class="ti ti-info-circle text-info me-1"></i> 通知内容支持 HTML 标签</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">预览</h3>
            </div>
            <div class="card-body">
                <div class="alert" id="preview-alert">
                    <h5 class="alert-title" id="preview-title">{{ $notification->title }}</h5>
                    <div class="text-secondary" id="preview-content">{!! App\Support\HtmlPurifier::clean($notification->content) !!}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/admin-notifications-edit.css') }}">
@endpush

@push('scripts')
<script src="/vendor/tinymce/tinymce.min.js"></script>
<script src="{{ asset('js/pages/shared-tinymce-editor.js') }}"></script>
<script src="{{ asset('js/pages/admin-notifications-create.js') }}"></script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/admin-notifications-edit.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/pages/admin-notifications-edit.js') }}"></script>
@endpush