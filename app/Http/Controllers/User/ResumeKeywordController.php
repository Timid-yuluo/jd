<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Services\Resume\Keyword\KeywordExtractorFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ResumeKeywordController extends Controller
{
    use RespondsWithJsonSuccess;

    public function __construct(
        private readonly KeywordExtractorFactory $extractorFactory,
    ) {}

    public function extract(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:12000'],
            'title' => ['nullable', 'string', 'max:255'],
            'max_count' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $extractor = $this->extractorFactory->make();
        $keywords = $extractor->extract(
            (string) $validated['text'],
            (string) ($validated['title'] ?? ''),
            (int) ($validated['max_count'] ?? 20)
        );
        $this->markQuotaConsumptionSuccess($request);

        return $this->respondSuccessPayload([
            'driver' => (string) config('resume.keyword_extractor.driver', 'simple'),
            'keywords' => $keywords,
        ]);
    }

    public function parseJd(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'min:20', 'max:12000'],
        ]);

        $text = (string) $validated['text'];
        $result = $this->parseJdStructure($text);

        return $this->respondSuccessPayload($result);
    }

    private function parseJdStructure(string $text): array
    {
        $textLower = mb_strtolower($text);
        $hardSkillPatterns = config('jd_keywords.hard_skills', []);
        $softSkillPatterns = config('jd_keywords.soft_skills', []);

        $foundHardSkills = [];
        foreach ($hardSkillPatterns as $skill) {
            if (str_contains($textLower, $skill)) {
                $foundHardSkills[] = $skill;
            }
        }

        $foundSoftSkills = [];
        foreach ($softSkillPatterns as $pattern => $label) {
            if (preg_match('/' . $pattern . '/i', $textLower)) {
                $foundSoftSkills[] = $label;
            }
        }

        $experienceYears = null;
        if (preg_match('/(\d+)\s*[年+]\s*以上?\s*(?:工作|开发|相关|经验)/', $text, $m)) {
            $experienceYears = (int) $m[1];
        } elseif (preg_match('/(\d+)\+?\s*years?\s*(?:of\s+)?(?:experience|work)/i', $text, $m)) {
            $experienceYears = (int) $m[1];
        }

        $education = null;
        if (preg_match('/博士/', $text)) $education = '博士';
        elseif (preg_match('/硕士/', $text)) $education = '硕士';
        elseif (preg_match('/本科|学士|bachelor/i', $text)) $education = '本科';
        elseif (preg_match('/大专|专科/', $text)) $education = '大专';

        $salary = null;
        if (preg_match('/(\d+)[kK-]+(\d+)[kK]?/', $text, $m)) {
            $salary = $m[1] . 'K-' . $m[2] . 'K';
        }

        return [
            'hard_skills' => array_values(array_unique($foundHardSkills)),
            'soft_skills' => array_values(array_unique($foundSoftSkills)),
            'experience_years' => $experienceYears,
            'education' => $education,
            'salary' => $salary,
        ];
    }
}
