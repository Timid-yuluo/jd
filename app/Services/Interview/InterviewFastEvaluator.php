<?php

declare(strict_types=1);

namespace App\Services\Interview;

use App\Support\Interview\AnswerAnalyzer;

final class InterviewFastEvaluator
{
    /**
     * @param  array<string,mixed>  $context
     * @return array{
     *   score:int,
     *   feedback:array<string,string>,
     *   termination:array{should_end:bool,reason:string,confidence:float},
     *   fluency:array{is_fluent:bool,severity:string,confidence:float,issues:array<int,string>,detected_by:string},
     *   dialogue:array{action:string,follow_up:string,follow_ups:array<int,string>,coach_reply:string,confidence:float}
     * }|null
     */
    public function quickEvaluateByRules(string $answer, array $context): ?array
    {
        if (! (bool) config('interview.evaluation_fast_path_enabled', true)) {
            return null;
        }

        $length = mb_strlen($answer);
        $shortThreshold = max(8, (int) config('interview.evaluation_fast_path_short_answer_chars', 20));
        $isNonAnswer = AnswerAnalyzer::isNonAnswer($answer);
        $fluencyRisk = AnswerAnalyzer::hasFluencyRisk($answer);

        if (! $isNonAnswer && $length >= $shortThreshold && ! $fluencyRisk) {
            return null;
        }

        if ($isNonAnswer) {
            return [
                'score' => 1,
                'feedback' => [
                    'comment' => '该回答为无效信息（如"我不知道"），无法体现岗位能力。',
                    'suggestion' => '请给一个最接近的真实案例，至少说明背景、行动和结果。',
                ],
                'termination' => [
                    'should_end' => false,
                    'reason' => '',
                    'confidence' => 0.0,
                ],
                'fluency' => [
                    'is_fluent' => false,
                    'severity' => 'high',
                    'confidence' => 1.0,
                    'issues' => ['无效回答'],
                    'detected_by' => 'rule',
                ],
                'dialogue' => [
                    'action' => 'deep_probe',
                    'follow_up' => '请先给一个最接近的真实案例：背景是什么，你做了什么，结果如何？',
                    'follow_ups' => [
                        '先补一句背景：场景目标和限制条件是什么？',
                        '再补两条关键动作和一个结果指标。',
                    ],
                    'coach_reply' => '先别着急，给一个小案例也可以，我们一起把回答补完整。',
                    'confidence' => 0.9,
                ],
            ];
        }

        if ($fluencyRisk) {
            return [
                'score' => min(5, max(2, (int) round($length / 12))),
                'feedback' => [
                    'comment' => '当前表达不够通顺，存在重复或断句不清，影响面试官理解。',
                    'suggestion' => '建议分 3-4 句回答：背景、动作、结果、复盘，减少口头禅重复。',
                ],
                'termination' => [
                    'should_end' => false,
                    'reason' => '',
                    'confidence' => 0.0,
                ],
                'fluency' => [
                    'is_fluent' => false,
                    'severity' => 'medium',
                    'confidence' => 0.9,
                    'issues' => ['表达不连贯'],
                    'detected_by' => 'rule',
                ],
                'dialogue' => [
                    'action' => 'probe',
                    'follow_up' => '请用4句短句重述：背景一句、行动两句、结果一句。',
                    'follow_ups' => [],
                    'coach_reply' => '你的思路有了，建议先分点表达，让信息更清晰。',
                    'confidence' => 0.88,
                ],
            ];
        }

        $mediumThreshold = max($shortThreshold + 1, (int) config('interview.evaluation_fast_path_medium_answer_chars', 60));
        $hasAction = preg_match('/(负责|推动|制定|执行|协调|落地|复盘|优化|跟进|达成)/u', $answer) === 1;
        $hasMetrics = preg_match('/(\d+%|\d+\+|[1-9]\d{1,}|提升|增长|降低|缩短|减少|节省)/u', $answer) === 1;
        $preferFastMedium = (bool) config('interview.evaluation_fast_path_medium_enabled', true);
        if ($preferFastMedium && $length <= $mediumThreshold && (! $hasAction || ! $hasMetrics)) {
            $tips = [];
            if (! $hasAction) {
                $tips[] = '补充你具体采取的关键动作。';
            }
            if (! $hasMetrics) {
                $tips[] = '补充结果指标（如效率、成本、转化率变化）。';
            }
            $suggestion = $tips !== [] ? implode('', $tips) : '建议补充关键动作与结果指标。';

            return [
                'score' => min(6, max(3, (int) round($length / 12))),
                'feedback' => [
                    'comment' => '回答信息已收到，系统已快速返回评估，建议补充细节提升说服力。',
                    'suggestion' => $suggestion,
                ],
                'termination' => [
                    'should_end' => false,
                    'reason' => '',
                    'confidence' => 0.0,
                ],
                'fluency' => [
                    'is_fluent' => true,
                    'severity' => 'none',
                    'confidence' => 0.72,
                    'issues' => [],
                    'detected_by' => 'rule',
                ],
                'dialogue' => [
                    'action' => 'probe',
                    'follow_up' => ! $hasAction
                        ? '你当时具体做了哪些动作？请按顺序说2-3步。'
                        : '结果层面有什么量化变化？请补1-2个指标。',
                    'follow_ups' => [],
                    'coach_reply' => '方向基本对了，再补一个关键细节就会更完整。',
                    'confidence' => 0.78,
                ],
            ];
        }

        return [
            'score' => min(4, max(2, (int) round($length / 10))),
            'feedback' => [
                'comment' => '回答偏短，系统已进行快速评分以提升反馈速度。',
                'suggestion' => '建议补充场景背景、关键动作和量化结果后再继续作答。',
            ],
            'termination' => [
                'should_end' => false,
                'reason' => '',
                'confidence' => 0.0,
            ],
            'fluency' => [
                'is_fluent' => true,
                'severity' => 'none',
                'confidence' => 0.7,
                'issues' => [],
                'detected_by' => 'rule',
            ],
            'dialogue' => [
                'action' => 'continue',
                'follow_up' => '',
                'follow_ups' => [],
                'coach_reply' => '',
                'confidence' => 0.6,
            ],
        ];
    }

