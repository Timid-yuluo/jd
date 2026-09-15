@php
$modules = (isset($forceModules) && !empty($forceModules)) ? $forceModules : ($resume->modules->isNotEmpty() ? $resume->modules->toArray() : []);
$accent = match($theme ?? 'blue') {
    'coral' => '#ff6b6b',
    'green' => '#27ae60',
    default => '#2563eb',
};
@endphp

@if(!empty($modules))
    <div class="resume-template-classic" style="font-family: 'Instrument Sans', 'Segoe UI', system-ui, sans-serif; color: #1f2937; line-height: 1.7; max-width: 800px; margin: 0 auto; padding: 40px; background: #fff;">
        @foreach($modules as $mod)
            @if($mod['type'] === 'personal')
                <div style="text-align: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 2px solid {{ $accent }};">
                    @if(!empty($mod['data']['avatar']))
                        <div style="margin-bottom: 10px;">
                            <img src="{{ $mod['data']['avatar'] }}" alt="头像" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid {{ $accent }};">
                        </div>
                    @endif
                    <h1 style="margin: 0 0 8px 0; font-size: 28px; font-weight: 700; color: #111827; letter-spacing: 0.5px;">{{ $mod['data']['name'] ?? $resume->title }}</h1>
                    @if(!empty($mod['data']['phone']) || !empty($mod['data']['email']) || !empty($mod['data']['location']))
                        <div style="font-size: 13px; color: #4b5563; display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
                            @if(!empty($mod['data']['phone']))<span>{{ $mod['data']['phone'] }}</span>@endif
                            @if(!empty($mod['data']['email']))<span>{{ $mod['data']['email'] }}</span>@endif
                            @if(!empty($mod['data']['location']))<span>{{ $mod['data']['location'] }}</span>@endif
                        </div>
                    @endif
                </div>
            @endif

            @if($mod['type'] === 'objective')
                <div style="margin-bottom: 24px;">
                    <h2 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 700; color: #1f2937; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px;">求职意向</h2>
                    @if(!empty($mod['data']['target_job']))<div style="font-size: 14px; margin-bottom: 6px;"><strong>目标岗位：</strong>{{ $mod['data']['target_job'] }}</div>@endif
                    @if(!empty($mod['data']['content']))<div style="font-size: 13.5px; color: #4b5563; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>@endif
                </div>
            @endif

            @if(in_array($mod['type'], ['education','experience','project','certificate']))
                <div style="margin-bottom: 24px;">
                    <h2 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 700; color: #1f2937; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px;">{{ $mod['data']['title'] ?? '未命名模块' }}</h2>
                    @if(!empty($mod['data']['subtitle']) || !empty($mod['data']['date']) || !empty($mod['data']['location']))
                        <div style="font-size: 13px; color: #6b7280; margin-bottom: 8px; display: flex; gap: 12px; flex-wrap: wrap;">
                            @if(!empty($mod['data']['subtitle']))<span style="font-weight: 600; color: #4b5563;">{{ $mod['data']['subtitle'] }}</span>@endif
                            @if(!empty($mod['data']['date']))<span>{{ $mod['data']['date'] }}</span>@endif
                            @if(!empty($mod['data']['location']))<span>{{ $mod['data']['location'] }}</span>@endif
                        </div>
                    @endif
                    {{-- 渲染 content 字段（与 visual-classic 模板一致） --}}
                    @if(!empty(trim((string)($mod['data']['content'] ?? ''))))
                        <div style="font-size: 13.5px; color: #374151; margin-bottom: 8px; white-space: pre-wrap; line-height: 1.75;">{{ $mod['data']['content'] }}</div>
                    @endif
                    @if(!empty($mod['data']['items']))
                        <ul style="margin: 0; padding-left: 20px;">
                            @foreach($mod['data']['items'] as $item)
                                @if(trim($item) !== '')<li style="margin-bottom: 6px;">{{ $item }}</li>@endif
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @if($mod['type'] === 'skill')
                <div style="margin-bottom: 24px;">
                    <h2 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 700; color: #1f2937; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px;">{{ $mod['data']['title'] ?? '技能证书' }}</h2>
                    {{-- 如果有 items，渲染为标签；否则渲染 content --}}
                    @if(!empty(array_filter($mod['data']['items'] ?? [], fn($i) => trim((string)$i) !== '')))
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            @foreach($mod['data']['items'] ?? [] as $item)
                                @if(trim($item) !== '')<span style="display: inline-block; padding: 3px 10px; background: #eff6ff; color: {{ $accent }}; border-radius: 4px; font-size: 12px;">{{ $item }}</span>@endif
                            @endforeach
                        </div>
                    @elseif(!empty(trim((string)($mod['data']['content'] ?? ''))))
                        <div style="font-size: 13.5px; color: #374151; white-space: pre-wrap; line-height: 1.75;">{{ $mod['data']['content'] }}</div>
                    @endif
                </div>
            @endif

            @if($mod['type'] === 'summary')
                <div style="margin-bottom: 24px;">
                    <h2 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 700; color: #1f2937; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px;">{{ $mod['data']['title'] ?? '自我评价' }}</h2>
                    <div style="font-size: 13.5px; color: #374151; white-space: pre-wrap;">{{ $mod['data']['content'] ?? '' }}</div>
                </div>
            @endif
        @endforeach

        @if(!empty($resume->highlights))
            <div style="margin-top: 28px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
                <h2 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 700; color: #1f2937;">核心亮点</h2>
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($resume->highlights as $highlight)
                        <li style="margin-bottom: 6px; color: #374151;">{{ $highlight }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@else
    @include('user.resumes.templates._parser')
    <div class="resume-template-classic" style="font-family: 'Instrument Sans', 'Segoe UI', system-ui, sans-serif; color: #1f2937; line-height: 1.7; max-width: 800px; margin: 0 auto; padding: 40px; background: #fff;">
        <div style="text-align: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 2px solid #2563eb;">
            <h1 style="margin: 0 0 8px 0; font-size: 28px; font-weight: 700; color: #111827; letter-spacing: 0.5px;">{{ $resume->title }}</h1>
            @if($resume->target_job)
                <div style="font-size: 15px; color: #2563eb; font-weight: 600;">{{ $resume->target_job }}</div>
            @endif
        </div>
        @foreach($resumeSections as $section)
            <div style="margin-bottom: 24px;">
                @if($section['title'])
                    @php
                        $titleSize = match($section['level']) { 2 => '18px', 3 => '16px', default => '14px' };
                        $titleColor = match($section['level']) { 2 => '#1f2937', 3 => '#374151', default => '#4b5563' };
                        $border = $section['level'] === 2 ? 'border-bottom: 1px solid #e5e7eb; padding-bottom: 6px;' : '';
                    @endphp
                    <h{{ $section['level'] }} style="margin: 0 0 10px 0; font-size: {{ $titleSize }}; font-weight: 700; color: {{ $titleColor }}; {{ $border }}">{{ $section['title'] }}</h{{ $section['level'] }}>
                @endif
                @if(!empty($section['items']))
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach($section['items'] as $item)<li style="margin-bottom: 6px;">{{ $item }}</li>@endforeach
                    </ul>
                @endif
                @php $paragraphs = array_filter(array_map('trim', explode("\n", trim($section['content'])))); @endphp
                @foreach($paragraphs as $paragraph)
                    @if(!str_starts_with($paragraph, '- ') && !str_starts_with($paragraph, '* '))
                        <p style="margin: 0 0 8px 0;">{{ $paragraph }}</p>
                    @endif
                @endforeach
            </div>
        @endforeach
        @if(!empty($resume->highlights))
            <div style="margin-top: 28px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
                <h2 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 700; color: #1f2937;">核心亮点</h2>
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($resume->highlights as $highlight)<li style="margin-bottom: 6px; color: #374151;">{{ $highlight }}</li>@endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
