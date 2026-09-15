@extends('layouts.user')

@section('title', '版本对比 - ' . $resume->title)

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <a href="{{ route('user.resumes.versions', $resume) }}" class="btn btn-link text-secondary p-0"><i class="ti ti-arrow-left me-1"></i>返回版本历史</a>
                <h2 class="page-title">版本对比</h2>
                <div class="text-secondary">{{ $resume->title }}</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        {{-- 版本选择器 --}}
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('user.resumes.versions.compare', $resume) }}" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">旧版本（左侧）</label>
                        <select name="a" class="form-select" required>
                            @foreach($allVersions as $v)
                                <option value="{{ $v->id }}" {{ $versionA && $versionA->id === $v->id ? 'selected' : '' }}>
                                    #{{ $v->id }} - {{ $v->created_at->format('m-d H:i') }} {{ $v->label ? "· {$v->label}" : '' }} {{ $v->source === 'manual' ? '📌' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">新版本（右侧）</label>
                        <select name="b" class="form-select" required>
                            @foreach($allVersions as $v)
                                <option value="{{ $v->id }}" {{ $versionB && $versionB->id === $v->id ? 'selected' : '' }}>
                                    #{{ $v->id }} - {{ $v->created_at->format('m-d H:i') }} {{ $v->label ? "· {$v->label}" : '' }} {{ $v->source === 'manual' ? '📌' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="ti ti-arrows-exchange me-1"></i>对比</button>
                    </div>
                </form>
            </div>
        </div>

        @if(!$versionA || !$versionB)
            <div class="empty">
                <p class="empty-title">请选择两个版本进行对比</p>
                <p class="empty-subtitle text-secondary">至少需要两个版本才能进行对比。</p>
            </div>
        @elseif($diff)
            {{-- 变更统计概览 --}}
            @if($diffStats)
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card version-diff-card version-diff-old">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary">旧版本</span>
                                <span class="text-secondary small">{{ $versionA->created_at->format('Y-m-d H:i:s') }}</span>
                                @if($versionA->label)<span class="badge bg-light text-dark">{{ $versionA->label }}</span>@endif
                                @if($versionA->source === 'manual')<span class="badge bg-info-lt">📌 手动保存</span>@endif
                                <span class="badge bg-light text-secondary">{{ $versionA->module_count ?? 0 }} 个模块</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card version-diff-card version-diff-new">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary">新版本</span>
                                <span class="text-secondary small">{{ $versionB->created_at->format('Y-m-d H:i:s') }}</span>
                                @if($versionB->label)<span class="badge bg-light text-dark">{{ $versionB->label }}</span>@endif
                                @if($versionB->source === 'manual')<span class="badge bg-info-lt">📌 手动保存</span>@endif
                                <span class="badge bg-light text-secondary">{{ $versionB->module_count ?? 0 }} 个模块</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 变更统计卡片 --}}
            <div class="row g-3 mb-4">
                <div class="col-6 col-sm-3">
                    <div class="card">
                        <div class="card-body p-3 text-center">
                            <div class="text-h2 mb-0 text-success">{{ $diffStats['added'] }}</div>
                            <div class="text-secondary small">新增模块</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="card">
                        <div class="card-body p-3 text-center">
                            <div class="text-h2 mb-0 text-danger">{{ $diffStats['removed'] }}</div>
                            <div class="text-secondary small">删除模块</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="card">
                        <div class="card-body p-3 text-center">
                            <div class="text-h2 mb-0 text-warning">{{ $diffStats['changed'] }}</div>
                            <div class="text-secondary small">修改模块</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="card">
                        <div class="card-body p-3 text-center">
                            <div class="text-h2 mb-0 {{ $diffStats['total_changes'] > 0 ? 'text-primary' : 'text-secondary' }}">{{ $diffStats['total_changes'] }}</div>
                            <div class="text-secondary small">变更总数</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- 元信息变更 --}}
            @if(!empty($diff['meta']))
                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title"><i class="ti ti-file-info me-1"></i>基本信息变更</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th style="width:140px;">字段</th>
                                    <th>旧版本</th>
                                    <th>新版本</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($diff['meta'] as $item)
                                    <tr>
                                        <td class="fw-medium">{{ $item['field'] === 'title' ? '简历标题' : '目标岗位' }}</td>
                                        <td class="diff-old">{!! highlightDiff($item['old'] ?? '', $item['new'] ?? '', false) !!}</td>
                                        <td class="diff-new">{!! highlightDiff($item['new'] ?? '', $item['old'] ?? '', true) !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- 模块变更 --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-layout-list me-1"></i>模块内容变更</h3>
                </div>
                <div class="card-body">
                    @if(empty($diff['modules']))
                        <div class="text-secondary text-center py-4">两个版本无模块差异</div>
                    @else
                        <div class="row g-3">
                            @foreach($diff['modules'] as $mod)
                                <div class="col-12">
                                    <div class="version-module-diff {{ $mod['status'] }}">
                                        <div class="version-module-header d-flex align-items-center gap-2">
                                            @if($mod['status'] === 'added')
                                                <span class="badge bg-success-lt"><i class="ti ti-plus me-1"></i>新增</span>
                                            @elseif($mod['status'] === 'removed')
                                                <span class="badge bg-danger-lt"><i class="ti ti-minus me-1"></i>删除</span>
                                            @elseif($mod['status'] === 'changed')
                                                <span class="badge bg-warning-lt"><i class="ti ti-pencil me-1"></i>修改</span>
                                            @else
                                                <span class="badge bg-light text-dark"><i class="ti ti-check me-1"></i>未变</span>
                                            @endif
                                            <span class="fw-semibold">{{ $mod['label'] }}</span>
                                        </div>

                                        @if($mod['status'] !== 'unchanged')
                                            <div class="row g-2 mt-1">
                                                @if($mod['old'])
                                                    <div class="col-md-6">
                                                        <div class="version-module-content version-module-old">
                                                            <div class="small text-secondary mb-1">旧版本</div>
                                                            <div class="small">{!! \App\Helpers\ModuleDataFormatter::toHtml($mod['old'] ?? null) !!}</div>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($mod['new'])
                                                    <div class="col-md-6">
                                                        <div class="version-module-content version-module-new">
                                                            <div class="small text-secondary mb-1">新版本</div>
                                                            <div class="small">{!! \App\Helpers\ModuleDataFormatter::toHtml($mod['new'] ?? null) !!}</div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@php
// 文字级差异高亮辅助函数 — 基于 LCS 的字符级 diff
if (!function_exists('highlightDiff')) {
    function highlightDiff(string $text, string $compareWith, bool $isNew): string {
        if ($text === $compareWith) {
            return e($text) ?: '<span class="text-secondary">（空）</span>';
        }
        if ($text === '') {
            return '<span class="text-secondary">（空）</span>';
        }

        // 短文本（<=200字符）使用字符级 diff，长文本回退到整段标记
        if (mb_strlen($text) > 200 || mb_strlen($compareWith) > 200) {
            $class = $isNew ? 'diff-highlight-add' : 'diff-highlight-del';
            return '<span class="' . $class . '">' . e($text) . '</span>';
        }

        // 将文本拆分为字符序列（支持中文）
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $otherChars = preg_split('//u', $compareWith, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // 计算 LCS（最长公共子序列）DP 表
        $m = count($chars);
        $n = count($otherChars);
        $dp = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));

        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                if ($chars[$i - 1] === $otherChars[$j - 1]) {
                    $dp[$i][$j] = $dp[$i - 1][$j - 1] + 1;
                } else {
                    $dp[$i][$j] = max($dp[$i - 1][$j], $dp[$i][$j - 1]);
                }
            }
        }

        // 回溯生成 diff 序列
        // 对于 $isNew=true：标记新增字符为高亮，公共字符为普通
        // 对于 $isNew=false：标记删除字符为高亮，公共字符为普通
        $result = [];
        $i = $m;
        $j = $n;
        $segments = []; // [type => 'common'|'changed', text => string]

        while ($i > 0 && $j > 0) {
            if ($chars[$i - 1] === $otherChars[$j - 1]) {
                array_unshift($segments, ['type' => 'common', 'text' => $chars[$i - 1]]);
                $i--;
                $j--;
            } elseif ($dp[$i - 1][$j] >= $dp[$i][$j - 1]) {
                // 当前文本独有的字符
                array_unshift($segments, ['type' => 'changed', 'text' => $chars[$i - 1]]);
                $i--;
            } else {
                $j--;
            }
        }
        // 剩余的当前文本字符
        while ($i > 0) {
            array_unshift($segments, ['type' => 'changed', 'text' => $chars[$i - 1]]);
            $i--;
        }

        // 合并连续的同类型片段并输出 HTML
        $html = '';
        $buffer = '';
        $bufferType = null;
        $class = $isNew ? 'diff-highlight-add' : 'diff-highlight-del';

        foreach ($segments as $seg) {
            if ($bufferType === null) {
                $bufferType = $seg['type'];
                $buffer = $seg['text'];
            } elseif ($bufferType === $seg['type']) {
                $buffer .= $seg['text'];
            } else {
                // 输出缓冲区
                if ($bufferType === 'common') {
                    $html .= e($buffer);
                } else {
                    $html .= '<span class="' . $class . '">' . e($buffer) . '</span>';
                }
                $bufferType = $seg['type'];
                $buffer = $seg['text'];
            }
        }
        // 输出最后一段
        if ($buffer !== '') {
            if ($bufferType === 'common') {
                $html .= e($buffer);
            } else {
                $html .= '<span class="' . $class . '">' . e($buffer) . '</span>';
            }
        }

        return $html ?: '<span class="text-secondary">（空）</span>';
    }
}
@endphp

<style>
.version-diff-old { border-left: 3px solid #6c757d; }
.version-diff-new { border-left: 3px solid #206bc4; }

.version-module-diff {
    border: 1px solid #e9eef5;
    border-radius: 8px;
    padding: .75rem 1rem;
    transition: background-color .15s;
}
.version-module-diff.added { background: #f0fdf4; border-color: #b2f5b2; }
.version-module-diff.removed { background: #fef2f2; border-color: #f5b2b2; }
.version-module-diff.changed { background: #fffbeb; border-color: #fde68a; }

.version-module-content {
    background: #fff;
    border: 1px solid #e9eef5;
    border-radius: 6px;
    padding: .5rem .75rem;
    max-height: 300px;
    overflow-y: auto;
}
.version-module-old { border-left: 3px solid #6c757d; }
.version-module-new { border-left: 3px solid #206bc4; }

.diff-old { background: #fef2f2; }
.diff-new { background: #f0fdf4; }

.diff-highlight-add { background: #bbf7d0; border-radius: 2px; padding: 0 2px; }
.diff-highlight-del { background: #fecaca; border-radius: 2px; padding: 0 2px; }
</style>
@endsection
