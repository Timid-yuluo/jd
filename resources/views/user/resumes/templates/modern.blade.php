@php
$modules = (isset($forceModules) && !empty($forceModules)) ? $forceModules : ($resume->modules->isNotEmpty() ? $resume->modules->toArray() : []);
$accent = match($theme ?? 'blue') {
    'coral' => '#1a1a1a',
    'green' => '#1b4d3e',
    default => '#1e3a5f',
};
$isPdfRender = ($exportMode ?? null) === 'pdf';
@endphp

@if(!empty($modules))
    @if($isPdfRender)
        <table class="resume-template-modern" style="width: 100%; border-collapse: collapse; font-family: 'Instrument Sans', 'Segoe UI', system-ui, sans-serif; color: #1f2937; line-height: 1.65; background: #fff;">
            <tr>
                <td style="width: 220px; background: {{ $accent }}; color: #fff; padding: 32px 20px; vertical-align: top;">
                    @foreach($modules as $mod)
                        @if($mod['type'] === 'personal')
                            <div style="margin-bottom: 24px;">
                                @if(!empty($mod['data']['avatar']))
                                    <div style="margin-bottom: 10px;">
                                        <img src="{{ $mod['data']['avatar'] }}" alt="头像" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.35);">
                                    </div>
                                @endif
                                <h1 style="margin: 0 0 8px 0; font-size: 20px; font-weight: 700; color: #fff;">{{ $mod['data']['name'] ?? '姓名' }}</h1>
                                <div style="font-size: 12px; color: #dbeafe; line-height: 1.8;">
                                    @if(!empty($mod['data']['phone']))<div>{{ $mod['data']['phone'] }}</div>@endif
                                    @if(!empty($mod['data']['email']))<div>{{ $mod['data']['email'] }}</div>@endif
                                    @if(!empty($mod['data']['location']))<div>{{ $mod['data']['location'] }}</div>@endif
                                </div>
                            </div>
                        @endif

                        @if($mod['type'] === 'skill')
                            <div style="margin-bottom: 24px;">
                                <div style="font-size: 11px; color: #93c5fd; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 10px; font-weight: 600;">{{ $mod['data']['title'] ?? '技能证书' }}</div>
                                @foreach($mod['data']['items'] ?? [] as $item)
                                    @if(trim($item) !== '')<div style="font-size: 11px; margin-bottom: 5px; padding: 4px 8px; background: rgba(255,255,255,0.12); border-radius: 3px;">{{ $item }}</div>@endif
                                @endforeach
                            </div>
                        @endif

                        @if($mod['type'] === 'summary')
                            <div style="margin-bottom: 24px;">
                                <div style="font-size: 11px; color: #93c5fd; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 10px; font-weight: 600;">{{ $mod['data']['title'] ?? '自我评价' }}</div>
                                <div style="font-size: 12px; color: #dbeafe; line-height: 1.6; white-space: pre-wrap;">{{ $mod['data']['content'] ?? '' }}</div>
                            </div>
                        @endif
                    @endforeach
                </td>
                <td style="padding: 32px 28px; background: #fff; vertical-align: top;">
                    @foreach($modules as $mod)
                        @if($mod['type'] === 'objective')
                            <div style="margin-bottom: 18px;">
                                <h3 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 700; color: {{ $accent }}; border-bottom: 2px solid {{ $accent }}; padding-bottom: 4px;">求职意向</h3>
                                @if(!empty($mod['data']['target_job']))<div style="font-size: 13px;"><strong>目标岗位：</strong>{{ $mod['data']['target_job'] }}</div>@endif
                                @if(!empty($mod['data']['content']))<div style="font-size: 13px; color: #4b5563; margin-top: 4px; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>@endif
                            </div>
                        @endif

                        @if(in_array($mod['type'], ['education','experience','project','certificate']))
                            <div style="margin-bottom: 18px;">
                                <h3 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 700; color: {{ $accent }}; border-bottom: 2px solid {{ $accent }}; padding-bottom: 4px;">{{ $mod['data']['title'] ?? '未命名' }}</h3>
                                @if(!empty($mod['data']['subtitle']) || !empty($mod['data']['date']) || !empty($mod['data']['location']))
                                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">
                                        @if(!empty($mod['data']['subtitle']))<span style="font-weight: 600; color: #4b5563;">{{ $mod['data']['subtitle'] }}</span>@endif
                                        @if(!empty($mod['data']['date']))<span style="margin-left: 10px;">{{ $mod['data']['date'] }}</span>@endif
                                        @if(!empty($mod['data']['location']))<span style="margin-left: 10px;">{{ $mod['data']['location'] }}</span>@endif
                                    </div>
                                @endif
                                @if(!empty(trim((string)($mod['data']['content'] ?? ''))))
                                    <div style="font-size: 13px; color: #4b5563; margin-bottom: 6px; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                                @endif
                                @if(!empty($mod['data']['items']))
                                    <ul style="margin: 6px 0 0 0; padding-left: 16px; font-size: 13px;">
                                        @foreach($mod['data']['items'] as $item)
                                            @if(trim($item) !== '')<li style="margin-bottom: 3px;">{{ $item }}</li>@endif
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </td>
            </tr>
        </table>
    @else
        <div class="resume-template-modern" style="font-family: 'Instrument Sans', 'Segoe UI', system-ui, sans-serif; color: #1f2937; line-height: 1.65; display: flex; min-height: 600px;">
            {{-- 左侧边栏 --}}
            <div style="width: 220px; background: {{ $accent }}; color: #fff; padding: 32px 20px; flex-shrink: 0;">
                @foreach($modules as $mod)
                    @if($mod['type'] === 'personal')
                        <div style="margin-bottom: 24px;">
                            @if(!empty($mod['data']['avatar']))
                                <div style="margin-bottom: 10px;">
                                    <img src="{{ $mod['data']['avatar'] }}" alt="头像" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.35);">
                                </div>
                            @endif
                            <h1 style="margin: 0 0 8px 0; font-size: 20px; font-weight: 700; color: #fff;">{{ $mod['data']['name'] ?? '姓名' }}</h1>
                            <div style="font-size: 12px; color: #93c5fd; line-height: 1.8;">
                                @if(!empty($mod['data']['phone']))<div>{{ $mod['data']['phone'] }}</div>@endif
                                @if(!empty($mod['data']['email']))<div>{{ $mod['data']['email'] }}</div>@endif
                                @if(!empty($mod['data']['location']))<div>{{ $mod['data']['location'] }}</div>@endif
                            </div>
                        </div>
                    @endif

                    @if($mod['type'] === 'skill')
                        <div style="margin-bottom: 24px;">
                            <div style="font-size: 11px; color: #93c5fd; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 10px; font-weight: 600;">{{ $mod['data']['title'] ?? '技能证书' }}</div>
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                @foreach($mod['data']['items'] ?? [] as $item)
                                    @if(trim($item) !== '')<span style="font-size: 11px; padding: 2px 8px; background: rgba(255,255,255,0.15); border-radius: 3px;">{{ $item }}</span>@endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($mod['type'] === 'summary')
                        <div style="margin-bottom: 24px;">
                            <div style="font-size: 11px; color: #93c5fd; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 10px; font-weight: 600;">{{ $mod['data']['title'] ?? '自我评价' }}</div>
                            <div style="font-size: 12px; color: #dbeafe; line-height: 1.6;">{{ $mod['data']['content'] ?? '' }}</div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- 右侧主内容 --}}
            <div style="flex: 1; padding: 32px 28px; background: #fff;">
                @foreach($modules as $mod)
                    @if($mod['type'] === 'objective')
                        <div style="margin-bottom: 18px;">
                            <h3 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 700; color: {{ $accent }}; border-bottom: 2px solid {{ $accent }}; padding-bottom: 4px;">求职意向</h3>
                            @if(!empty($mod['data']['target_job']))<div style="font-size: 13px;"><strong>目标岗位：</strong>{{ $mod['data']['target_job'] }}</div>@endif
                            @if(!empty($mod['data']['content']))<div style="font-size: 13px; color: #4b5563; margin-top: 4px; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>@endif
                        </div>
                    @endif

                    @if(in_array($mod['type'], ['education','experience','project','certificate']))
                        <div style="margin-bottom: 18px;">
                            <h3 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 700; color: {{ $accent }}; border-bottom: 2px solid {{ $accent }}; padding-bottom: 4px;">{{ $mod['data']['title'] ?? '未命名' }}</h3>
                            @if(!empty($mod['data']['subtitle']) || !empty($mod['data']['date']) || !empty($mod['data']['location']))
                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px; display: flex; gap: 10px; flex-wrap: wrap;">
                                    @if(!empty($mod['data']['subtitle']))<span style="font-weight: 600; color: #4b5563;">{{ $mod['data']['subtitle'] }}</span>@endif
                                    @if(!empty($mod['data']['date']))<span>{{ $mod['data']['date'] }}</span>@endif
                                    @if(!empty($mod['data']['location']))<span>{{ $mod['data']['location'] }}</span>@endif
                                </div>
                            @endif
                            @if(!empty(trim((string)($mod['data']['content'] ?? ''))))
                                <div style="font-size: 13px; color: #4b5563; margin-bottom: 6px; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                            @endif
                            @if(!empty($mod['data']['items']))
                                <ul style="margin: 6px 0 0 0; padding-left: 16px; font-size: 13px;">
                                    @foreach($mod['data']['items'] as $item)
                                        @if(trim($item) !== '')<li style="margin-bottom: 3px;">{{ $item }}</li>@endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