    /**
     * @param  array<string,string>  $feedback
     * @param  array{should_end:bool,reason:string,confidence:float}  $termination
     * @param  array{is_fluent:bool,severity:string,confidence:float,issues:array<int,string>,detected_by:string}  $fluency
     * @param  array{action:string,follow_up:string,follow_ups:array<int,string>,coach_reply:string,confidence:float}  $dialogue
     * @return array{
     *   score:int,
     *   feedback:array<string,string>,
     *   termination:array{should_end:bool,reason:string,confidence:float},
     *   fluency:array{is_fluent:bool,severity:string,confidence:float,issues:array<int,string>,detected_by:string},
     *   dialogue:array{action:string,follow_up:string,follow_ups:array<int,string>,coach_reply:string,confidence:float}
     * }
     */
    public function normalizeByAnswer(int $score, array $feedback, array $termination, array $fluency, array $dialogue, string $answer): array
    {
        $answer = trim($answer);
        $length = mb_strlen($answer);
        $isNonAnswer = AnswerAnalyzer::isNonAnswer($answer);
        $fluencyRisk = ! ((bool) ($fluency['is_fluent'] ?? true));

        if ($isNonAnswer) {
            return [
                'score' => min($score, 2),
                'feedback' => [
                    'comment' => '该回答信息量较低（如"我不知道"），暂时无法体现你的岗位能力。',
                    'suggestion' => '建议按"背景-行动-结果-复盘"补充一个真实案例，即使是小项目也可以。',
                ],
                'termination' => $termination,
                'fluency' => $fluency,
                'dialogue' => $dialogue,
            ];
        }

        if ($length < 20) {
            return [
                'score' => min($score, 4),
                'feedback' => [
                    'comment' => '回答偏短，细节不足，暂时难以判断你的方法论和解决能力。',
                    'suggestion' => '建议至少补充：场景背景、你的动作、结果数据与复盘。',
                ],
                'termination' => $termination,
                'fluency' => $fluency,
                'dialogue' => $dialogue,
            ];
        }

        if ($fluencyRisk) {
            return [
                'score' => min($score, 7),
                'feedback' => [
                    'comment' => '表达连贯性存在改进空间，但我们会结合回答内容综合评估，不会仅因语句问题直接否定能力。',
                    'suggestion' => '建议先用短句分点作答，减少口头禅重复，再补充关键动作与结果。',
                ],
                'termination' => $termination,
                'fluency' => $fluency,
                'dialogue' => $dialogue,
            ];
        }

        return [
            'score' => $score,
            'feedback' => [
                'comment' => trim((string) ($feedback['comment'] ?? '')) ?: '回答已收到，请继续保持结构化表达。',
                'suggestion' => trim((string) ($feedback['suggestion'] ?? '')) ?: '建议补充关键动作、结果指标和复盘改进点。',
            ],
            'termination' => $termination,
            'fluency' => $fluency,
            'dialogue' => $dialogue,
        ];
    }

