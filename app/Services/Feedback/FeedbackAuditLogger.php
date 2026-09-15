<?php

declare(strict_types=1);

namespace App\Services\Feedback;

use App\Models\Feedback;
use App\Models\FeedbackAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class FeedbackAuditLogger
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $context
     */
    public function log(
        Feedback $feedback,
        string $action,
        ?Request $request = null,
        array $oldValues = [],
        array $newValues = [],
        array $context = [],
    ): FeedbackAuditLog {
        return FeedbackAuditLog::query()->create([
            'feedback_id' => $feedback->id,
            'changed_by_user_id' => $request?->user()?->id,
            'action' => $action,
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'context' => $context === [] ? null : $this->normalizeContext($context),
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 500, ''),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function normalizeContext(array $context): array
    {
        $normalized = [];

        foreach ($context as $key => $value) {
            if (is_string($value)) {
                $normalized[$key] = Str::limit($value, 1000, '');

                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
