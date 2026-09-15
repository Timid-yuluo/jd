<?php

declare(strict_types=1);

namespace App\Services\Resume\Template;

use App\Models\Resume;
use App\Models\ResumeTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class TemplateUserDataService
{
    /**
     * @return Collection<int, Resume>
     */
    public function recentResumes(Request $request, int $limit = 6): Collection
    {
        $user = $request->user();
        if (! $user) {
            return collect();
        }

        return Resume::query()
            ->where('user_id', (int) $user->id)
            ->orderByDesc('updated_at')
            ->limit(max(1, $limit))
            ->get(['id', 'title', 'updated_at']);
    }

    /**
     * @return Collection<int, ResumeTemplate>
     */
    public function recentTemplates(Request $request, int $limit = 6): Collection
    {
        $user = $request->user();
        if (! $user) {
            return collect();
        }

        $recentResumes = Resume::query()
            ->where('user_id', (int) $user->id)
            ->whereNotNull('content_structured')
            ->orderByDesc('updated_at')
            ->limit((int) config('ui.limit.template_search', 30))
            ->get(['content_structured', 'updated_at']);

        $templateIds = [];
        foreach ($recentResumes as $resume) {
            $contentStructured = is_array($resume->content_structured) ? $resume->content_structured : [];
            $templateId = (int) ($contentStructured['template_source']['template_id'] ?? 0);
            if ($templateId <= 0 || in_array($templateId, $templateIds, true)) {
                continue;
            }
            $templateIds[] = $templateId;
            if (count($templateIds) >= $limit) {
                break;
            }
        }

        if ($templateIds === []) {
            return collect();
        }

        $templateMap = ResumeTemplate::query()
            ->active()
            ->whereIn('id', $templateIds)
            ->get()
            ->keyBy('id');

        return collect($templateIds)
            ->map(static fn (int $id): ?ResumeTemplate => $templateMap->get($id))
            ->filter(static fn (?ResumeTemplate $template): bool => $template instanceof ResumeTemplate)
            ->values();
    }

    /**
     * @return Collection<int, array{id:int,title:string}>
     */
    public function resumeTargets(Request $request): Collection
    {
        $user = $request->user();
        if (! $user) {
            return collect();
        }

        return Resume::query()
            ->where('user_id', (int) $user->id)
            ->orderByDesc('updated_at')
            ->limit((int) config('ui.limit.template_hot', 12))
            ->get(['id', 'title'])
            ->map(static function (Resume $resume): array {
                $title = trim((string) $resume->title);
                if ($title === '') {
                    $title = '未命名简历 #'.$resume->id;
                }

                return [
                    'id' => (int) $resume->id,
                    'title' => $title,
                ];
            })
            ->values();
    }
}
