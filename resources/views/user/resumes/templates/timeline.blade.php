@php
$modules = (isset($forceModules) && !empty($forceModules)) ? $forceModules : ($resume->modules->isNotEmpty() ? $resume->modules->toArray() : []);
$accent = match($theme ?? 'blue') {
    'coral' => '#ff6b6b',
    'green' => '#27ae60',
    default => '#2563eb',
};
$isPdfRender = ($exportMode ?? null) === 'pdf';
@endphp

@if(!empty($modules))
    <div class="resume-template-timeline" style="font-family: 'Instrument Sans', 'Segoe UI', system-ui, sans-serif; color: #1f2937; line-height: 1.7; max-width: 800px; margin: 0 auto; padding: 40px; background: #fff;">
        @foreach($modules as $mod)
            @if($mod['type'] === 'personal')
                <div style="text-align: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 3px solid {{ $accent }};">
                    @if(!empty($mod['data']['avatar']))
                        <div style="margin-bottom: 10px;">
                            <img src="{{ $mod['data']['avatar'] }}" alt="头像" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid {{ $accent }};">
                        </div>
                    @endif
                    <h1 style="margin: 0 0 10px 0; font-size: 28px; font-weight: 700; color: #1f2937;">{{ $mod['data']['name'] ?? $resume->title }}</h1>
                    <div style="font-size: 13px; color: #4b5563;{{ $isPdfRender ? '' : ' display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;' }}">
                        @if(!empty($mod['data']['phone']))<span>{{ $mod['data']['phone'] }}</span>@endif
                        @if(!empty($mod['data']['email']))<span style="{{ $isPdfRender ? 'margin-left: 12px;' : '' }}">{{ $mod['data']['email'] }}</span>@endif
                        @if(!empty($mod['data']['location']))<span style="{{ $isPdfRender ? 'margin-left: 12px;' : '' }}">{{ $mod['data']['location'] }}</span>@endif
                    </div>
                </div>
            @endif
        @endforeach

        <div style="position: relative; padding-left: 24px;">
            <div style="position: absolute; left: 6px; top: 0; bottom: 0; width: 2px; background: linear-gradient(to bottom, {{ $accent }}, {{ $accent }}66);"></div>
            @foreach($modules as $mod)
                @if(in_array($mod['type'], ['education','experience','project','certificate']))
                    <div style="position: relative; margin-bottom: 24px;">
                        <div style="position: absolute; left: -22px; top: 4px; width: 14px; height: 14px; border-radius: 50%; background: {{ $accent }}; border: 3px solid #fff; box-shadow: 0 0 0 2px {{ $accent }}33;"></div>
                        <div style="background: #f8fafc; border-radius: 8px; padding: 16px; border-left: 3px solid {{ $accent }};">
                            <h3 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 700; color: #1f2937;">{{ $mod['data']['title'] ?? '未命名模块' }}</h3>
                            @if(!empty($mod['data']['subtitle']) || !empty($mod['data']['date']) || !empty($mod['data']['location']))
                                <div style="font-size: 12.5px; color: #6b7280; margin-bottom: 8px;{{ $isPdfRender ? '' : ' display: flex; gap: 12px; flex-wrap: wrap;' }}">
                                    @if(!empty($mod['data']['subtitle']))<span style="font-weight: 600; color: #4b5563;">{{ $mod['data']['subtitle'] }}</span>@endif
                                    @if(!empty($mod['data']['date']))<span style="{{ $isPdfRender ? 'margin-left: 12px;' : '' }}">{{ $mod['data']['date'] }}</span>@endif
                                    @if(!empty($mod['data']['location']))<span style="{{ $isPdfRender ? 'margin-left: 12px;' : '' }}">{{ $mod['data']['location'] }}</span>@endif
                                </div>
                            @endif
                            @if(!empty(trim((string)($mod['data']['content'] ?? ''))))
                                <div style="font-size: 13px; color: #4b5563; margin-bottom: 8px; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                            @endif
                            @if(!empty($mod['data']['items']))
                                <ul style="margin: 0; padding-left: 18px; font-size: 13.5px;">
                                    @foreach($mod['data']['items'] as $item)
                                        @if(trim($item) !== '')<li style="margin-bottom: 5px;">{{ $item }}</li>@endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        @foreach($modules as $mod)
            @if(in_array($mod['type'], ['objective','skill','summary']))
                <div style="margin-bottom: 20px; padding: 16px; background: #f8fafc; border-radius: 8px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 700; color: #1f2937; border-bottom: 2px solid {{ $accent }}; padding-bottom: 6px; display: inline-block;">
                        @if($mod['type'] === 'objective')求职意向
                        @elseif($mod['type'] === 'skill'){{ $mod['data']['title'] ?? '技能证书' }}
                        @elseif($mod['type'] === 'summary'){{ $mod['data']['title'] ?? '自我评价' }}
                        @endif
                    </h3>
                    @if($mod['type'] === 'objective')
                        @if(!empty($mod['data']['target_job']))<div style="font-size: 14px; margin-bottom: 6px;"><strong>目标岗位：</strong>{{ $mod['data']['target_job'] }}</div>@endif
                        @if(!empty($mod['data']['content']))<div style="font-size: 13.5px; color: #4b5563; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>@endif
                    @elseif($mod['type'] === 'skill')
                        @if(!empty(array_filter($mod['data']['items'] ?? [], fn ($item) => trim((string) $item) !== '')))
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                @foreach($mod['data']['items'] ?? [] as $item)
                                    @if(trim($item) !== '')<span style="display: inline-block; padding: 4px 12px; background: {{ $accent }}15; color: {{ $accent }}; border-radius: 20px; font-size: 12px; font-weight: 500;">{{ $item }}</span>@endif
                                @endforeach
                            </div>
                        @elseif(!empty(trim((string)($mod['data']['content'] ?? ''))))
                            <div style="font-size: 13.5px; color: #4b5563; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                        @endif
                    @elseif($mod['type'] === 'summary')
                        <div style="font-size: 13.5px; color: #4b5563; white-space: pre-wrap;">{{ $mod['data']['content'] ?? '' }}</div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
@else
    @include('user.resumes.templates._parser')
    <div class="resume-template-classic" style="font-family: 'Instrument Sans', 'Segoe UI', system-ui, sans-serif; color: #1f2937; line-height: 1.7; max-width: 800px; margin: 0 auto; padding: 40px; background: #fff;">
        <div style="text-align: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 2px solid #2563eb;">
            <h1 style="margin: 0 0 8px 0; font-size: 28px; font-weight: 700; color: #111827; letter-spacing: 0.5px;">{{ $resume->title }}</h1>
            @if($resume->target_job)<div style="font-size: 15px; color: #2563eb; font-weight: 600;">{{ $resume->target_job }}</div>@endif
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
                    @if(!str_starts_with($paragraph, '- ') && !str_starts_with($paragraph, '* '))<p style="margin: 0 0 8px 0;">{{ $paragraph }}</p>@endif
                @endforeach
            </div>
        @endforeach
    </div>
@endif
