<?php

use App\Http\Controllers\AlipayPayController;
use App\Http\Controllers\Api\CspReportController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DeviceFingerprintController;
use App\Http\Controllers\PrivateFileController;
use App\Http\Controllers\Public\HelpController;
use App\Http\Controllers\Public\SiteEventController;
use App\Http\Controllers\User\EmailVerificationController;
use App\Http\Controllers\User\FeedbackController;
use App\Http\Controllers\User\ProfileController;
use App\Models\Plan;
use App\Models\SiteEvent;
use Illuminate\Support\Facades\Route;

Route::get('/share/{token}', [ShareController::class, 'show'])->name('share.resume');
Route::post('/share/{token}/verify', [ShareController::class, 'verifyPassword'])->middleware('throttle:10,1')->name('share.verify-password');

Route::get('/', function () {
    return view('welcome', [
        'latestEvents' => SiteEvent::latestPublished(3),
        'plans' => Plan::getActivePlans(),
    ]);
})->middleware(['maintenance', 'mobile']);

Route::get('/api/health', HealthController::class)->name('health');
Route::post('/csp-report', CspReportController::class)->name('csp.report');

Route::post('/api/track/duration', [TrackingController::class, 'duration'])->middleware('throttle:60,1')->name('track.duration');
Route::post('/api/track/event', [TrackingController::class, 'event'])->middleware('throttle:60,1')->name('track.event');

Route::get('/private/avatars/{filename}', [PrivateFileController::class, 'avatar'])
    ->name('private.avatar');

Route::post('/admin/device-fingerprint', [DeviceFingerprintController::class, 'store'])
    ->name('device.fingerprint.store')
    ->middleware('throttle:60,1');

Route::post('/alipay-pay/notify', [AlipayPayController::class, 'notify'])->name('alipay-pay.notify');

Route::get('/auth/github/callback', [OAuthController::class, 'callbackUnified'])
    ->defaults('provider', 'github');
Route::get('/auth/oauth/github/callback', [OAuthController::class, 'callbackUnified'])
    ->defaults('provider', 'github')
    ->name('auth.oauth.github.callback');
Route::get('/auth/alipay/callback', [OAuthController::class, 'callbackUnified'])->defaults('provider', 'alipay');
Route::get('/auth/oauth/alipay/callback', [OAuthController::class, 'callbackUnified'])
    ->defaults('provider', 'alipay')
    ->name('auth.oauth.alipay.callback');

Route::get('/mobile-tip', function () {
    return view('mobile-tip', [
        'siteUrl' => request()->getSchemeAndHttpHost(),
        'redirect' => request()->query('redirect'),
    ]);
})->name('mobile-tip');

Route::post('/mobile-tip/continue', function () {
    session(['mobile_continue' => true]);
    $redirect = request('redirect', '/');
    if (! is_string($redirect) || ! str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
        $redirect = '/';
    }

    return redirect($redirect);
})->name('mobile-tip.continue');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware(['throttle:login', 'admin.throttle'])->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:register')->name('register.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');

    Route::get('/auth/oauth/github/redirect', [OAuthController::class, 'redirectForLogin'])->defaults('provider', 'github')->name('auth.oauth.github.redirect');
    Route::get('/auth/oauth/alipay/redirect', [OAuthController::class, 'redirectForLogin'])->defaults('provider', 'alipay')->name('auth.oauth.alipay.redirect');
});

Route::get('/logout', [AuthenticatedSessionController::class, 'confirmLogout'])
    ->middleware('auth')
    ->name('logout.confirm');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])
    ->middleware('auth')
    ->name('password.confirm');

Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store'])
    ->middleware('auth');

Route::post('/feedback', [FeedbackController::class, 'store'])->middleware(['auth', 'throttle:feedback-submit'])->name('feedback.store');
Route::get('/feedback', [FeedbackController::class, 'index'])->middleware(['auth', 'maintenance', 'email.verification.required', 'mobile', 'throttle:60,1'])->name('feedback.index');
Route::get('/feedback/{feedback}', [FeedbackController::class, 'show'])->middleware(['auth', 'maintenance', 'email.verification.required', 'mobile', 'throttle:60,1'])->name('feedback.show');
Route::post('/feedback/{feedback}/reply', [FeedbackController::class, 'reply'])->middleware(['auth', 'throttle:feedback-submit'])->name('feedback.reply');
Route::post('/feedback/{feedback}/rate', [FeedbackController::class, 'rateSatisfaction'])->middleware(['auth', 'throttle:feedback-submit'])->name('feedback.rate');

Route::get('/email/verify/{token}', [EmailVerificationController::class, 'verify'])
    ->middleware('throttle:6,1')
    ->name('user.verification.verify');

// Account recovery (public, no auth — user was logged out during deletion)
Route::get('/account/recover/{token}', [ProfileController::class, 'recoverAccount'])
    ->middleware('throttle:6,1')
    ->name('account.recover');

// Help Center (public)
Route::get('/help', [HelpController::class, 'index'])->name('public.help.index');
Route::get('/help/search', [HelpController::class, 'search'])->name('public.help.search');
Route::get('/help/{categorySlug}', [HelpController::class, 'category'])->name('public.help.category');
Route::get('/help/{categorySlug}/{articleSlug}', [HelpController::class, 'show'])->name('public.help.show');

// Events (public)
Route::get('/events', [SiteEventController::class, 'index'])->name('public.events.index');
Route::get('/events/{event}', [SiteEventController::class, 'show'])->name('public.events.show');
Route::get('/events/{event}/content', [SiteEventController::class, 'content']);
