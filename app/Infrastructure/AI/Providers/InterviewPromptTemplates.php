<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Providers;

final class InterviewPromptTemplates
{
    public function generateQuestionSystemPrompt(): string
    {
        return <<<'PROMPT'
你是资深面试官，专注真实工作场景出题。请只生成"1道"面试问题，要求：
1) 出题数据源优先级：岗位JD > 应聘职位+技术关键词 > 简历经历 > 通用维度；
2) 若提供JD，问题必须紧密围绕岗位职责和任职要求设计；
3) 若无JD但有应聘职位和技术关键词，围绕该职位常见工作场景出题；
4) 若JD和关键词都不足，优先从简历中的项目经历、实习经历、教育背景中提取切入点；
5) 若提供公司名，可适度引入公司场景（"假设你入职XX公司..."）；
6) 语音模式问题更简短口语化（20-60字），文字模式可略长（30-120字）；
7) 若职位与简历目标岗位明显不匹配，第一题可温和询问转岗动机；
8) 优先考察候选人在工作中"做了什么、怎么做的、结果如何"，而非书本概念；
9) 难度随轮次递进，结合总题数分配深度：题少时每轮覆盖更多维度，题多时层层深挖；
10) 严禁与历史问题重复或语义近似重复；
11) 引导候选人用 STAR 框架（情境-任务-行动-结果）回答；
12) 简历锚点/关注点必须至少引用 1 个经历或关键词；
13) 应届生/转岗候选人：考察学习路径、小项目实践和成长潜力；
14) 经验深时出复盘/决策性问题，经验浅时出探索性问题；
15) 问题要"场景化"而非"问定义"；
16) 最后一题（剩余0题时）生成总结性追问：让候选人回顾整场面试、连接不同话题形成成长叙事、描述加入团队后的融入计划或职业愿景，而非继续考察细节；
17) 只返回 JSON，不要 markdown，不要解释。

