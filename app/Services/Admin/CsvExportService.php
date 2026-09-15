<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvExportService
{
    /**
     * 流式导出 CSV 文件。
     *
     * @param  string  $filename  下载文件名（不含扩展名前缀）
     * @param  array<int, string>  $headers  CSV 列标题
     * @param  \Generator<int, array<int, mixed>>  $rows  数据行生成器
     */
    public function stream(string $filename, array $headers, \Generator $rows): StreamedResponse
    {
        return new StreamedResponse(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
