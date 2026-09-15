<?php

declare(strict_types=1);

namespace App\Services\Resume\Template;

use App\Models\ResumeTemplate;
use Illuminate\Support\Collection;

final class TemplateRecommendationService
{
    public function __construct(
        private readonly TemplatePresentationService $presentationService,
    ) {}

    /**
     * @return Collection<int, ResumeTemplate>
     */
    public function buildRelatedTemplates(ResumeTemplate $sourceTemplate, int $targetCount = 6): Collection
    {
        $focusLabel = $this->presentationService->focusLabelByStyle((string) $sourceTemplate->style);
        $focusStyleMap = $this->presentationService->focusStyleMap();
        $focusStyles = $focusStyleMap[$focusLabel] ?? [];

        $relatedTemplates = $this->fetchSamePosition($sourceTemplate);

        if ($relatedTemplates->count() < $targetCount && ! empty($focusStyles)) {
            $relatedTemplates = $this->appendSameFocus($relatedTemplates, $sourceTemplate, $focusStyles, $targetCount);
        }

        if ($relatedTemplates->count() < $targetCount) {
            $relatedTemplates = $this->appendSameCategory($relatedTemplates, $sourceTemplate, $targetCount);
        }

        if ($relatedTemplates->count() < $targetCount) {
            $relatedTemplates = $this->appendPopularFallback($relatedTemplates, $sourceTemplate, $targetCount);
        }

        return $this->presentationService->normalizeRelatedTemplates($relatedTemplates);
    }

    /**
     * @return Collection<int, ResumeTemplate>
     */
    private function fetchSamePosition(ResumeTemplate $sourceTemplate): Collection
    {
        $templates = ResumeTemplate::query()
            ->active()
            ->where('id', '!=', $sourceTemplate->id)
            ->where('position', $sourceTemplate->position)
            ->orderByDesc('is_featured')
            ->orderByDesc('usage_count')
            ->limit((int) config('ui.limit.template_preview', 2))
            ->get();

        $templates->each(static function (ResumeTemplate $template): void {
            $template->setAttribute('recommend_reason', '同岗位');
        });

        return $templates;
    }

    /**
     * @param  Collection<int, ResumeTemplate>  $existing
     * @param  array<int, string>  $focusStyles
     * @return Collection<int, ResumeTemplate>
     */
    private function appendSameFocus(Collection $existing, ResumeTemplate $sourceTemplate, array $focusStyles, int $targetCount): Collection
    {
        $needed = $targetCount - $existing->count();
        $excludeIds = $existing->pluck('id')->push($sourceTemplate->id)->all();

        $sameFocus = ResumeTemplate::query()
            ->active()
            ->whereNotIn('id', $excludeIds)
            ->whereIn('style', $focusStyles)
            ->orderByDesc('is_featured')
            ->orderByDesc('usage_count')
            ->limit($needed)
            ->get();

        $sameFocus->each(static function (ResumeTemplate $template): void {
            $template->setAttribute('recommend_reason', '同侧重');
        });

        return $existing->concat($sameFocus);
    }

    /**
     * @param  Collection<int, ResumeTemplate>  $existing
     * @return Collection<int, ResumeTemplate>
     */
    private function appendSameCategory(Collection $existing, ResumeTemplate $sourceTemplate, int $targetCount): Collection
    {
        $needed = $targetCount - $existing->count();
        $excludeIds = $existing->pluck('id')->push($sourceTemplate->id)->all();

        $sameCategory = ResumeTemplate::query()
            ->active()
            ->whereNotIn('id', $excludeIds)
            ->where('category', $sourceTemplate->category)
            ->orderByDesc('is_featured')
            ->orderByDesc('usage_count')
            ->limit($needed)
            ->get();

        $sameCategory->each(static function (ResumeTemplate $template): void {
            $template->setAttribute('recommend_reason', '同分类');
        });

        return $existing->concat($sameCategory);
    }

    /**
     * @param  Collection<int, ResumeTemplate>  $existing
     * @return Collection<int, ResumeTemplate>
     */
    private function appendPopularFallback(Collection $existing, ResumeTemplate $sourceTemplate, int $targetCount): Collection
    {
        $needed = $targetCount - $existing->count();
        $excludeIds = $existing->pluck('id')->push($sourceTemplate->id)->all();

        $fallback = ResumeTemplate::query()
            ->active()
            ->whereNotIn('id', $excludeIds)
            ->orderByDesc('is_featured')
            ->orderByDesc('usage_count')
            ->limit($needed)
            ->get();

        $fallback->each(static function (ResumeTemplate $template): void {
            $template->setAttribute('recommend_reason', '热门');
        });

        return $existing->concat($fallback);
    }
}