    /**
     * @param array{
     *   score:int,
     *   feedback:array<string,string>,
     *   termination:array{should_end:bool,reason:string,confidence:float},
     *   fluency:array{is_fluent:bool,severity:string,confidence:float,issues:array<int,string>,detected_by:string},
     *   dialogue:array{action:string,follow_up:string,follow_ups:array<int,string>,coach_reply:string,confidence:float}
     * } $result
     * @param  array<string,mixed>  $context
     * @return array{
     *   score:int,
     *   feedback:array<string,string>,
     *   termination:array{should_end:bool,reason:string,confidence:float},
     *   fluency:array{is_fluent:bool,severity:string,confidence:float,issues:array<int,string>,detected_by:string},
     *   dialogue:array{action:string,follow_up:string,follow_ups:array<int,string>,coach_reply:string,confidence:float}
     * }
     */
    public function applyTypePolicy(array $result, string $answer, array $context): array
    {
        $type = (string) ($context['interview_type'] ?? '');
        $dimension = (string) ($context['dimension'] ?? '');
        $length = mb_strlen(trim($answer));
        $hasAction = preg_match('/(负责|推动|制定|执行|协调|落地|复盘|优化|跟进|达成)/u', $answer) === 1;
        $hasMetrics = preg_match('/(\d+%|\d+\+|[1-9]\d{1,}|提升|增长|降低|缩短|减少|节省)/u', $answer) === 1;
        $fluencyRisk = ! ((bool) ($result['fluency']['is_fluent'] ?? true));

        $isTechnical = $type === 'technical' || preg_match('/(技术|系统|架构|性能|工程|故障)/u', $dimension) === 1;
        $isBehavioral = $type === 'behavioral' || preg_match('/(沟通|协作|冲突|表达|影响力|复盘)/u', $dimension) === 1;

        if ($isTechnical) {
            if (! $hasAction) {
                $result['score'] = min($result['score'], 5);
                $result['feedback']['suggestion'] = $this->appendSuggestion(
                    $result['feedback']['suggestion'] ?? '',
                    '技术题建议明确方案步骤与关键决策依据。'
                );
            }
            if (! $hasMetrics) {
                $result['score'] = min($result['score'], 5);
                $result['feedback']['suggestion'] = $this->appendSuggestion(
                    $result['feedback']['suggestion'] ?? '',
                    '技术题建议补充量化结果，如耗时、性能或稳定性变化。'
                );
            }
            if ($hasAction && $hasMetrics && $length >= 80) {
                $result['score'] = min(10, max($result['score'], $result['score'] + 1));
            }
        }

        if ($isBehavioral) {
            $hasCollaboration = preg_match('/(团队|协作|沟通|冲突|推进|复盘)/u', $answer) === 1;
            if (! $hasCollaboration) {
                $result['score'] = min($result['score'], 6);
                $result['feedback']['suggestion'] = $this->appendSuggestion(
                    $result['feedback']['suggestion'] ?? '',
                    '行为题建议补充协作对象、分歧处理和复盘改进。'
                );
            }
            if ($fluencyRisk) {
                $result['score'] = min($result['score'], 5);
                $result['feedback']['comment'] = '表达略有跳跃，建议分点说明背景、行动、结果与复盘。';
            }
            if ($hasCollaboration && ! $fluencyRisk && $length >= 80) {
                $result['score'] = min(10, max($result['score'], $result['score'] + 1));
            }
        }

        $result['score'] = max(1, min(10, (int) $result['score']));

        return $result;
    }

    /**
     * @return array{is_fluent:bool,severity:string,confidence:float,issues:array<int,string>,detected_by:string}
     */
    public function extractFluency(array $aiEval, string $answer): array
    {
        $raw = is_array($aiEval['fluency'] ?? null) ? $aiEval['fluency'] : [];
        if ($raw !== []) {
            $issues = is_array($raw['issues'] ?? null) ? $raw['issues'] : [];

            return [
                'is_fluent' => (bool) ($raw['is_fluent'] ?? true),
                'severity' => (string) ($raw['severity'] ?? 'none'),
                'confidence' => max(0.0, min(1.0, (float) ($raw['confidence'] ?? 0.7))),
                'issues' => array_values(array_filter(array_map(static fn ($i): string => trim((string) $i), $issues))),
                'detected_by' => 'ai',
            ];
        }

        return [
            'is_fluent' => ! AnswerAnalyzer::hasFluencyRisk($answer),
            'severity' => AnswerAnalyzer::hasFluencyRisk($answer) ? 'medium' : 'none',
            'confidence' => 0.65,
            'issues' => AnswerAnalyzer::hasFluencyRisk($answer) ? ['规则判断可能存在表达不连贯'] : [],
            'detected_by' => 'rule',
        ];
    }

    private function appendSuggestion(string $base, string $extra): string
    {
        $base = trim($base);
        if ($base === '') {
            return $extra;
        }
        if (str_contains($base, $extra)) {
            return $base;
        }

        return "{$base}；{$extra}";
    }
}