返回格式：{"question":"...","tags":["标签1","标签2"],"difficulty_level":"easy|medium|hard"}
- tags：2-4个标签，描述该题考察的知识点或能力维度（如"项目复盘"、"沟通协作"、"SQL优化"等）
- difficulty_level：该题实际难度，easy=简单/medium=中等/hard=困难
PROMPT;
    }

    /**
     * @param  array<string,mixed>  $context
     */
    public function generateQuestionUserPrompt(string $position, int $round, array $context): string
    {
        $interviewType = (string) ($context['interview_type'] ?? 'mixed');
        $candidateProfile = (string) ($context['candidate_profile'] ?? 'fresh_graduate');
        $candidateProfileLabel = is_string($context['candidate_profile_label'] ?? null) ? (string) $context['candidate_profile_label'] : ($candidateProfile === 'no_experience' ? '无经验转岗' : ($candidateProfile === 'junior' ? '1-3年经验' : ($candidateProfile === 'experienced' ? '3年+经验' : '应届生')));
        $candidateTone = (string) ($context['candidate_tone'] ?? 'friendly');
        $candidateDifficulty = (string) ($context['candidate_difficulty'] ?? 'low_to_mid');
        $candidateFocus = is_array($context['candidate_focus'] ?? null) ? $context['candidate_focus'] : [];
        $resumeExperienceSignal = is_array($context['resume_experience_signal'] ?? null) ? $context['resume_experience_signal'] : [];
        $company = (string) ($context['company'] ?? '');
        $roundGoal = (string) ($context['round_goal'] ?? '');
        $focusDimension = (string) ($context['focus_dimension'] ?? '岗位综合能力');
        $resumeTitle = (string) ($context['resume_title'] ?? '');
        $resumeTargetJob = (string) ($context['resume_target_job'] ?? '');
        $resumeExcerpt = (string) ($context['resume_excerpt'] ?? '');
        $focusPoints = is_array($context['focus_points'] ?? null) ? $context['focus_points'] : [];
        $resumeAnchors = is_array($context['resume_anchors'] ?? null) ? $context['resume_anchors'] : [];
        $jobDescriptionExcerpt = (string) ($context['job_description_excerpt'] ?? '');
        $jobKeywords = is_array($context['job_keywords'] ?? null) ? $context['job_keywords'] : [];
        $previousQuestions = is_array($context['previous_questions'] ?? null) ? $context['previous_questions'] : [];
        $lastAnswerSummary = (string) ($context['last_answer_summary'] ?? '');
        $difficulty = (string) ($context['difficulty'] ?? 'medium');
        $techKeywords = (string) ($context['tech_keywords'] ?? '');
        $language = (string) ($context['language'] ?? 'zh');
        $dataRichness = (int) ($context['data_richness'] ?? 0);
        $totalQuestions = (int) ($context['total_questions'] ?? 5);
        $mode = (string) ($context['mode'] ?? 'text');
        $hasCompany = (bool) ($context['has_company'] ?? false);
        $positionAligned = (string) ($context['position_aligned'] ?? 'no_target');
        $adjustedDifficulty = (string) ($context['adjusted_difficulty'] ?? 'medium');
        $pacingStrategy = (string) ($context['pacing_strategy'] ?? '标准推进');
        $usedDimensions = is_array($context['used_dimensions'] ?? null) ? $context['used_dimensions'] : [];
        $remainingQuestions = (int) ($context['remaining_questions'] ?? 0);
        $hasJD = (bool) ($context['has_jd'] ?? false);
        $hasTechKws = (bool) ($context['has_tech_keywords'] ?? false);
        $hasPosition = (bool) ($context['has_position'] ?? false);

        $richnessLevel = $dataRichness >= 80 ? '充足（优先使用JD和关键词出题）' : ($dataRichness >= 40 ? '中等（JD不足，侧重简历和职位出题）' : '偏低（信息有限，侧重通用能力和简历经历出题）');
        $difficultyLabel = $difficulty === 'easy' ? '简单' : ($difficulty === 'hard' ? '困难' : '中等');

        return implode("\n", [
            "岗位：{$position}",
            "面试类型：{$interviewType}",
            "面试语言：".($language === 'en' ? '英文（请生成英文问题）' : '中文'),
            "难度设置：{$difficultyLabel}",
            "动态难度：".($adjustedDifficulty !== $difficulty ? "上题回答质量".($adjustedDifficulty === 'hard' ? '高，本轮加难' : '偏低，本轮降压').' → '.$adjustedDifficulty : '保持原难度'),
            "面试节奏：{$pacingStrategy}",
            "已考察维度（避免重复）：".(empty($usedDimensions) ? '无' : implode('、', array_slice($usedDimensions, -5))),
            "剩余题数：{$remainingQuestions} 题",
            "数据丰富度：{$richnessLevel}",
            "总题数：{$totalQuestions} 题".($totalQuestions <= 3 ? '（每轮覆盖多个维度）' : ($totalQuestions >= 7 ? '（层层递进深挖）' : '（标准递进）')),
            "面试模式：".($mode === 'voice' ? '语音（生成简短口语化问题）' : '文字（可略长，适合阅读）'),
            "职位-简历匹配：".($positionAligned === 'aligned' ? '✓ 一致' : ($positionAligned === 'misaligned' ? '⚠ 不一致，可询问转岗' : '未填目标')),
            "技术关键词：".($techKeywords !== '' ? $techKeywords : '未设置'),
            "候选人身份：{$candidateProfileLabel}",
            "出题语气：{$candidateTone}",
            "难度梯度：{$candidateDifficulty}",
            '身份关注点：'.(empty($candidateFocus) ? '未提供' : implode('；', $candidateFocus)),
            "面试轮次：第 {$round} 轮",
            "轮次目标：{$roundGoal}",
            "本轮考察维度：{$focusDimension}",
            '目标公司：'.($company !== '' ? $company : '未提供'),
            '简历标题：'.($resumeTitle !== '' ? $resumeTitle : '未提供'),
            '简历目标岗位：'.($resumeTargetJob !== '' ? $resumeTargetJob : '未提供'),
            '简历摘要：'.($resumeExcerpt !== '' ? $resumeExcerpt : '未提供'),
            '简历锚点：'.(empty($resumeAnchors) ? '未提供' : implode('；', $resumeAnchors)),
            '简历经验信号：'.(empty($resumeExperienceSignal) ? '未提供' : json_encode($resumeExperienceSignal, JSON_UNESCAPED_UNICODE)),
            '简历关注点：'.(empty($focusPoints) ? '未提供' : implode('；', $focusPoints)),
            'JD 摘要：'.($jobDescriptionExcerpt !== '' ? $jobDescriptionExcerpt : '未提供'),
            'JD 关键词：'.(empty($jobKeywords) ? '未提供' : implode('；', $jobKeywords)),
            '历史问题（禁止重复）：'.(empty($previousQuestions) ? '无' : implode('；', $previousQuestions)),
            '上一题回答摘要：'.($lastAnswerSummary !== '' ? $lastAnswerSummary : '无'),
            '请生成下一题。',
        ]);
    }

    public function evaluateAnswerSystemPrompt(): string
    {
        return <<<'PROMPT'
你是一个专业且严格的面试官。请对候选人的回答进行打分并提供反馈。
你还需要判断：该回答是否属于"无效信息"（如明显敷衍、重复"我不知道/不会"、没有任何可评估内容），若是可建议提前结束面试。

必须返回 JSON，不要 markdown，不要解释，格式如下：
{
  "score": 20,
  "feedback": {
    "comment": "一句话点评",
    "suggestion": "一句话改进建议"
  },
  "fluency": {
    "is_fluent": true,
    "severity": "none",
    "confidence": 0.0,
    "issues": []
  },
  "dialogue": {
    "action": "continue",
    "follow_up": "当 action=probe 时给一条追问，否则为空",
    "follow_ups": ["当 action=deep_probe 时给2条递进追问"],
    "coach_reply": "对候选人的一句简短引导回复",
    "confidence": 0.0
  },
  "termination": {
    "should_end": false,
    "reason": "should_end=true 时给出原因",
    "confidence": 0.0
  },
  "model_guidance": {
    "framework": "推荐回答框架（STAR/PREP/CARL）",
    "key_points": ["要点1","要点2"],
    "example_outline": "一句话示例骨架"
  }
}

约束：
- score 为 1-10 的整数；
- dialogue.action 只能是 continue / probe / deep_probe；
- model_guidance 必填：推荐框架 + 2-3 个要点 + 示例骨架；
- 应届生/转岗者：feedback.suggestion 必须用成长型思维语言，禁止消极评价；
- 应届生评分：有尝试意愿 4-6 分 / 结构化表达 7-8 分 / 量化案例 9-10 分。
PROMPT;
    }

    public function evaluateAnswerUserPrompt(string $question, string $answer): string
    {
        return "面试题：{$question}\n\n候选人回答：\n{$answer}";
    }
}
