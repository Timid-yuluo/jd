<?php

declare(strict_types=1);

namespace App\Services\Resume\Keyword;

final class KeywordSynonymDictionary
{
    /**
     * @return array<string,string>
     */
    public function map(): array
    {
        return [
            'laravel框架' => 'laravel',
            'laravel framework' => 'laravel',
            'php语言' => 'php',
            'mysql数据库' => 'mysql',
            'redis缓存' => 'redis',
            'docker容器' => 'docker',
            'k8s' => 'kubernetes',
            'ks8' => 'kubernetes',
            '微服务架构' => '微服务',
            '高并发系统' => '高并发',
            'restful' => 'rest api',
            'restful api' => 'rest api',
        ];
    }
}
