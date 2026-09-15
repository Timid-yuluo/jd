<?php

use App\Http\Controllers\Admin\ActionLogController;
use App\Http\Controllers\Admin\AccessBanController;
use App\Http\Controllers\Admin\AiAnalyticsController;
use App\Http\Controllers\Admin\CareerTrackController;
use App\Http\Controllers\Admin\AiConfigController;
use App\Http\Controllers\Admin\AiPromptController;
use App\Http\Controllers\Admin\CreditPackController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DataExportController;
use App\Http\Controllers\Admin\EmailLogController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\ExternalRecruitmentController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\FileManagerController;
use App\Http\Controllers\Admin\HelpArticleController;
use App\Http\Controllers\Admin\HelpCategoryController;
use App\Http\Controllers\Admin\InterviewController;
use App\Http\Controllers\Admin\JobApplicationController;
use App\Http\Controllers\Admin\MailConfigController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OAuthDiagnosticsController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\RecommendationInsightsController;
use App\Http\Controllers\Admin\ResumeController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SiteEventController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SubscriptionManageController;
use App\Http\Controllers\Admin\SystemOpsController;
use App\Http\Controllers\Admin\SystemSettingAuditController;
use App\Http\Controllers\Admin\TemplateSourceController;
use App\Http\Controllers\Admin\UsageLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VisitorAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', 'admin.log'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index')->middleware('permission:users.view');
        Route::get('/create', [UserController::class, 'create'])->name('create')->middleware('permission:users.create');
        Route::post('/', [UserController::class, 'store'])->name('store')->middleware('permission:users.create');
        Route::get('/{user}', [UserController::class, 'show'])->name('show')->middleware('permission:users.view');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('permission:users.edit');
        Route::match(['put', 'patch'], '/{user}', [UserController::class, 'update'])->name('update')->middleware('permission:users.edit');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('permission:users.delete');
        Route::get('/export', [UserController::class, 'export'])->name('export')->middleware('permission:users.view');
        Route::post('/batch-delete', [UserController::class, 'batchDestroy'])->name('batch-delete')->middleware('permission:users.batch_delete');
        Route::post('/batch-suspend', [UserController::class, 'batchSuspend'])->name('batch-suspend')->middleware('permission:users.edit');
        Route::get('/{user}/roles', [UserController::class, 'editRoles'])->name('roles.edit')->middleware('permission:users.edit');
        Route::put('/{user}/roles', [UserController::class, 'updateRoles'])->name('roles.update')->middleware('permission:users.edit');
        Route::post('/{user}/suspend', [UserController::class, 'suspend'])->name('suspend')->middleware('permission:users.edit');
        Route::post('/{user}/unsuspend', [UserController::class, 'unsuspend'])->name('unsuspend')->middleware('permission:users.edit');
        Route::get('/{user}/grant-credits', [CreditPackController::class, 'grantForm'])->name('grant-credits')->middleware('permission:plans.manage');
        Route::post('/{user}/grant-credits', [CreditPackController::class, 'grant'])->name('grant-credits.store')->middleware('permission:plans.manage');
        Route::get('/{user}/subscriptions', [SubscriptionManageController::class, 'userSubscriptions'])->name('subscriptions')->middleware('permission:plans.manage');
        Route::get('/{user}/activate-plan', [SubscriptionManageController::class, 'activateForm'])->name('activate-plan')->middleware('permission:plans.manage');
        Route::post('/{user}/activate-plan', [SubscriptionManageController::class, 'activate'])->name('activate-plan.store')->middleware('permission:plans.manage');
        Route::post('/{user}/update-membership', [UserController::class, 'updateMembership'])->name('update-membership')->middleware('permission:plans.manage');
        Route::post('/{user}/cancel-membership', [UserController::class, 'cancelMembership'])->name('cancel-membership')->middleware('permission:plans.manage');
    });

    Route::prefix('resumes')->name('resumes.')->group(function () {
        Route::get('/', [ResumeController::class, 'index'])->name('index')->middleware('permission:resumes.view');
        Route::get('/export', [ResumeController::class, 'export'])->name('export')->middleware('permission:resumes.view');
        Route::get('/{resume}', [ResumeController::class, 'show'])->name('show')->middleware('permission:resumes.view');
        Route::delete('/{resume}', [ResumeController::class, 'destroy'])->name('destroy')->middleware('permission:resumes.delete');
        Route::post('/{resume}/optimize', [ResumeController::class, 'optimize'])->name('optimize')->middleware('permission:resumes.view');
        Route::post('/{resume}/ats-score', [ResumeController::class, 'atsScore'])->name('ats-score')->middleware('permission:resumes.view');
    });

    Route::prefix('interviews')->name('interviews.')->group(function () {
        Route::get('/', [InterviewController::class, 'index'])->name('index')->middleware('permission:interviews.view');
        Route::get('/export', [InterviewController::class, 'export'])->name('export')->middleware('permission:interviews.view');
        Route::get('/{interview}', [InterviewController::class, 'show'])->name('show')->middleware('permission:interviews.view');
        Route::delete('/{interview}', [InterviewController::class, 'destroy'])->name('destroy')->middleware('permission:interviews.delete');
        Route::post('/{interview}/retry-pending-evaluation', [InterviewController::class, 'retryPendingEvaluation'])->name('retry-pending-evaluation')->middleware('permission:interviews.view');
        Route::post('/{interview}/questions/{question}/retry-evaluation', [InterviewController::class, 'retryQuestionEvaluation'])->name('retry-question-evaluation')->middleware('permission:interviews.view');
    });

    Route::prefix('job-applications')->name('job-applications.')->group(function () {
        Route::get('/', [JobApplicationController::class, 'index'])->name('index')->middleware('permission:job-applications.view');
        Route::get('/{job_application}', [JobApplicationController::class, 'show'])->name('show')->middleware('permission:job-applications.view');
        Route::get('/{job_application}/edit', [JobApplicationController::class, 'edit'])->name('edit')->middleware('permission:job-applications.edit');
        Route::match(['put', 'patch'], '/{job_application}', [JobApplicationController::class, 'update'])->name('update')->middleware('permission:job-applications.edit');
        Route::delete('/{job_application}', [JobApplicationController::class, 'destroy'])->name('destroy')->middleware('permission:job-applications.edit');
    });

    Route::prefix('external-recruitments')->name('external-recruitments.')->group(function () {
        Route::get('/', [ExternalRecruitmentController::class, 'index'])->name('index')->middleware('permission:site-settings.view');
        Route::post('/sync', [ExternalRecruitmentController::class, 'sync'])->name('sync')->middleware('permission:site-settings.edit');
        Route::post('/auto-review', [ExternalRecruitmentController::class, 'autoReview'])->name('auto-review')->middleware('permission:site-settings.edit');
        Route::post('/approve-all', [ExternalRecruitmentController::class, 'approveAll'])->name('approve-all')->middleware('permission:site-settings.edit');
        Route::post('/batch-approve', [ExternalRecruitmentController::class, 'batchApprove'])->name('batch-approve')->middleware('permission:site-settings.edit');
        Route::post('/batch-reject', [ExternalRecruitmentController::class, 'batchReject'])->name('batch-reject')->middleware('permission:site-settings.edit');
        Route::post('/{externalRecruitment}/approve', [ExternalRecruitmentController::class, 'approve'])->name('approve')->middleware('permission:site-settings.edit');
        Route::post('/{externalRecruitment}/reject', [ExternalRecruitmentController::class, 'reject'])->name('reject')->middleware('permission:site-settings.edit');
    });

    // #29 推荐效果看板
    Route::get('/recommendation-insights', [RecommendationInsightsController::class, 'index'])
        ->name('recommendation-insights.index')
        ->middleware('permission:site-settings.view');

    Route::prefix('usage-logs')->name('usage-logs.')->group(function () {
        Route::get('/', [UsageLogController::class, 'index'])->name('index')->middleware('permission:usage-logs.view');
        Route::get('/{usage_log}', [UsageLogController::class, 'show'])->name('show')->middleware('permission:usage-logs.view');
    });

    Route::prefix('feedbacks')->name('feedbacks.')->group(function () {
        Route::get('/', [FeedbackController::class, 'index'])->name('index')->middleware('permission:feedbacks.view');
        Route::get('/{feedback}', [FeedbackController::class, 'show'])->name('show')->middleware('permission:feedbacks.view');
        Route::put('/{feedback}/status', [FeedbackController::class, 'updateStatus'])->name('update-status')->middleware(['permission:feedbacks.manage|feedbacks.edit', 'throttle:feedback-admin-write']);
        Route::post('/{feedback}/reply', [FeedbackController::class, 'reply'])->name('reply')->middleware(['permission:feedbacks.reply|feedbacks.edit', 'throttle:feedback-admin-write']);
        Route::put('/{feedback}/note', [FeedbackController::class, 'updateNote'])->name('update-note')->middleware(['permission:feedbacks.manage|feedbacks.edit', 'throttle:feedback-admin-write']);
        Route::put('/{feedback}/adoption', [FeedbackController::class, 'updateAdoption'])->name('update-adoption')->middleware(['permission:feedbacks.adopt|feedbacks.edit', 'throttle:feedback-admin-write']);
        Route::post('/{feedback}/reward', [FeedbackController::class, 'reward'])->name('reward')->middleware(['permission:feedbacks.reward|feedbacks.edit', 'throttle:feedback-admin-reward']);
        Route::delete('/{feedback}', [FeedbackController::class, 'destroy'])->name('destroy')->middleware(['permission:feedbacks.delete', 'throttle:feedback-admin-write']);
    });

    Route::prefix('ai-config')->name('ai-config.')->group(function () {
        Route::get('/', [AiConfigController::class, 'index'])->name('index')->middleware('permission:ai-config.view');
        Route::put('/', [AiConfigController::class, 'update'])->name('update')->middleware('permission:ai-config.edit');
        Route::post('/test', [AiConfigController::class, 'test'])->name('test')->middleware('permission:ai-config.edit');
        Route::get('/history', [AiConfigController::class, 'history'])->name('history')->middleware('permission:ai-config.view');
        Route::post('/benchmark', [AiConfigController::class, 'benchmark'])->name('benchmark')->middleware('permission:ai-config.view');
    });

    Route::prefix('ai-prompts')->name('ai-prompts.')->group(function () {
        Route::get('/', [AiPromptController::class, 'index'])->name('index')->middleware('permission:ai-prompts.view');
        Route::get('/create', [AiPromptController::class, 'create'])->name('create')->middleware('permission:ai-prompts.edit');
        Route::post('/', [AiPromptController::class, 'store'])->name('store')->middleware('permission:ai-prompts.edit');
        Route::get('/{aiPrompt}', [AiPromptController::class, 'show'])->name('show')->middleware('permission:ai-prompts.view');
        Route::get('/{aiPrompt}/edit', [AiPromptController::class, 'edit'])->name('edit')->middleware('permission:ai-prompts.edit');
        Route::match(['put', 'patch'], '/{aiPrompt}', [AiPromptController::class, 'update'])->name('update')->middleware('permission:ai-prompts.edit');
        Route::delete('/{aiPrompt}', [AiPromptController::class, 'destroy'])->name('destroy')->middleware('permission:ai-prompts.edit');
        Route::post('/{aiPrompt}/test', [AiPromptController::class, 'test'])->name('test')->middleware('permission:ai-prompts.edit');
        Route::post('/{aiPrompt}/duplicate', [AiPromptController::class, 'duplicate'])->name('duplicate')->middleware('permission:ai-prompts.edit');
    });

    Route::get('/ai-analytics', [AiAnalyticsController::class, 'index'])->name('ai-analytics.index')->middleware('permission:ai-analytics.view');

    Route::prefix('site-settings')->name('site-settings.')->group(function () {
        Route::get('/', [SiteSettingController::class, 'index'])->name('index')->middleware('permission:site-settings.view');
        Route::put('/', [SiteSettingController::class, 'update'])->name('update')->middleware('permission:site-settings.edit');
        Route::post('/clear-cache', [SiteSettingController::class, 'clearCache'])->name('clear-cache')->middleware('permission:site-settings.edit');
        Route::post('/toggle-maintenance', [SiteSettingController::class, 'toggleMaintenance'])->name('toggle-maintenance')->middleware('permission:site-settings.edit');
        Route::post('/sanitize-head-snippets', [SiteSettingController::class, 'sanitizeHeadSnippets'])->name('sanitize-head-snippets')->middleware('permission:site-settings.edit');
        Route::get('/export', [SiteSettingController::class, 'export'])->name('export')->middleware('permission:site-settings.view');
    });

    Route::get('/system-setting-audits', [SystemSettingAuditController::class, 'index'])->name('system-setting-audits.index')->middleware('permission:system-setting-audits.view');
    Route::get('/action-logs', [ActionLogController::class, 'index'])->name('action-logs.index')->middleware('permission:action-logs.view');
    Route::get('/action-logs/export', [ActionLogController::class, 'exportCsv'])->name('action-logs.export')->middleware('permission:action-logs.view');

    Route::prefix('visitor-analytics')->name('visitor-analytics.')->group(function () {
        Route::get('/', [VisitorAnalyticsController::class, 'index'])->name('index')->middleware('permission:usage-logs.view');
        Route::get('/detail', [VisitorAnalyticsController::class, 'detail'])->name('detail')->middleware('permission:usage-logs.view');
        Route::get('/export', [VisitorAnalyticsController::class, 'export'])->name('export')->middleware('permission:usage-logs.view');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index')->middleware('permission:notifications.view');
        Route::get('/create', [NotificationController::class, 'create'])->name('create')->middleware('permission:notifications.edit');
        Route::post('/', [NotificationController::class, 'store'])->name('store')->middleware('permission:notifications.edit');
        Route::get('/{notification}', [NotificationController::class, 'show'])->name('show')->middleware('permission:notifications.view');
        Route::get('/{notification}/edit', [NotificationController::class, 'edit'])->name('edit')->middleware('permission:notifications.edit');
        Route::match(['put', 'patch'], '/{notification}', [NotificationController::class, 'update'])->name('update')->middleware('permission:notifications.edit');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy')->middleware('permission:notifications.edit');
        Route::post('/{notification}/send', [NotificationController::class, 'send'])->name('send')->middleware('permission:notifications.edit');
    });

    Route::prefix('email-templates')->group(function () {
        Route::get('/', [EmailTemplateController::class, 'index'])->name('notifications.email-templates')->middleware('permission:notifications.view');
        Route::get('/create', [EmailTemplateController::class, 'create'])->name('notifications.email-templates.create')->middleware('permission:notifications.edit');
        Route::post('/', [EmailTemplateController::class, 'store'])->name('notifications.email-templates.store')->middleware('permission:notifications.edit');
        Route::get('/{template}/edit', [EmailTemplateController::class, 'edit'])->name('notifications.email-templates.edit')->middleware('permission:notifications.edit');
        Route::put('/{template}', [EmailTemplateController::class, 'update'])->name('notifications.email-templates.update')->middleware('permission:notifications.edit');
        Route::delete('/{template}', [EmailTemplateController::class, 'destroy'])->name('notifications.email-templates.destroy')->middleware('permission:notifications.edit');
        Route::post('/{template}/preview', [EmailTemplateController::class, 'preview'])->name('notifications.email-templates.preview')->middleware('permission:notifications.view');
    });

    Route::prefix('mail-config')->name('mail-config.')->group(function () {
        Route::get('/', [MailConfigController::class, 'index'])->name('index')->middleware('permission:site-settings.view');
        Route::put('/', [MailConfigController::class, 'update'])->name('update')->middleware('permission:site-settings.edit');
        Route::post('/test', [MailConfigController::class, 'test'])->name('test')->middleware('permission:site-settings.edit');
        Route::post('/logs/{emailLog}/retry', [MailConfigController::class, 'retry'])->name('retry')->middleware('permission:site-settings.edit');
        Route::get('/stats', [MailConfigController::class, 'stats'])->name('stats')->middleware('permission:site-settings.view');
    });

    Route::prefix('email-logs')->name('email-logs.')->group(function () {
        Route::get('/', [EmailLogController::class, 'index'])->name('index')->middleware('permission:notifications.view');
        Route::get('/{emailLog}', [EmailLogController::class, 'show'])->name('show')->middleware('permission:notifications.view');
        Route::post('/{emailLog}/resend', [EmailLogController::class, 'resend'])->name('resend')->middleware('permission:notifications.edit');
        Route::post('/resend-failed', [EmailLogController::class, 'resendFailed'])->name('resend-failed')->middleware('permission:notifications.edit');
        Route::post('/clear', [EmailLogController::class, 'clear'])->name('clear')->middleware('permission:notifications.edit');
        Route::delete('/{emailLog}', [EmailLogController::class, 'destroy'])->name('destroy')->middleware('permission:notifications.edit');
        Route::get('/statistics', [EmailLogController::class, 'statistics'])->name('statistics')->middleware('permission:notifications.view');
    });

    Route::prefix('data-export')->name('data-export.')->group(function () {
        Route::get('/', [DataExportController::class, 'index'])->name('index')->middleware('permission:site-settings.view');
        Route::get('/{type}/export', [DataExportController::class, 'export'])->name('export')->middleware('permission:site-settings.view', 'throttle:10,1');
        Route::get('/{type}/preview', [DataExportController::class, 'preview'])->name('preview')->middleware('permission:site-settings.view');
    });

    Route::prefix('file-manager')->name('file-manager.')->group(function () {
        Route::get('/', [FileManagerController::class, 'index'])->name('index')->middleware('permission:file-manager.view');
        Route::post('/upload', [FileManagerController::class, 'upload'])->name('upload')->middleware('permission:file-manager.edit');
        Route::post('/create-folder', [FileManagerController::class, 'createFolder'])->name('create-folder')->middleware('permission:file-manager.edit');
        Route::delete('/destroy', [FileManagerController::class, 'destroy'])->name('destroy')->middleware('permission:file-manager.delete');
        Route::get('/download/{path}', [FileManagerController::class, 'download'])->name('download')->middleware('permission:file-manager.view')->where('path', '.*');
        Route::post('/rename', [FileManagerController::class, 'rename'])->name('rename')->middleware('permission:file-manager.edit');
    });

    Route::prefix('roles-permissions')->name('roles-permissions.')->group(function () {
        Route::get('/', [RolePermissionController::class, 'index'])->name('index')->middleware('permission:roles.view');
        Route::get('/create', [RolePermissionController::class, 'create'])->name('create')->middleware('permission:roles.create');
        Route::post('/', [RolePermissionController::class, 'store'])->name('store')->middleware('permission:roles.create');
        Route::get('/{role}/edit', [RolePermissionController::class, 'edit'])->name('edit')->middleware('permission:roles.edit');
        Route::put('/{role}', [RolePermissionController::class, 'update'])->name('update')->middleware('permission:roles.edit');
        Route::delete('/{role}', [RolePermissionController::class, 'destroy'])->name('destroy')->middleware('permission:roles.delete');
    });
    Route::get('/permissions', [RolePermissionController::class, 'permissions'])->name('roles-permissions.permissions')->middleware('permission:permissions.view');
    Route::post('/permissions/sync', [RolePermissionController::class, 'syncPermissions'])->name('roles-permissions.sync-permissions')->middleware('permission:permissions.edit');

    Route::prefix('schedule')->name('schedule.')->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])->name('index')->middleware('permission:schedule.view');
        Route::get('/{task}', [ScheduleController::class, 'show'])->name('show')->middleware('permission:schedule.view');
        Route::post('/{task}/run', [ScheduleController::class, 'run'])->name('run')->middleware('permission:schedule.run');
    });
    Route::get('/schedule-logs', [ScheduleController::class, 'logs'])->name('schedule.logs')->middleware('permission:schedule.view');
    Route::post('/schedule-logs/clear', [ScheduleController::class, 'clearLogs'])->name('schedule.clear-logs')->middleware('permission:schedule.run');
    Route::get('/schedule-status', [ScheduleController::class, 'status'])->name('schedule.status')->middleware('permission:schedule.view');

    Route::prefix('system-ops')->name('system-ops.')->group(function () {
        Route::get('/', [SystemOpsController::class, 'index'])->name('index')->middleware('permission:system-ops.view');
        Route::post('/clear-cache', [SystemOpsController::class, 'clearCache'])->name('clear-cache')->middleware('permission:system-ops.edit');
        Route::post('/optimize', [SystemOpsController::class, 'optimize'])->name('optimize')->middleware('permission:system-ops.edit');
        Route::post('/queue-action', [SystemOpsController::class, 'queueAction'])->name('queue-action')->middleware('permission:system-ops.edit');
        Route::post('/failed-jobs/{id}/retry', [SystemOpsController::class, 'retryFailedJob'])->name('failed-jobs.retry')->middleware('permission:system-ops.edit');
        Route::post('/failed-jobs/{id}/forget', [SystemOpsController::class, 'forgetFailedJob'])->name('failed-jobs.forget')->middleware('permission:system-ops.edit');
        Route::post('/migrate', [SystemOpsController::class, 'migrate'])->name('migrate')->middleware('permission:system-ops.edit');
        Route::get('/logs', [SystemOpsController::class, 'logs'])->name('logs')->middleware('permission:system-ops.view');
        Route::get('/oauth-diagnostics', [OAuthDiagnosticsController::class, 'index'])->name('oauth-diagnostics')->middleware('permission:system-ops.view');
        Route::post('/oauth-diagnostics/auto-mitigate', [OAuthDiagnosticsController::class, 'autoMitigate'])->name('oauth-diagnostics.auto-mitigate')->middleware('permission:system-ops.edit');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/password', [UserController::class, 'passwordForm'])->name('password');
        Route::put('/password', [UserController::class, 'updatePassword'])->name('password.update');
    });

    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index')->middleware('permission:plans.manage');
        Route::get('/create', [PlanController::class, 'create'])->name('create')->middleware('permission:plans.manage');
        Route::post('/', [PlanController::class, 'store'])->name('store')->middleware('permission:plans.manage');
        Route::get('/{plan}/edit', [PlanController::class, 'edit'])->name('edit')->middleware('permission:plans.manage');
        Route::match(['put', 'patch'], '/{plan}', [PlanController::class, 'update'])->name('update')->middleware('permission:plans.manage');
        Route::delete('/{plan}', [PlanController::class, 'destroy'])->name('destroy')->middleware('permission:plans.manage');
    });

    Route::prefix('credit-packs')->name('credit-packs.')->group(function () {
        Route::get('/', [CreditPackController::class, 'index'])->name('index')->middleware('permission:plans.manage');
        Route::get('/create', [CreditPackController::class, 'create'])->name('create')->middleware('permission:plans.manage');
        Route::post('/', [CreditPackController::class, 'store'])->name('store')->middleware('permission:plans.manage');
        Route::get('/{credit_pack}/edit', [CreditPackController::class, 'edit'])->name('edit')->middleware('permission:plans.manage');
        Route::match(['put', 'patch'], '/{credit_pack}', [CreditPackController::class, 'update'])->name('update')->middleware('permission:plans.manage');
        Route::delete('/{credit_pack}', [CreditPackController::class, 'destroy'])->name('destroy')->middleware('permission:plans.manage');
        Route::get('/orders', [CreditPackController::class, 'orders'])->name('orders')->middleware('permission:plans.manage');
    });

    Route::get('subscription-orders', [SubscriptionManageController::class, 'orders'])->name('subscriptions.orders')->middleware('permission:plans.manage');
    Route::get('subscription-orders/export', [SubscriptionManageController::class, 'exportOrders'])->name('subscriptions.orders.export')->middleware('permission:plans.manage');

    // 校招赛道管理
    Route::prefix('career-tracks')->name('career-tracks.')->group(function () {
        Route::get('/', [CareerTrackController::class, 'index'])->name('index')->middleware('permission:site-settings.view');
        Route::get('/create', [CareerTrackController::class, 'create'])->name('create')->middleware('permission:site-settings.edit');
        Route::post('/', [CareerTrackController::class, 'store'])->name('store')->middleware('permission:site-settings.edit');
        Route::get('/{careerTrack}/edit', [CareerTrackController::class, 'edit'])->name('edit')->middleware('permission:site-settings.edit');
        Route::match(['put', 'patch'], '/{careerTrack}', [CareerTrackController::class, 'update'])->name('update')->middleware('permission:site-settings.edit');
        Route::delete('/{careerTrack}', [CareerTrackController::class, 'destroy'])->name('destroy')->middleware('permission:site-settings.edit');
        Route::post('/{careerTrack}/toggle-active', [CareerTrackController::class, 'toggleActive'])->name('toggle-active')->middleware('permission:site-settings.edit');
    });

    Route::prefix('template-sources')->name('template-sources.')->group(function () {
        Route::get('/', [TemplateSourceController::class, 'index'])->name('index')->middleware('permission:site-settings.view');
        Route::get('/create', [TemplateSourceController::class, 'create'])->name('create')->middleware('permission:site-settings.edit');
        Route::post('/', [TemplateSourceController::class, 'store'])->name('store')->middleware('permission:site-settings.edit');
        Route::get('/{templateSource}/edit', [TemplateSourceController::class, 'edit'])->name('edit')->middleware('permission:site-settings.edit');
        Route::match(['put', 'patch'], '/{templateSource}', [TemplateSourceController::class, 'update'])->name('update')->middleware('permission:site-settings.edit');
        Route::delete('/{templateSource}', [TemplateSourceController::class, 'destroy'])->name('destroy')->middleware('permission:site-settings.edit');
        Route::post('/{templateSource}/sync', [TemplateSourceController::class, 'sync'])->name('sync')->middleware('permission:site-settings.edit');
        Route::post('/sync-all', [TemplateSourceController::class, 'syncAll'])->name('sync-all')->middleware('permission:site-settings.edit');
    });

    Route::prefix('help-categories')->name('help-categories.')->group(function () {
        Route::get('/', [HelpCategoryController::class, 'index'])->name('index')->middleware('permission:help-center.view');
        Route::get('/create', [HelpCategoryController::class, 'create'])->name('create')->middleware('permission:help-center.manage');
        Route::post('/', [HelpCategoryController::class, 'store'])->name('store')->middleware('permission:help-center.manage');
        Route::get('/{helpCategory}/edit', [HelpCategoryController::class, 'edit'])->name('edit')->middleware('permission:help-center.manage');
        Route::put('/{helpCategory}', [HelpCategoryController::class, 'update'])->name('update')->middleware('permission:help-center.manage');
        Route::delete('/{helpCategory}', [HelpCategoryController::class, 'destroy'])->name('destroy')->middleware('permission:help-center.manage');
    });

    Route::prefix('help-articles')->name('help-articles.')->group(function () {
        Route::get('/', [HelpArticleController::class, 'index'])->name('index')->middleware('permission:help-center.view');
        Route::get('/create', [HelpArticleController::class, 'create'])->name('create')->middleware('permission:help-center.manage');
        Route::post('/', [HelpArticleController::class, 'store'])->name('store')->middleware('permission:help-center.manage');
        Route::get('/{helpArticle}/edit', [HelpArticleController::class, 'edit'])->name('edit')->middleware('permission:help-center.manage');
        Route::put('/{helpArticle}', [HelpArticleController::class, 'update'])->name('update')->middleware('permission:help-center.manage');
        Route::delete('/{helpArticle}', [HelpArticleController::class, 'destroy'])->name('destroy')->middleware('permission:help-center.manage');
        Route::post('/{helpArticle}/toggle-publish', [HelpArticleController::class, 'togglePublish'])->name('toggle-publish')->middleware('permission:help-center.manage');
        Route::post('/upload-image', [HelpArticleController::class, 'uploadImage'])->name('upload-image')->middleware('permission:help-center.manage');
    });

    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [SiteEventController::class, 'index'])->name('index')->middleware('permission:notifications.view');
        Route::get('/create', [SiteEventController::class, 'create'])->name('create')->middleware('permission:notifications.manage');
        Route::post('/', [SiteEventController::class, 'store'])->name('store')->middleware('permission:notifications.manage');
        Route::get('/{event}/edit', [SiteEventController::class, 'edit'])->name('edit')->middleware('permission:notifications.manage');
        Route::put('/{event}', [SiteEventController::class, 'update'])->name('update')->middleware('permission:notifications.manage');
        Route::delete('/{event}', [SiteEventController::class, 'destroy'])->name('destroy')->middleware('permission:notifications.manage');
        Route::post('/{event}/toggle-publish', [SiteEventController::class, 'togglePublish'])->name('toggle-publish')->middleware('permission:notifications.manage');
    });

    Route::prefix('access-bans')->name('access-bans.')->group(function () {
        Route::get('/', [AccessBanController::class, 'index'])->name('index')->middleware('permission:access-bans.view');
        Route::post('/', [AccessBanController::class, 'store'])->name('store')->middleware('permission:access-bans.edit');
        Route::post('/batch-destroy', [AccessBanController::class, 'batchDestroy'])->name('batch-destroy')->middleware('permission:access-bans.edit');
        Route::post('/clear-expired', [AccessBanController::class, 'clearExpired'])->name('clear-expired')->middleware('permission:access-bans.edit');
        Route::delete('/{accessBan}', [AccessBanController::class, 'destroy'])->name('destroy')->middleware('permission:access-bans.edit');
        Route::get('/logs', [AccessBanController::class, 'accessLogs'])->name('logs')->middleware('permission:access-bans.view');
    });
});
