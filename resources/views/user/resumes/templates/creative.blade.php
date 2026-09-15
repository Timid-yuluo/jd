@php
$modules = (isset($forceModules) && !empty($forceModules)) ? $forceModules : ($resume->modules->isNotEmpty() ? $resume->modules->toArray() : []);
$accent = match($theme ?? 'blue') {
    'coral' => '#ff6b6b',
    'green' => '#27ae60',
    default => '#6366f1',
};
$bg = match($theme ?? 'blue') {
    'coral' => '#fff0f0',
    'green' => '#e8f5e9',
    default => '#eef2ff',
};
$isPdfRender = ($exportMode ?? null) === 'pdf';
@endphp

@if(!empty($modules))
    <div class="resume-template-creative" style="font-family: 'Instrument Sans', 'Segoe UI', system-ui, sans-serif; color: #1f2937; line-height: 1.7; max-width: 800px; margin: 0 auto; padding: 30px; background: #fff;">
        @foreach($modules as $mod)
            @if($mod['type'] === 'personal')
                <div style="background: linear-gradient(135deg, {{ $accent }} 0%, {{ $accent }}dd 100%); color: #fff; border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 10px 40px {{ $accent }}33;">
                    <div style="{{ $isPdfRender ? '' : 'display: flex; align-items: center; gap: 20px; flex-wrap: wrap;' }}">
                        <div style="width: 72px; height: 72px; border-radius: 50%; background: rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700;">
                            @if(!empty($mod['data']['avatar']))
                                <img src="{{ $mod['data']['avatar'] }}" alt="头像" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            @else
                                {{ mb_substr($mod['data']['name'] ?? '姓', 0, 1) }}
                            @endif
                        </div>
                        <div style="{{ $isPdfRender ? 'margin-top: 14px;' : '' }}">
                            <h1 style="margin: 0 0 8px 0; font-size: 26px; font-weight: 700; color: #fff;">{{ $mod['data']['name'] ?? '姓名' }}</h1>
                            <div style="font-size: 14px; color: rgba(255,255,255,0.9);{{ $isPdfRender ? '' : ' display: flex; gap: 16px; flex-wrap: wrap;' }}">
                                @if(!empty($mod['data']['phone']))<span>{{ $mod['data']['phone'] }}</span>@endif
                                @if(!empty($mod['data']['email']))<span style="{{ $isPdfRender ? 'margin-left: 16px;' : '' }}">{{ $mod['data']['email'] }}</span>@endif
                                @if(!empty($mod['data']['location']))<span style="{{ $isPdfRender ? 'margin-left: 16px;' : '' }}">{{ $mod['data']['location'] }}</span>@endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach

        @if($isPdfRender)
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 16px;">
                @foreach($modules as $mod)
                    @if($mod['type'] === 'objective')
                        <tr>
                            <td colspan="2" style="background: {{ $bg }}; border-radius: 12px; padding: 20px; border: 1px solid {{ $accent }}22;">
                                <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 700; color: {{ $accent }};">求职意向</h3>
                                @if(!empty($mod['data']['target_job']))<div style="font-size: 14px; margin-bottom: 6px;"><strong>目标岗位：</strong>{{ $mod['data']['target_job'] }}</div>@endif
                                @if(!empty($mod['data']['content']))<div style="font-size: 13px; color: #4b5563; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>@endif
                            </td>
                        </tr>
                    @endif
                @endforeach
                @php
                    $cardModules = collect($modules)->filter(fn ($mod) => in_array($mod['type'], ['education', 'experience', 'project', 'certificate'], true))->values();
                @endphp
                @foreach($cardModules->chunk(2) as $chunk)
                    <tr>
                        @foreach($chunk as $mod)
                            <td style="width: 50%; vertical-align: top; {{ $loop->first ? 'padding-right: 8px;' : 'padding-left: 8px;' }}">
                                <div style="background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 700; color: {{ $accent }};">{{ $mod['data']['title'] ?? '未命名' }}</h3>
                                    @if(!empty($mod['data']['subtitle']) || !empty($mod['data']['date']) || !empty($mod['data']['location']))
                                        <div style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">
                                            @if(!empty($mod['data']['subtitle']))<span style="font-weight: 600; color: #4b5563;">{{ $mod['data']['subtitle'] }}</span>@endif
                                            @if(!empty($mod['data']['date']))<span style="margin-left: 10px;">{{ $mod['data']['date'] }}</span>@endif
                                            @if(!empty($mod['data']['location']))<span style="margin-left: 10px;">{{ $mod['data']['location'] }}</span>@endif
                                        </div>
                                    @endif
                                    @if(!empty(trim((string)($mod['data']['content'] ?? ''))))
                                        <div style="font-size: 13px; color: #4b5563; margin-bottom: 8px; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                                    @endif
                                    @if(!empty($mod['data']['items']))
                                        <ul style="margin: 0; padding-left: 16px; font-size: 13px;">
                                            @foreach($mod['data']['items'] as $item)
                                                @if(trim($item) !== '')<li style="margin-bottom: 4px; color: #374151;">{{ $item }}</li>@endif
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </td>
                        @endforeach
                        @if($chunk->count() === 1)
                            <td style="width: 50%;"></td>
                        @endif
                    </tr>
                @endforeach
                @foreach($modules as $mod)
                    @if($mod['type'] === 'skill')
                        <tr>
                            <td colspan="2" style="background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb;">
                                <h3 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: {{ $accent }};">{{ $mod['data']['title'] ?? '技能证书' }}</h3>
                                @if(!empty(array_filter($mod['data']['items'] ?? [], fn ($item) => trim((string) $item) !== '')))
                                    @foreach($mod['data']['items'] ?? [] as $item)
                                        @if(trim($item) !== '')<span style="display: inline-block; margin: 0 8px 8px 0; padding: 6px 14px; background: {{ $accent }}; color: #fff; border-radius: 20px; font-size: 12px; font-weight: 500;">{{ $item }}</span>@endif
                                    @endforeach
                                @elseif(!empty(trim((string)($mod['data']['content'] ?? ''))))
                                    <div style="font-size: 13px; color: #4b5563; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                                @endif
                            </td>
                        </tr>
                    @endif

                    @if($mod['type'] === 'summary')
                        <tr>
                            <td colspan="2" style="background: {{ $bg }}; border-radius: 12px; padding: 20px; border: 1px solid {{ $accent }}22;">
                                <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 700; color: {{ $accent }};">{{ $mod['data']['title'] ?? '自我评价' }}</h3>
                                <div style="font-size: 13px; color: #4b5563; white-space: pre-wrap;">{{ $mod['data']['content'] ?? '' }}</div>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </table>
        @else
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            @foreach($modules as $mod)
                @if($mod['type'] === 'objective')
                    <div style="grid-column: 1 / -1; background: {{ $bg }}; border-radius: 12px; padding: 20px; border: 1px solid {{ $accent }}22;">
                        <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 700; color: {{ $accent }};"><i class="ti ti-briefcase me-1"></i>求职意向</h3>
                        @if(!empty($mod['data']['target_job']))<div style="font-size: 14px; margin-bottom: 6px;"><strong>目标岗位：</strong>{{ $mod['data']['target_job'] }}</div>@endif
                        @if(!empty($mod['data']['content']))<div style="font-size: 13px; color: #4b5563; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>@endif
                    </div>
                @endif

                @if(in_array($mod['type'], ['education','experience','project','certificate']))
                    <div style="background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                        <h3 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 700; color: {{ $accent }};">
                            @if($mod['type'] === 'education')<i class="ti ti-school me-1"></i>
                            @elseif($mod['type'] === 'experience')<i class="ti ti-building me-1"></i>
                            @elseif($mod['type'] === 'project')<i class="ti ti-code me-1"></i>
                            @else<i class="ti ti-certificate me-1"></i>
                            @endif
                            {{ $mod['data']['title'] ?? '未命名' }}
                        </h3>
                        @if(!empty($mod['data']['subtitle']) || !empty($mod['data']['date']) || !empty($mod['data']['location']))
                            <div style="font-size: 12px; color: #6b7280; margin-bottom: 8px; display: flex; gap: 10px; flex-wrap: wrap;">
                                @if(!empty($mod['data']['subtitle']))<span style="font-weight: 600; color: #4b5563;">{{ $mod['data']['subtitle'] }}</span>@endif
                                @if(!empty($mod['data']['date']))<span>{{ $mod['data']['date'] }}</span>@endif
                                @if(!empty($mod['data']['location']))<span>{{ $mod['data']['location'] }}</span>@endif
                            </div>
                        @endif
                        @if(!empty(trim((string)($mod['data']['content'] ?? ''))))
                            <div style="font-size: 13px; color: #4b5563; margin-bottom: 8px; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                        @endif
                        @if(!empty($mod['data']['items']))
                            <ul style="margin: 0; padding-left: 16px; font-size: 13px;">
                                @foreach($mod['data']['items'] as $item)
                                    @if(trim($item) !== '')<li style="margin-bottom: 4px; color: #374151;">{{ $item }}</li>@endif
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                @if($mod['type'] === 'skill')
                    <div style="grid-column: 1 / -1; background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb;">
                        <h3 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: {{ $accent }};"><i class="ti ti-tools me-1"></i>{{ $mod['data']['title'] ?? '技能证书' }}</h3>
                        @if(!empty(array_filter($mod['data']['items'] ?? [], fn ($item) => trim((string) $item) !== '')))
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                @foreach($mod['data']['items'] ?? [] as $item)
                                    @if(trim($item) !== '')<span style="display: inline-block; padding: 6px 14px; background: {{ $accent }}; color: #fff; border-radius: 20px; font-size: 12px; font-weight: 500;">{{ $item }}</span>@endif
                                @endforeach
                            </div>
                        @elseif(!empty(trim((string)($mod['data']['content'] ?? ''))))
                            <div style="font-size: 13px; color: #4b5563; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                        @endif
                    </div>
                @endif

                @if($mod['type'] === 'summary')
                    <div style="grid-column: 1 / -1; background: {{ $bg }}; border-radius: 12px; padding: 20px; border: 1px solid {{ $accent }}22;">
                        <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 700; color: {{ $accent }};"><i class="ti ti-user-check me-1"></i>{{ $mod['data']['title'] ?? '自我评价' }}</h3>
                        <div style="font-size: 13px; color: #4b5563; white-space: pre-wrap;">{{ $mod['data']['content'] ?? '' }}</div>
                    </div>
                @endif
            @endforeach
        </div>
        @endif
    </div>
@else
    @include('user.resumes.templates._parser')
    <div class="resume-template-classic" style="font-family: 'Instrument Sans', 'Segoe UI', system-ui, sans-serif; color: #1f2937; line-height: 1.7; max-width: 800px; margin: 0 auto; padding: 40px; background: #fff;">
        <div style="text-align: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 2px solid #6366f1;">
            <h1 style="margin: 0 0 8px 0; font-size: 28px; font-weight: 700; color: #111827; letter-spacing: 0.5px;">{{ $resume->title }}</h1>
            @if($resume->target_job)<div style="font-size: 15px; color: #6366f1; font-weight: 600;">{{ $resume->target_job }}</div>@endif
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
