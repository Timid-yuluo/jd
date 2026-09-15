<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\EmailVerificationController;
use App\Http\Controllers\User\InterviewController;
use App\Http\Controllers\User\JobMatchingController;
use App\Http\Controllers\User\JobRecommendationController;
use App\Http\Controllers\User\KanbanController;
use App\Http\Controllers\User\MembershipController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\NotificationPreferenceController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\QuestionFavoriteController;
use App\Http\Controllers\User\ResumeAnalysisController;
use App\Http\Controllers\User\ResumeController;
use App\Http\Controllers\User\ResumeEditorController;
use App\Http\Controllers\User\ResumeExportController;
use App\Http\Controllers\User\ResumeImportController;
use App\Http\Controllers\User\ResumeKeywordController;
use App\Http\Controllers\User\ResumeManageController;
use App\Http\Controllers\User\AssessmentController;
use App\Http\Controllers\User\LearningPathController;
use App\Http\Controllers\User\ResumeOptimizeController;
use App\Http\Controllers\User\ResumeOptimizeInsightsController;
use App\Http\Controllers\User\ResumeOptimizeSessionController;
use App\Http\Controllers\User\ResumeStreamController;
use App\Http\Controllers\User\ResumeTemplateController;
use App\Http\Controllers\User\SalaryController;
use App\Http\Controllers\User\SkillAssessmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'maintenance', 'user.log', 'email.verification.required', 'mobile'])->group(function () {
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/dashboard/dismiss-onboarding', [DashboardController::class, 'dismissOnboarding'])->name('dashboard.dismiss-onboarding');

        Route::get('/resume-templates', [ResumeTemplateController::class, 'index'])->name('resume-templates.index');
        Route::get('/resume-templates/analytics', [ResumeTemplateController::class, 'analytics'])->name('resume-templates.analytics');
        Route::get('/resume-templates/{resumeTemplate}', [ResumeTemplateController::class, 'show'])->name('resume-templates.show');
        Route::post('/resume-templates/{resumeTemplate}/apply', [ResumeTemplateController::class, 'apply'])->name('resume-templates.apply');
        Route::post('/resumes/{resume}/template-undo', [ResumeTemplateController::class, 'undoApply'])->name('resumes.template-undo');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])
            ->middleware(['throttle:profile-write', 'password.confirm'])
            ->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
            ->middleware(['throttle:profile-write', 'password.confirm'])
            ->name('profile.password.update');
        Route::post('/profile/logout-all', [ProfileController::class, 'logoutAllDevices'])
            ->middleware('password.confirm')
            ->name('profile.logout-all');
        Route::get('/bindings/github/redirect', [OAuthController::class, 'redirectForBinding'])->defaults('provider', 'github')->name('bindings.github.redirect');
        Route::get('/bindings/github/callback', [OAuthController::class, 'callbackForBinding'])->defaults('provider', 'github')->name('bindings.github.callback');
        Route::get('/bindings/alipay/redirect', [OAuthController::class, 'redirectForBinding'])->defaults('provider', 'alipay')->name('bindings.alipay.redirect');
        Route::get('/bindings/alipay/callback', [OAuthController::class, 'callbackForBinding'])->defaults('provider', 'alipay')->name('bindings.alipay.callback');
        Route::delete('/bindings/{provider}', [OAuthController::class, 'disconnect'])
            ->whereIn('provider', ['github', 'alipay'])
            ->name('bindings.disconnect');
        Route::post('/profile/export-data', [ProfileController::class, 'exportData'])
            ->name('profile.export-data');
        Route::get('/profile/activity-log', [ProfileController::class, 'activityLog'])
            ->name('profile.activity-log');
        Route::delete('/profile/account', [ProfileController::class, 'destroyAccount'])
            ->middleware(['password.confirm', 'throttle:account-delete'])
            ->name('profile.destroy-account');

        Route::prefix('resumes')->name('resumes.')->group(function (): void {
            Route::post('optimize-section', [ResumeOptimizeController::class, 'optimizeSection'])
                ->middleware(['throttle:resume-ai-section', 'quota'])
                ->name('optimize-section');
            Route::post('generate-section', [ResumeOptimizeController::class, 'generateSection'])
                ->middleware(['throttle:resume-ai-section', 'quota'])
                ->name('generate-section');
            Route::post('import-document-draft', [ResumeImportController::class, 'importDocumentDraft'])
                ->middleware(['throttle:resume-ai-heavy', 'quota'])
                ->name('import-document-draft');
            Route::post('keywords/extract', [ResumeKeywordController::class, 'extract'])
                ->middleware(['throttle:resume-ai-heavy', 'quota'])
                ->name('keywords.extract');
            Route::post('keywords/parse-jd', [ResumeKeywordController::class, 'parseJd'])
                ->middleware(['throttle:resume-ai-heavy', 'quota'])
                ->name('keywords.parse-jd');

            Route::prefix('export-tasks')->name('export-tasks.')->group(function (): void {
                Route::post('/', [ResumeExportController::class, 'createExportTask'])
                    ->middleware('throttle:export-task-create')
                    ->name('create');
                Route::get('{taskId}/status', [ResumeExportController::class, 'exportTaskStatus'])->name('status');
                Route::get('{taskId}/download', [ResumeExportController::class, 'downloadExportTask'])->name('download');
            });

            Route::get('trash', [ResumeManageController::class, 'trash'])->name('trash');
            Route::get('/', [ResumeController::class, 'index'])->name('index');
            Route::get('create', [ResumeManageController::class, 'create'])->name('create');
            Route::post('/', [ResumeManageController::class, 'store'])->name('store');
            Route::post('{id}/restore', [ResumeManageController::class, 'restore'])->name('restore');
            Route::delete('{id}/force', [ResumeManageController::class, 'forceDelete'])->name('force-delete');

            Route::prefix('{resume}')->group(function (): void {
                Route::get('/', [ResumeController::class, 'show'])->name('show');
                Route::get('edit', [ResumeManageController::class, 'edit'])->name('edit');
                Route::post('duplicate', [ResumeManageController::class, 'duplicate'])->name('duplicate');
                Route::post('toggle-share', [ResumeManageController::class, 'toggleShare'])->name('toggle-share');
                Route::put('share-password', [ResumeManageController::class, 'updateSharePassword'])->name('update-share-password');
                Route::put('career-track', [ResumeManageController::class, 'updateCareerTrack'])->name('update-career-track');
                Route::get('versions', [ResumeManageController::class, 'versions'])->name('versions');
                Route::post('versions/snapshot', [ResumeManageController::class, 'createSnapshot'])->name('versions.snapshot');
                Route::get('versions/compare', [ResumeManageController::class, 'compareVersions'])->name('versions.compare');
                Route::post('versions/{version}/restore', [ResumeManageController::class, 'restoreVersion'])->name('versions.restore');
                Route::patch('versions/{version}/label', [ResumeManageController::class, 'updateVersionLabel'])->name('versions.label');
                Route::match(['put', 'patch'], '/', [ResumeManageController::class, 'update'])->name('update');
                Route::delete('/', [ResumeManageController::class, 'destroy'])->name('destroy');
                Route::get('print-pdf', [ResumeExportController::class, 'printPdf'])->name('print-pdf');
                Route::post('pdf-download', [ResumeExportController::class, 'registerPdfDownload'])->middleware(\App\Http\Middleware\ThrottlePdfDownload::class)->name('pdf-download');
                Route::get('export-docx', [ResumeExportController::class, 'exportDocx'])->middleware(\App\Http\Middleware\ThrottlePdfDownload::class)->name('export-docx');
                Route::get('editor', [ResumeEditorController::class, 'editor'])->name('editor');
                Route::post('editor', [ResumeEditorController::class, 'saveModules'])
                    ->middleware('throttle:resume-write')
                    ->name('save-modules');
                Route::post('upload-avatar', [ResumeEditorController::class, 'uploadAvatar'])
                    ->middleware('throttle:resume-write')
                    ->name('upload-avatar');
                Route::get('optimize', [ResumeOptimizeController::class, 'optimizeView'])->name('optimize-view');
                Route::post('optimize', [ResumeOptimizeController::class, 'optimize'])
                    ->middleware(['throttle:resume-ai-heavy', 'quota'])
                    ->name('optimize');
                Route::post('optimize-stream', [ResumeStreamController::class, 'optimizeStream'])
                    ->middleware(['throttle:resume-ai-heavy', 'quota'])
                    ->name('optimize-stream');
                Route::post('optimize-stream-prewarm', [ResumeStreamController::class, 'prewarm'])
                    ->middleware('throttle:api')
                    ->name('optimize-stream-prewarm');
                Route::post('apply-optimized', [ResumeOptimizeController::class, 'applyOptimized'])
                    ->middleware('throttle:resume-write')
                    ->name('apply-optimized');
                Route::get('ats-report', [ResumeAnalysisController::class, 'atsReport'])->name('ats-report');
                Route::get('ats-score', [ResumeAnalysisController::class, 'atsScoreRedirect'])->name('ats-score-redirect');
                Route::post('ats-score', [ResumeAnalysisController::class, 'atsScore'])
                    ->middleware(['throttle:resume-ai-heavy'])
                    ->name('ats-score');
                Route::post('import-document', [ResumeImportController::class, 'importDocument'])
                    ->middleware('throttle:resume-write')
                    ->name('import-document');

                Route::prefix('optimize-sessions')->name('optimize-sessions.')->group(function (): void {
                    Route::post('/', [ResumeOptimizeSessionController::class, 'create'])
                        ->middleware(['throttle:resume-ai-heavy', 'quota'])
                        ->name('create');
                    Route::get('history', [ResumeOptimizeSessionController::class, 'history'])->name('history');
                    Route::get('{session}', [ResumeOptimizeSessionController::class, 'status'])->name('status');
                    Route::post('{session}/retry', [ResumeOptimizeSessionController::class, 'retry'])->middleware('quota')->name('retry');
                });

                Route::prefix('optimize-insights')->name('optimize-insights.')->group(function (): void {
                    Route::get('strategy-gain', [ResumeOptimizeInsightsController::class, 'strategyGain'])->name('strategy-gain');
                });

                Route::prefix('optimize-compare')->name('optimize-compare.')->group(function (): void {
                    Route::get('{session}', [ResumeOptimizeSessionController::class, 'comparePage'])->name('show');
                    Route::post('{session}/apply', [ResumeOptimizeSessionController::class, 'apply'])->name('apply');
                });

                // 模块排序建议
                Route::post('suggest-module-order', [ResumeOptimizeController::class, 'suggestModuleOrder'])
                    ->middleware(['throttle:resume-ai-section', 'quota'])
                    ->name('suggest-module-order');

                // 一键翻译
                Route::post('translate', [ResumeOptimizeController::class, 'translate'])
                    ->middleware(['throttle:resume-ai-heavy', 'quota'])
                    ->name('translate');
            });
        });

        Route::get('interviews/keywords/{type}', [InterviewController::class, 'keywords'])->name('interviews.keywords');
        Route::get('interviews/jd-sources', [InterviewController::class, 'jdSources'])->name('interviews.jd-sources');
        Route::post('interviews/jd-content', [InterviewController::class, 'jdContent'])->name('interviews.jd-content');

        Route::resource('interviews', InterviewController::class)->except(['edit', 'update'])->middleware('quota');
        Route::post('interviews/resume-highlights', [InterviewController::class, 'resumeHighlights'])->name('interviews.resume-highlights');
        Route::get('interviews/{interview}/session', [InterviewController::class, 'session'])->name('interviews.session');
        Route::post('interviews/{interview}/submit-answer', [InterviewController::class, 'submitAnswer'])
            ->middleware('throttle:interview-ai')
            ->name('interviews.submitAnswer');
        Route::get('interviews/{interview}/evaluation-status', [InterviewController::class, 'evaluationStatus'])->name('interviews.evaluationStatus');
        Route::post('interviews/{interview}/heartbeat', [InterviewController::class, 'heartbeat'])
            ->middleware('throttle:60,1')
            ->name('interviews.heartbeat');
        Route::post('interviews/{interview}/pause', [InterviewController::class, 'pause'])
            ->middleware('throttle:30,1')
            ->name('interviews.pause');
        Route::post('interviews/{interview}/resume', [InterviewController::class, 'resume'])
            ->middleware('throttle:30,1')
            ->name('interviews.resume');
        Route::get('interviews/compare', [InterviewController::class, 'compare'])->name('interviews.compare');
        Route::post('interviews/{interview}/finish', [InterviewController::class, 'finish'])
            ->middleware('throttle:30,1')
            ->name('interviews.finish');
        Route::get('interviews/{interview}/report', [InterviewController::class, 'report'])->name('interviews.report');
        Route::get('interviews/{interview}/report-pdf', [InterviewController::class, 'reportPdf'])->name('interviews.report-pdf');
        Route::post('interviews/{interview}/qr-code', [InterviewController::class, 'generateQrCode'])->middleware('throttle:10,1')->name('interviews.qr-code');
        Route::get('interviews/voice/mobile', [InterviewController::class, 'voiceMobile'])->name('interviews.voice-mobile');

        // 面试问题收藏（错题本）
        Route::get('question-favorites', [QuestionFavoriteController::class, 'index'])->name('question-favorites.index');
        Route::post('questions/{question}/favorite', [QuestionFavoriteController::class, 'toggle'])->name('questions.favorite');
        Route::put('question-favorites/{favorite}/note', [QuestionFavoriteController::class, 'updateNote'])->name('question-favorites.update-note');
        Route::delete('question-favorites/{favorite}', [QuestionFavoriteController::class, 'destroy'])->name('question-favorites.destroy');

        Route::get('kanban', [KanbanController::class, 'index'])->name('kanban.index');

        Route::middleware('request.id')->group(function (): void {
            Route::get('/jobs/analyze', [JobMatchingController::class, 'index'])->name('jobs.analyze');
            Route::post('/jobs/analyze', [JobMatchingController::class, 'analyze'])
                ->middleware(['throttle:job-match-analyze', 'quota'])
                ->name('jobs.analyze.submit');
            Route::get('/jobs/analyze/history', [JobMatchingController::class, 'history'])
                ->name('jobs.analyze.history.index');
            Route::get('/jobs/analyze/history/{analysis}', [JobMatchingController::class, 'showHistory'])
                ->name('jobs.analyze.history');
            Route::get('/jobs/analyze/history/{analysis}/pdf', [JobMatchingController::class, 'exportPdf'])
                ->name('jobs.analyze.history.pdf');
            Route::delete('/jobs/analyze/history/{analysis}', [JobMatchingController::class, 'destroyHistory'])
                ->name('jobs.analyze.history.destroy');
            Route::post('/jobs/analyze/history/batch-destroy', [JobMatchingController::class, 'batchDestroyHistory'])
                ->name('jobs.analyze.history.batch-destroy');

            Route::get('/jobs/batch', [JobMatchingController::class, 'batch'])->name('jobs.batch');
            Route::get('/jobs/batch/{batch}/progress', [JobMatchingController::class, 'batchProgress'])->middleware('throttle:30,1')->name('jobs.batch.progress');
            Route::post('/jobs/batch', [JobMatchingController::class, 'batchSubmit'])
                ->middleware(['throttle:job-match-analyze', 'quota'])
                ->name('jobs.batch.submit');

            Route::get('/jobs/bookmarks', [JobMatchingController::class, 'bookmarks'])->name('jobs.bookmarks');
            Route::post('/jobs/bookmarks', [JobMatchingController::class, 'storeBookmark'])->name('jobs.bookmarks.store');
            Route::delete('/jobs/bookmarks/{bookmark}', [JobMatchingController::class, 'destroyBookmark'])->name('jobs.bookmarks.destroy');
        });

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::get('/api/notifications/unread-count', [NotificationController::class, 'unreadCount'])->middleware('throttle:60,1')->name('notifications.unread-count');
        Route::get('/api/notifications/recent', [NotificationController::class, 'recent'])->middleware('throttle:30,1')->name('notifications.recent');

        Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index'])->name('notification-preferences.index');
        Route::post('/notification-preferences', [NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');
        Route::get('/api/notification-preferences', [NotificationPreferenceController::class, 'show'])->name('notification-preferences.show');
        Route::post('/notification-preferences/reset', [NotificationPreferenceController::class, 'reset'])->name('notification-preferences.reset');
        Route::post('/notification-preferences/test', [NotificationPreferenceController::class, 'test'])->name('notification-preferences.test');

        Route::get('/pricing', [MembershipController::class, 'pricing'])->name('membership.pricing');
        Route::get('/subscription', [MembershipController::class, 'mySubscription'])->name('membership.subscription');
        Route::get('/credits', [MembershipController::class, 'myCredits'])->name('membership.credits');
        Route::get('/credits/history', [MembershipController::class, 'creditUsageHistory'])->name('membership.credits.history');
        Route::get('/credit-packs', [MembershipController::class, 'creditPacks'])->name('membership.credit-packs');
        Route::post('/credit-packs/purchase', [MembershipController::class, 'purchaseCreditPack'])->name('membership.credit-packs.purchase');
        Route::get('/credit-packs/payment/{orderNo}', [MembershipController::class, 'creditPayment'])->name('membership.credit-payment');
        Route::get('/usage', [MembershipController::class, 'usage'])->name('membership.usage');
        Route::post('/subscribe', [MembershipController::class, 'subscribe'])->name('membership.subscribe');
        Route::get('/payment/{orderNo}', [MembershipController::class, 'payment'])->name('membership.payment');
        Route::post('/payment/{orderNo}/method', [MembershipController::class, 'updateSubscriptionPaymentMethod'])->name('membership.payment.method');
        Route::post('/credit-packs/payment/{orderNo}/method', [MembershipController::class, 'updateCreditPaymentMethod'])->name('membership.credit-payment.method');
        Route::get('/orders', [MembershipController::class, 'orders'])->name('membership.orders');
        Route::post('/orders/{order}/cancel', [MembershipController::class, 'cancelOrder'])->name('membership.orders.cancel');
        Route::post('/credit-orders/{order}/cancel', [MembershipController::class, 'cancelCreditOrder'])->name('membership.credit-orders.cancel');

        Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->name('verification.send');

        // Offer 对比
        Route::get('/offers/compare', [KanbanController::class, 'offersCompare'])->name('offers.compare');

        // 面试日历
        Route::get('/interviews/calendar', [InterviewController::class, 'calendar'])->name('interviews.calendar');

        // ========== 智能岗位推荐引擎 ==========
        Route::prefix('recommendations')->name('recommendations.')->group(function () {
            Route::get('/', [JobRecommendationController::class, 'index'])->name('index');
            Route::post('/generate', [JobRecommendationController::class, 'generate'])
                ->middleware('throttle:recommend-generate')
                ->name('generate');
            Route::get('/progress', [JobRecommendationController::class, 'progress'])
                ->middleware('throttle:30,1')
                ->name('progress');
            // #15 已查看状态
            Route::post('/{recommendation}/view', [JobRecommendationController::class, 'markViewed'])
                ->name('view');
            Route::post('/{recommendation}/apply', [JobRecommendationController::class, 'markApplied'])
                ->name('apply');
            Route::post('/{recommendation}/dismiss', [JobRecommendationController::class, 'dismiss'])
                ->name('dismiss');
            // #19 收藏切换
            Route::post('/{recommendation}/favorite', [JobRecommendationController::class, 'toggleFavorite'])
                ->name('favorite');
            // #20 推荐详情页
            Route::get('/{recommendation}', [JobRecommendationController::class, 'show'])
                ->name('show');
            Route::post('/batch-dismiss', [JobRecommendationController::class, 'batchDismiss'])
                ->name('batch-dismiss');
        });

        // ========== AI 薪资谈判助手 ==========
        Route::prefix('salary')->name('salary.')->group(function () {
            Route::get('/', [SalaryController::class, 'index'])->name('index');
            Route::get('/chart', [SalaryController::class, 'chartData'])->name('chart');
            Route::post('/report', [SalaryController::class, 'report'])->name('report');
            Route::get('/negotiate', [SalaryController::class, 'negotiate'])->name('negotiate');
            Route::post('/negotiate', [SalaryController::class, 'startNegotiation'])
                ->middleware('throttle:salary-analyze')
                ->name('negotiate.start');
            Route::get('/negotiate/history', [SalaryController::class, 'negotiationHistory'])
                ->name('negotiate.history');
            Route::get('/negotiate/{session}', [SalaryController::class, 'negotiationResult'])
                ->name('negotiate.result');
        });

        // ========== AI 职业测评 ==========
        Route::prefix('assessments')->name('assessments.')->group(function () {
            Route::get('/', [AssessmentController::class, 'index'])->name('index');
            Route::get('/history', [AssessmentController::class, 'history'])->name('history');
            Route::get('/{type}/start', [AssessmentController::class, 'start'])->name('start');
            Route::get('/{type}/questions', [AssessmentController::class, 'questions'])->name('questions');
            Route::post('/{type}/submit', [AssessmentController::class, 'submit'])
                ->middleware('throttle:assessment-submit')
                ->name('submit');
            Route::get('/result/{assessment}', [AssessmentController::class, 'result'])->name('result');
        });

        // ========== 技能评估与学习路径 ==========
        Route::prefix('skills')->name('skills.')->group(function () {
            Route::get('/', [SkillAssessmentController::class, 'index'])->name('index');
            Route::post('/', [SkillAssessmentController::class, 'store'])->name('store');
            Route::put('/{skill}', [SkillAssessmentController::class, 'update'])->name('update');
            Route::delete('/{skill}', [SkillAssessmentController::class, 'destroy'])->name('destroy');
            Route::get('/radar', [SkillAssessmentController::class, 'radar'])->name('radar');

            // 学习路径
            Route::prefix('learn')->name('learn.')->group(function () {
                Route::get('/', [LearningPathController::class, 'index'])->name('index');
                Route::post('/analyze', [LearningPathController::class, 'analyze'])
                    ->middleware('throttle:skill-analyze')
                    ->name('analyze');
                Route::get('/history', [LearningPathController::class, 'history'])->name('history');
                Route::get('/{path}', [LearningPathController::class, 'show'])->name('show');
            });
        });
    });
});
