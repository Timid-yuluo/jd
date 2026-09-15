<?php

declare(strict_types=1);

namespace App\Support;

/**
 * HTML 净化辅助类（#38）
 *
 * 提供用户输入内容的 XSS 过滤，剥离危险标签但保留基本格式
 * 不引入第三方 HTML Purifier，使用 PHP 原生函数
 */
final class HtmlPurifier
{
    /**
     * 危险标签黑名单（这些标签会被完全移除）
     */
    private const DANGEROUS_TAGS = [
        'script', 'iframe', 'object', 'embed', 'link', 'meta',
        'style', 'base', 'form', 'input', 'button', 'textarea',
        'svg', 'math',
    ];

    /**
     * 危险属性黑名单
     */
    private const DANGEROUS_ATTRS = [
        'onload', 'onerror', 'onclick', 'onmouseover', 'onmouseout',
        'onfocus', 'onblur', 'onchange', 'onsubmit', 'onreset',
        'javascript:', 'vbscript:', 'data:text/html',
    ];

    /**
     * 净化字符串：移除危险标签和属性，保留基本换行
     *
     * @param  string|null  $content  原始内容
     * @param  int  $maxLength  最大长度
     * @return string  净化后的内容
     */
    public static function purify(?string $content, int $maxLength = 5000): string
    {
        if (empty($content)) {
            return '';
        }

        // 截断超长内容
        $content = mb_substr($content, 0, $maxLength);

        // 1) 剥离危险标签（包含其内容）
        foreach (self::DANGEROUS_TAGS as $tag) {
            $content = preg_replace(
                '/<' . $tag . '[^>]*>.*?<\/' . $tag . '>/is',
                '',
                $content
            ) ?? '';
            $content = preg_replace(
                '/<' . $tag . '[^>]*\/?>/is',
                '',
                $content
            ) ?? '';
        }

        // 2) 移除危险属性
        foreach (self::DANGEROUS_ATTRS as $attr) {
            $content = preg_replace(
                '/\s' . preg_quote($attr, '/') . '[^>\s]*/i',
                '',
                $content
            ) ?? '';
        }

        // 3) 转换特殊字符为 HTML 实体（防止 XSS）
        $content = htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 4) 保留换行
        $content = nl2br($content);

        return trim($content);
    }

    /**
     * Alias for purify() for backwards compatibility
     */
    public static function clean(?string $content, int $maxLength = 5000): string
    {
        return self::purify($content, $maxLength);
    }

    /**
     * 净化纯文本（移除所有 HTML 标签，仅保留文本）
     *
     * @param  string|null  $content  原始内容
     * @param  int  $maxLength  最大长度
     * @return string  纯文本
     */
    public static function plaintext(?string $content, int $maxLength = 5000): string
    {
        if (empty($content)) {
            return '';
        }

        $content = mb_substr($content, 0, $maxLength);

        // 剥离所有 HTML 标签
        $content = strip_tags($content);

        // 转换特殊字符为实体
        $content = htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($content);
    }
}
