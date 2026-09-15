@extends('layouts.user')

@section('title', '通知偏好设置')

@section('content')
<div data-route-user-notification-preferences-update="{{ route('user.notification-preferences.update') }}" data-route-user-notification-preferences-reset="{{ route('user.notification-preferences.reset') }}" data-route-user-notification-preferences-test="{{ route('user.notification-preferences.test') }}">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="bi bi-bell-gear"></i> 通知偏好设置</h4>
                </div>
                <div class="card-body">
                    <form id="preferenceForm">
                        @csrf

                        <!-- 总开关 -->
                        <div class="mb-4">
                            <div class="form-check form-switch form-check-reverse">
                                <input class="form-check-input" type="checkbox" id="email_notifications_enabled"
                                    name="email_notifications_enabled" value="1"
                                    {{ $preferences['email_notifications_enabled'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="email_notifications_enabled">
                                    <strong>启用邮件通知</strong>
                                    <small class="d-block text-muted">关闭后将不再接收任何邮件通知</small>
                                </label>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- 简历相关 -->
                        <h6 class="text-muted mb-3">简历相关</h6>
                        <div class="mb-3 ms-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input preference-check" type="checkbox"
                                    id="notify_resume_completed" name="notify_resume_completed" value="1"
                                    {{ $preferences['notify_resume_completed'] ? 'checked' : '' }}
                                    {{ $preferences['email_notifications_enabled'] ? '' : 'disabled' }}>
                                <label class="form-check-label" for="notify_resume_completed">
                                    AI 岗位定向优化完成通知
                                    <small class="d-block text-muted">当您的 AI 岗位定向优化完成时发送通知</small>
                                </label>
                            </div>
                        </div>

                        <!-- 面试相关 -->
                        <h6 class="text-muted mb-3 mt-4">面试相关</h6>
                        <div class="mb-3 ms-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input preference-check" type="checkbox"
                                    id="notify_interview_started" name="notify_interview_started" value="1"
                                    {{ $preferences['notify_interview_started'] ? 'checked' : '' }}
                                    {{ $preferences['email_notifications_enabled'] ? '' : 'disabled' }}>
                                <label class="form-check-label" for="notify_interview_started">
                                    面试模拟开始通知
                                    <small class="d-block text-muted">当您开始新的面试模拟时发送通知</small>
                                </label>
                            </div>
                        </div>
                        <div class="mb-3 ms-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input preference-check" type="checkbox"
                                    id="notify_interview_completed" name="notify_interview_completed" value="1"
                                    {{ $preferences['notify_interview_completed'] ? 'checked' : '' }}
                                    {{ $preferences['email_notifications_enabled'] ? '' : 'disabled' }}>
                                <label class="form-check-label" for="notify_interview_completed">
                                    面试模拟完成通知
                                    <small class="d-block text-muted">当面试模拟完成并生成报告时发送通知</small>
                                </label>
                            </div>
                        </div>

                        <!-- 职位申请相关 -->
                        <h6 class="text-muted mb-3 mt-4">职位申请相关</h6>
                        <div class="mb-3 ms-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input preference-check" type="checkbox"
                                    id="notify_job_application" name="notify_job_application" value="1"
                                    {{ $preferences['notify_job_application'] ? 'checked' : '' }}
                                    {{ $preferences['email_notifications_enabled'] ? '' : 'disabled' }}>
                                <label class="form-check-label" for="notify_job_application">
                                    申请状态变更通知
                                    <small class="d-block text-muted">当您的申请状态发生变化时发送通知</small>
                                </label>
                            </div>
                        </div>
                        <div class="mb-3 ms-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input preference-check" type="checkbox"
                                    id="notify_deadline_reminder" name="notify_deadline_reminder" value="1"
                                    {{ $preferences['notify_deadline_reminder'] ? 'checked' : '' }}
                                    {{ $preferences['email_notifications_enabled'] ? '' : 'disabled' }}>
                                <label class="form-check-label" for="notify_deadline_reminder">
                                    截止日提醒
                                    <small class="d-block text-muted">在申请截止前3天发送提醒</small>
                                </label>
                            </div>
                        </div>

                        <!-- 营销邮件 -->
                        <h6 class="text-muted mb-3 mt-4">其他</h6>
                        <div class="mb-3 ms-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input preference-check" type="checkbox"
                                    id="notify_marketing" name="notify_marketing" value="1"
                                    {{ $preferences['notify_marketing'] ? 'checked' : '' }}
                                    {{ $preferences['email_notifications_enabled'] ? '' : 'disabled' }}>
                                <label class="form-check-label" for="notify_marketing">
                                    营销邮件
                                    <small class="d-block text-muted">接收产品更新、优惠活动等营销信息</small>
                                </label>
                            </div>
                        </div>
                        <div class="mb-3 ms-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input preference-check" type="checkbox"
                                    id="notify_weekly_digest" name="notify_weekly_digest" value="1"
                                    {{ $preferences['notify_weekly_digest'] ? 'checked' : '' }}
                                    {{ $preferences['email_notifications_enabled'] ? '' : 'disabled' }}>
                                <label class="form-check-label" for="notify_weekly_digest">
                                    每周求职摘要
                                    <small class="d-block text-muted">每周一接收求职进度邮件摘要</small>
                                </label>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" data-action="reset-preferences">
                                <i class="bi bi-arrow-counterclockwise"></i> 恢复默认
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> 保存设置
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 测试邮件 -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-envelope-paper"></i> 测试邮件</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">发送测试邮件验证您的邮箱配置是否正常</p>
                    <button type="button" class="btn btn-outline-primary" data-action="send-test-email">
                        <i class="bi bi-send"></i> 发送测试邮件
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/user-notification-preferences.js') }}"></script>
@endpush