@else
    @include('user.resumes.templates._parser')
    <div class="resume-template-modern" style="font-family: 'Instrument Sans', 'Segoe UI', system-ui, sans-serif; color: #1f2937; line-height: 1.65; max-width: 900px; margin: 0 auto; background: #fff; display: flex; min-height: 600px;">
        <div style="width: 260px; background: #1e3a5f; color: #fff; padding: 36px 24px; flex-shrink: 0;">
            <div style="margin-bottom: 28px;">
                <h1 style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700; color: #fff; line-height: 1.3;">{{ $resume->title }}</h1>
                @if($resume->target_job)<div style="font-size: 13px; color: #93c5fd; font-weight: 500; text-transform: uppercase; letter-spacing: 0.8px;">{{ $resume->target_job }}</div>@endif
            </div>
            @if($resume->ats_score)
                <div style="margin-bottom: 24px; padding: 12px; background: rgba(255,255,255,0.1); border-radius: 6px;">
                    <div style="font-size: 11px; color: #93c5fd; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">ATS 评分</div>
                    <div style="font-size: 24px; font-weight: 700; color: #fff;">{{ $resume->ats_score }}<span style="font-size: 14px; color: #93c5fd;">/100</span></div>
                </div>
            @endif
            @if(!empty($resume->highlights))
                <div style="margin-bottom: 24px;">
                    <div style="font-size: 11px; color: #93c5fd; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 10px; font-weight: 600;">核心亮点</div>
                    <ul style="margin: 0; padding: 0; list-style: none;">
                        @foreach($resume->highlights as $highlight)
                            <li style="margin-bottom: 10px; font-size: 13px; line-height: 1.5; padding-left: 14px; position: relative;">
                                <span style="position: absolute; left: 0; top: 6px; width: 6px; height: 6px; background: #60a5fa; border-radius: 50%;"></span>{{ $highlight }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        <div style="flex: 1; padding: 36px 32px; background: #fff;">
            @foreach($resumeSections as $section)
                <div style="margin-bottom: 22px;">
                    @if($section['title'])
                        @php
                            $titleSize = match($section['level']) { 2 => '16px', 3 => '14px', default => '13px' };
                            $titleColor = match($section['level']) { 2 => '#1e3a5f', 3 => '#374151', default => '#4b5563' };
                            $border = $section['level'] === 2 ? 'border-bottom: 2px solid #1e3a5f; padding-bottom: 6px; margin-bottom: 12px;' : 'margin-bottom: 8px;';
                        @endphp
                        <h{{ $section['level'] }} style="margin: 0; font-size: {{ $titleSize }}; font-weight: 700; color: {{ $titleColor }}; {{ $border }}">{{ $section['title'] }}</h{{ $section['level'] }}>
                    @endif
                    @if(!empty($section['items']))
                        <ul style="margin: 8px 0 0 0; padding-left: 18px;">
                            @foreach($section['items'] as $item)<li style="margin-bottom: 5px; font-size: 14px;">{{ $item }}</li>@endforeach
                        </ul>
                    @endif
                    @php $paragraphs = array_filter(array_map('trim', explode("\n", trim($section['content'])))); @endphp
                    @foreach($paragraphs as $paragraph)
                        @if(!str_starts_with($paragraph, '- ') && !str_starts_with($paragraph, '* '))<p style="margin: 0 0 6px 0; font-size: 14px; color: #374151;">{{ $paragraph }}</p>@endif
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@endif
