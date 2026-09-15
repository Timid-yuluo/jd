<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'wechat_openid',
    'school',
    'major',
    'verification_token',
    'verification_token_expires_at',
    'email_notifications_enabled',
    'notify_resume_completed',
    'notify_interview_started',
    'notify_interview_completed',
    'notify_job_application',
    'notify_deadline_reminder',
    'notify_marketing',
    'email_preferences_updated_at',
    'current_plan_slug',
    'deletion_requested_at',
    'deletion_scheduled_at',
    'deletion_reason',
    'deletion_feedback',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $guarded = [
        'id',
        'is_admin',
        'is_suspended',
        'suspended_at',
        'suspended_reason',
        'suspended_by',
        'verification_token',
        'verification_token_expires_at',
        'password',
        'remember_token',
        'current_plan_slug',
        'deletion_requested_at',
        'deletion_scheduled_at',
    ];

    private ?Plan $cachedCurrentPlan = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'verification_token_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'wechat_openid' => 'encrypted',
            'is_suspended' => 'boolean',
            'suspended_at' => 'datetime',
            'email_notifications_enabled' => 'boolean',
            'notify_resume_completed' => 'boolean',
            'notify_interview_started' => 'boolean',
            'notify_interview_completed' => 'boolean',
            'notify_job_application' => 'boolean',
            'notify_deadline_reminder' => 'boolean',
            'notify_marketing' => 'boolean',
            'email_preferences_updated_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'deletion_scheduled_at' => 'datetime',
        ];
    }

    public function isSuspended(): bool
    {
        return (bool) $this->is_suspended;
    }

    public function isPendingDeletion(): bool
    {
        return $this->deletion_requested_at !== null && $this->deletion_scheduled_at !== null;
    }

    public function currentPlan(): ?Plan
    {
        return $this->cachedCurrentPlan ??= Plan::findBySlug($this->current_plan_slug ?? 'free');
    }

    public function clearPlanCache(): void
    {
        $this->cachedCurrentPlan = null;
    }

    public function setPlan(string $planSlug): void
    {
        $this->forceFill(['current_plan_slug' => $planSlug])->save();
    }

    public function suspend(User $byUser, string $reason = ''): void
    {
        $this->forceFill([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspended_reason' => $reason ?: null,
            'suspended_by' => $byUser->id,
        ])->save();
    }

    public function unsuspend(): void
    {
        $this->forceFill([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspended_reason' => null,
            'suspended_by' => null,
        ])->save();
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', Subscription::STATUS_ACTIVE);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function quotaUsages(): HasMany
    {
        return $this->hasMany(QuotaUsage::class);
    }

    public function userCredits(): HasMany
    {
        return $this->hasMany(UserCredit::class);
    }

    public function creditPackOrders(): HasMany
    {
        return $this->hasMany(CreditPackOrder::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    public function interviewSessions(): HasMany
    {
        return $this->hasMany(InterviewSession::class);
    }

    public function questionFavorites(): HasMany
    {
        return $this->hasMany(InterviewQuestionFavorite::class);
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(UsageLog::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class)->orderBy('created_at', 'desc');
    }

    public function adminActionLogs(): HasMany
    {
        return $this->hasMany(AdminActionLog::class);
    }

    public function oauthAccounts(): HasMany
    {
        return $this->hasMany(UserOAuthAccount::class);
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function jobMatchAnalyses(): HasMany
    {
        return $this->hasMany(JobMatchAnalysis::class);
    }

    public function jobBookmarks(): HasMany
    {
        return $this->hasMany(JobBookmark::class);
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function siteEvents(): HasMany
    {
        return $this->hasMany(SiteEvent::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_suspended', false);
    }

    public function scopeSuspended(Builder $query): void
    {
        $query->where('is_suspended', true);
    }

    public function scopeAdmin(Builder $query): void
    {
        $query->where('is_admin', true);
    }

    /**
     * 发送密码重置通知
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ], false));

        $this->notify(new class($url) extends Notification
        {
            public function __construct(public string $url) {}

            public function via($notifiable): array
            {
                return ['mail'];
            }

            public function toMail($notifiable): MailMessage
            {
                return (new MailMessage)
                    ->subject('重置密码 - '.config('app.name'))
                    ->markdown('emails.reset-password', [
                        'url' => $this->url,
                    ]);
            }
        });
    }
}
