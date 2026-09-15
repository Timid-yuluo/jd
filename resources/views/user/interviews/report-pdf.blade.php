<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>面试报告 - {{ $interview->position }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1a1a1a; line-height: 1.6; padding: 40px; max-width: 800px; margin: 0 auto; }
        h1 { font-size: 24px; margin-bottom: 8px; }
        h2 { font-size: 18px; margin: 24px 0 12px; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px; }
        h3 { font-size: 15px; margin: 16px 0 8px; }
        .meta { color: #6b7280; font-size: 14px; margin-bottom: 20px; }
        .score-box { display: inline-block; background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 12px; padding: 16px 24px; text-align: center; margin: 16px 0; }
        .score-box .number { font-size: 48px; font-weight: 700; color: #2563eb; }
        .score-box .label { font-size: 13px; color: #6b7280; }
        .dim-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 16px 0; }
        .dim-item { background: #f9fafb; border-radius: 8px; padding: 12px; }
        .dim-item .name { font-size: 13px; color: #6b7280; }
        .dim-item .val { font-size: 20px; font-weight: 600; }
        .comment { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 12px 0; border-radius: 0 8px 8px 0; font-size: 14px; }
        .question { margin: 12px 0; padding: 12px; background: #f9fafb; border-radius: 8px; }
        .question .q-label { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
        .question .q-text { font-size: 14px; margin-bottom: 8px; }
        .question .q-answer { font-size: 13px; color: #374151; white-space: pre-wrap; }
        .footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; text-align: center; }
        @media print { body { padding: 20px; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:20px;">
        <button onclick="window.print()" style="padding:8px 20px;background:#2563eb;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:14px;">打印 / 导出 PDF</button>
    </div>

    <h1>面试报告</h1>
    <div class="meta">
        岗位：{{ $interview->position }}
        @if($interview->company) · {{ $interview->company }} @endif
        | 简历：{{ $interview->resume?->title ?? '未选择' }}
        | 时间：{{ $interview->created_at?->format('Y-m-d H:i') }}
    </div>

    @php
        $dims = $report['dimension_scores'] ?? [];
        $dimLabels = [
            'professional_quality' => '专业素养',
            'skill_depth' => '技能深度',
            'communication' => '沟通表达',
            'cultural_fit' => '文化契合',
            'learning_potential' => '学习潜力',
        ];
    @endphp

    <div class="score-box">
        <div class="number">{{ $interview->overall_score ?? 0 }}</div>
        <div class="label">总分 / 100</div>
    </div>

    @if(!empty($dims))
    <h2>维度评分</h2>
    <div class="dim-grid">
        @foreach($dims as $key => $val)
            <div class="dim-item">
                <div class="name">{{ $dimLabels[$key] ?? $key }}</div>
                <div class="val">{{ $val }}</div>
            </div>
        @endforeach
    </div>
    @endif

    @if(!empty($report['overall_comment']))
    <h2>总体评价</h2>
    <div class="comment">{{ $report['overall_comment'] }}</div>
    @endif

    @if(!empty($report['strengths']))
    <h2>优势</h2>
    <ul>
        @foreach($report['strengths'] as $s)
            <li>{{ $s }}</li>
        @endforeach
    </ul>
    @endif

    @if(!empty($report['weaknesses']))
    <h2>不足</h2>
    <ul>
        @foreach($report['weaknesses'] as $w)
            <li>{{ $w }}</li>
        @endforeach
    </ul>
    @endif

    @if(!empty($report['suggestions']))
    <h2>改进建议</h2>
    <ul>
        @foreach($report['suggestions'] as $s)
            <li>{{ $s }}</li>
        @endforeach
    </ul>
    @endif

    {{-- 维度雷达图 SVG --}}
    @if(!empty($dims) && count($dims) >= 3)
    @php
        $dimKeys = array_keys($dims);
        $dimValues = array_values($dims);
        $dimCount = count($dimKeys);
        $cx = 150; $cy = 130; $r = 100;
        $points = [];
        $gridLevels = [0.25, 0.5, 0.75, 1.0];
        foreach ($dimKeys as $i => $key) {
            $angle = (2 * pi() * $i / $dimCount) - pi() / 2;
            $val = min(100, max(0, (float) ($dimValues[$i] ?? 0))) / 100;
            $points[] = [
                'x' => $cx + $r * $val * cos($angle),
                'y' => $cy + $r * $val * sin($angle),
                'lx' => $cx + ($r + 18) * cos($angle),
                'ly' => $cy + ($r + 18) * sin($angle),
                'label' => $dimLabels[$key] ?? $key,
                'val' => $dimValues[$i] ?? 0,
            ];
        }
    @endphp
    <h2>维度雷达图</h2>
    <div style="text-align:center;margin:12px 0;">
        <svg width="300" height="280" viewBox="0 0 300 280">
            {{-- 网格线 --}}
            @foreach ($gridLevels as $level)
                @php
                    $gp = [];
                    foreach ($dimKeys as $i => $key) {
                        $angle = (2 * pi() * $i / $dimCount) - pi() / 2;
                        $gp[] = ($cx + $r * $level * cos($angle)) . ',' . ($cy + $r * $level * sin($angle));
                    }
                @endphp
                <polygon points="{{ implode(' ', $gp) }}" fill="none" stroke="#e5e7eb" stroke-width="0.8"/>
            @endforeach
            {{-- 轴线 --}}
            @foreach ($dimKeys as $i => $key)
                @php
                    $angle = (2 * pi() * $i / $dimCount) - pi() / 2;
                    $ex = $cx + $r * cos($angle);
                    $ey = $cy + $r * sin($angle);
                @endphp
                <line x1="{{ $cx }}" y1="{{ $cy }}" x2="{{ $ex }}" y2="{{ $ey }}" stroke="#e5e7eb" stroke-width="0.5"/>
            @endforeach
            {{-- 数据区域 --}}
            <polygon points="{{ implode(' ', array_map(fn($p) => $p['x'].','.$p['y'], $points)) }}" fill="rgba(37,99,235,0.15)" stroke="#2563eb" stroke-width="2"/>
            {{-- 数据点和标签 --}}
            @foreach ($points as $p)
                <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="3.5" fill="#2563eb"/>
                <text x="{{ $p['lx'] }}" y="{{ $p['ly'] }}" text-anchor="middle" dominant-baseline="middle" font-size="11" fill="#374151">{{ $p['label'] }} {{ $p['val'] }}</text>
            @endforeach
        </svg>
    </div>
    @endif

    @php $questions = $interview->questions ?? collect(); @endphp
    @if($questions->isNotEmpty())
    <h2>问答记录</h2>
    @foreach($questions as $qi => $q)
        <div class="question">
            <div class="q-label">Q{{ $qi + 1 }}: {{ $q->category ?? '综合' }}</div>
            <div class="q-text">{{ $q->question }}</div>
            @if($q->answer)
                <div class="q-answer"><strong>回答：</strong>{{ Str::limit($q->answer, 500) }}</div>
            @endif
        </div>
    @endforeach
    @endif

    <div class="footer">
        由 {{ config('app.name') }} 生成 · {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
