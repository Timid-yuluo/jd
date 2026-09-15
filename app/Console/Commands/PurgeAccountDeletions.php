<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AccountRecoveryToken;
use App\Models\Feedback;
use App\Models\FeedbackReward;
use App\Models\JobApplication;
use App\Models\JobBookmark;
use App\Models\JobMatchBatch;
use App\Models\LoginHistory;
use App\Models\Order;
use App\Models\PasswordHistory;
use App\Models\QuotaUsage;
use App\Models\Resume;
use App\Models\ResumeExportTask;
use App\Models\ResumeOptimizeSession;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserCredit;
use App\Models\UserOAuthAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PurgeAccountDeletions extends Command
{
    protected $signature = 'accounts:purge-deletions';

    protected $description = 'Permanently delete accounts whose 7-day cooling period has expired';

    public function handle(): int
    {
        $users = User::query()
            ->where('deletion_scheduled_at', '<', now())
            ->get();

        if ($users->isEmpty()) {
            $this->info('No accounts to purge.');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($users as $user) {
            DB::transaction(function () use ($user, &$count): void {
                $this->purgeUserData($user);
                $count++;
            });
        }

        Log::info('Purged deleted accounts', ['count' => $count]);
        $this->info("Purged {$count} accounts.");

        $expiredTokens = AccountRecoveryToken::query()
            ->where('expires_at', '<', now())
            ->delete();

        if ($expiredTokens > 0) {
            Log::info('Cleaned expired recovery tokens', ['count' => $expiredTokens]);
        }

        return self::SUCCESS;
    }

    public function purgeUserData(User $user): void
    {
        Resume::where('user_id', $user->id)->forceDelete();
        ResumeExportTask::where('user_id', $user->id)->delete();
        ResumeOptimizeSession::where('user_id', $user->id)->delete();
        JobApplication::where('user_id', $user->id)->delete();
        JobBookmark::where('user_id', $user->id)->delete();
        JobMatchBatch::where('user_id', $user->id)->delete();
        Subscription::where('user_id', $user->id)->delete();
        Order::where('user_id', $user->id)->delete();
        UserCredit::where('user_id', $user->id)->delete();
        QuotaUsage::where('user_id', $user->id)->delete();
        LoginHistory::where('user_id', $user->id)->delete();
        UserOAuthAccount::where('user_id', $user->id)->delete();
        PasswordHistory::where('user_id', $user->id)->delete();
        AccountRecoveryToken::where('user_id', $user->id)->delete();
        FeedbackReward::where('user_id', $user->id)->delete();
        Feedback::where('user_id', $user->id)->delete();

        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('user_notifications')->where('user_id', $user->id)->delete();
        DB::table('usage_logs')->where('user_id', $user->id)->delete();
        DB::table('user_action_logs')->where('user_id', $user->id)->delete();
        DB::table('email_logs')->where('user_id', $user->id)->delete();
        DB::table('job_match_analyses')->where('user_id', $user->id)->delete();
        DB::table('resume_optimize_apply_logs')->where('user_id', $user->id)->delete();
        DB::table('credit_pack_orders')->where('user_id', $user->id)->delete();
        DB::table('interview_sessions')->where('user_id', $user->id)->delete();

        $user->forceDelete();
    }
}
