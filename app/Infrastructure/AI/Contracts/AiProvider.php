<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Contracts;

interface AiProvider
{
    /**
     * @return array{optimized_text:string,highlights:array<int,string>}
     */
    public function optimizeResume(string $content, string $targetJob, array $options = []): array;

    /**
     * @return array{score:int,suggestions:array<int,string>,breakdown?:array<string,array<string,mixed>>}
     */
    public function scoreResume(string $content, string $targetJob = ''): array;

    /**
     * @return array{question:string}
     */
    public function generateInterviewQuestion(string $position, int $round, array $context = []): array;

    /**
     * @return array{
     *   score:int,
     *   feedback:array<string,string>,
     *   termination?:array{should_end:bool,reason:string,confidence:float},
     *   fluency?:array{is_fluent:bool,severity:string,confidence:float,issues:array<int,string>},
     *   dialogue?:array{
     *     action:string,
     *     follow_up:string,
     *     follow_ups?:array<int,string>,
     *     coach_reply:string,
     *     confidence:float
     *   }
     * }
     */
    public function evaluateInterviewAnswer(string $question, string $answer): array;

    /**
     * @return \Generator<int,string>
     */
    public function optimizeResumeStream(string $content, string $targetJob, array $options = []): \Generator;

    /**
     * 优化简历中的某一段内容
     *
     * @return array{optimized_text:string,suggestions:array<int,string>,score_before:int,score_after:int}
     */
    public function optimizeSection(string $sectionType, string $content, string $targetJob = ''): array;

    /**
     * 根据简单描述生成完整的简历段落
     *
     * @return array{generated_text:string,suggestions:array<int,string>}
     */
    public function generateSection(string $sectionType, string $brief, string $targetJob = ''): array;

    /**
     * 使用 LLM 将简历原文解析为结构化模块。
     *
     * @return array{
     *   modules:array<int,array{type:string,data:array<string,mixed>,sort_order?:int}>,
     *   target_job?:string,
     *   confidence?:float
     * }
     */
    public function extractResumeStructured(string $content): array;

    /**
     * 通用对话接口
     *
     * @param  array<int,array{role:string,content:string}>  $messages
     * @param  array<string,mixed>  $options
     * @return array<string,mixed>
     */
    public function chat(array $messages, array $options = []): array;
}
