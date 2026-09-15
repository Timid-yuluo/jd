@php
/**
 * 将 markdown 风格的 content_raw 解析为结构化区块数组
 * 每个区块包含：level, title, content, items
 */
function parseResumeContent(string $text): array
{
    $lines = explode("\n", $text);
    $sections = [];
    $current = null;
    $buffer = [];

    $flush = function () use (&$sections, &$current, &$buffer): void {
        if ($current !== null) {
            $current['content'] = implode("\n", $buffer);
            $sections[] = $current;
        }
        $buffer = [];
    };

    foreach ($lines as $line) {
        $trimmed = rtrim($line);
        if (preg_match('/^(#{2,4})\s+(.+)$/', $trimmed, $matches)) {
            $flush();
            $level = strlen($matches[1]);
            $current = [
                'level' => $level,
                'title' => trim($matches[2]),
                'content' => '',
                'items' => [],
            ];
        } elseif ($current !== null) {
            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $itemMatches)) {
                $current['items'][] = $itemMatches[1];
            }
            $buffer[] = $line;
        } else {
            // 无标题前缀的顶部内容，视为 level 1 简介
            if (trim($line) !== '') {
                if (empty($sections)) {
                    $current = ['level' => 1, 'title' => '', 'content' => '', 'items' => []];
                }
                $buffer[] = $line;
            }
        }
    }
    $flush();

    return $sections;
}

$resumeSections = parseResumeContent((string) ($resume->content_raw ?? ''));
@endphp
