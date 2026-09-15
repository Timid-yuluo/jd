<?php

declare(strict_types=1);

namespace App\Services\Feedback;

use App\Models\Feedback;
use App\Models\FeedbackReward;
use App\Services\Membership\CreditService;
use App\Services\Notification\UserNotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

interface FeedbackRewardServiceInterface
{
    public function grant(Feedback $feedback, int $adminUserId, array $payload): FeedbackReward;
}

final class FeedbackRewardService implements FeedbackRewardServiceInterface
{
    public function __construct(
        private readonly CreditService $creditService,
        private readonly UserNotificationService $userNotificationService,
    ) {}

    /**
     * @param  array{
     *     quota_key:?string,
     *     credits:int,
     *     validity_days:?int,
     *     reason:?string,
     *     adoption_note:?string
     * }  $payload
     */
    public function grant(Feedback $feedback, int $adminUserId, array $payload): FeedbackReward
    {
        return DB::transaction(function () use ($feedback, $adminUserId, $payload): FeedbackReward {
            if (! $feedback->exists) {
                throw new RuntimeException('反馈不存在');
            }

            $feedback->loadMissing(['user', 'reward']);

            if (! $feedback->user) {
                throw new RuntimeException('匿名反馈暂不支持赠送次卡');
            }

            if ($feedback->reward !== null) {
                throw new RuntimeException('该反馈已赠送过次卡，请勿重复发放');
            }

            $feedback->update([
                'adoption_status' => Feedback::ADOPTION_ADOPTED,
                'adopted_at' => now(),
                'adopted_by' => $adminUserId,
                'adoption_note' => $payload['adoption_note'] ?: $feedback->adoption_note,
            ]);

            try {
                $reward = FeedbackReward::query()->create([
                    'feedback_id' => $feedback->id,
                    'user_id' => $feedback->user->id,
                    'granted_by' => $adminUserId,
                    'quota_key' => $payload['quota_key'],
                    'credits' => $payload['credits'],
                    'validity_days' => $payload['validity_days'],
                    'reason' => $payload['reason'],
                ]);
            } catch (QueryException $exception) {
                throw new RuntimeException('该反馈已赠送过次卡，请勿重复发放', previous: $exception);
            }

            $userCredit = $this->creditService->feedbackRewardGrant(
                $feedback->user,
                $payload['quota_key'],
                $payload['credits'],
                $payload['validity_days'],
                $reward->id,
            );

            $reward->update([
                'user_credit_id' => $userCredit->id,
            ]);

            $this->userNotificationService->sendFeedbackRewardGranted($feedback, $reward);

            return $reward->load(['grantedBy', 'userCredit']);
        });
    }
}
