<?php

declare(strict_types=1);

namespace App\Services\Resume\Parsing;

final class ResumeSectionSplitter
{
    /**
     * @param  array<int,string>  $lines
     * @return array<int, array{type:string,title:string,lines:array<int,string>}>
     */
    public function split(array $lines): array
    {
        $sections = [];
        $current = null;
        $buffer = [];

        foreach ($lines as $line) {
            $sec = $this->matchHeader($line);
            if ($sec !== null) {
                if ($current !== null) {
                    $sections[] = array_merge($current, ['lines' => $buffer]);
                }
                $current = ['type' => $sec['type'], 'title' => $sec['title']];
                $buffer = [];
            } else {
                $buffer[] = $line;
            }
        }

        if ($current !== null) {
            $sections[] = array_merge($current, ['lines' => $buffer]);
        } elseif (! empty($buffer)) {
            $sections[] = ['type' => 'personal', 'title' => '个人信息', 'lines' => $buffer];
        }

        return $sections;
    }

    /**
     * @return array{type:string,title:string}|null
     */
    public function matchHeader(string $text): ?array
    {
        if ($text === '') {
            return null;
        }

        if (preg_match('/^#{1,4}\s+(.+)$/', $text, $m)) {
            $title = trim($m[1]);

            return ['title' => $title, 'type' => $this->guessType($title)];
        }

        $patterns = [
            '/^(个人信息|基本资料|个人概况|联系方式)/u' => 'personal',
            '/^(求职意向|职业目标|期望岗位|应聘岗位|意向岗位)/u' => 'objective',
            '/^(教育背景|教育经历|学历|学习经历|毕业院校)/u' => 'education',
            '/^(工作(经验|经历)|职业经历|实习(经验|经历)|职场经历)/u' => 'experience',
            '/^(项目(经验|经历))/u' => 'project',
            '/^(技能特长|专业技能|核心技能|职业技能|技术栈|技能)/u' => 'skill',
            '/^(证书|荣誉|获奖|资格证书|获奖情况)/u' => 'certificate',
            '/^(自我评价|个人评价|自我总结|个人介绍)/u' => 'summary',
        ];

        foreach ($patterns as $pattern => $type) {
            if (preg_match($pattern, $text)) {
                return ['title' => $text, 'type' => $type];
            }
        }

        return null;
    }

    public function guessType(string $title): string
    {
        return match (true) {
            str_contains($title, '教育') => 'education',
            str_contains($title, '工作') || str_contains($title, '经验') || str_contains($title, '实习') => 'experience',
            str_contains($title, '项目') => 'project',
            str_contains($title, '技能') || str_contains($title, '专业') || str_contains($title, '技术') => 'skill',
            str_contains($title, '证书') || str_contains($title, '荣誉') || str_contains($title, '获奖') => 'certificate',
            str_contains($title, '评价') || str_contains($title, '介绍') || str_contains($title, '总结') => 'summary',
            str_contains($title, '求职') || str_contains($title, '意向') || str_contains($title, '目标') => 'objective',
            str_contains($title, '个人') || str_contains($title, '联系') || str_contains($title, '基本') => 'personal',
            default => 'experience',
        };
    }
}
