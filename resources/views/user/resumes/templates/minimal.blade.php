@php
$modules = (isset($forceModules) && !empty($forceModules)) ? $forceModules : ($resume->modules->isNotEmpty() ? $resume->modules->toArray() : []);
@endphp

@if(!empty($modules))
    <div class="resume-template-minimal" style="font-family: 'Instrument Sans', 'Georgia', 'PingFang SC', serif; color: #2d2d2d; line-height: 1.8; padding: 48px 40px; background: #fff;">
        @foreach($modules as $mod)
            @if($mod['type'] === 'personal')
                <div style="text-align: center; margin-bottom: 36px;">
                    @if(!empty($mod['data']['avatar']))
                        <div style="margin-bottom: 10px;">
                            <img src="{{ $mod['data']['avatar'] }}" alt="头像" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;">
                        </div>
                    @endif
                    <h1 style="margin: 0 0 10px 0; font-size: 26px; font-weight: 400; color: #1a1a1a; letter-spacing: 2px; text-transform: uppercase;">{{ $mod['data']['name'] ?? '姓名' }}</h1>
                    <div style="font-size: 13px; color: #666; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                        @if(!empty($mod['data']['phone']))<span>{{ $mod['data']['phone'] }}</span>@endif
                        @if(!empty($mod['data']['email']))<span>{{ $mod['data']['email'] }}</span>@endif
                        @if(!empty($mod['data']['location']))<span>{{ $mod['data']['location'] }}</span>@endif
                    </div>
                    <div style="width: 40px; height: 1px; background: #ccc; margin: 16px auto 0;"></div>
                </div>
            @endif

            @if($mod['type'] === 'objective')
                <div style="margin-bottom: 28px;">
                    <h2 style="margin: 0 0 10px 0; font-size: 12px; font-weight: 600; color: #1a1a1a; letter-spacing: 1.5px; text-transform: uppercase;">{{ !empty($mod['data']['target_job']) ? '求职意向：' . $mod['data']['target_job'] : '求职意向' }}</h2>
                    <div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 10px;"></div>
                    @if(!empty($mod['data']['content']))<div style="font-size: 13.5px; color: #444; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>@endif
                </div>
            @endif

            @if(in_array($mod['type'], ['education','experience','project','certificate']))
                <div style="margin-bottom: 28px;">
                    <h2 style="margin: 0 0 10px 0; font-size: 12px; font-weight: 600; color: #1a1a1a; letter-spacing: 1.5px; text-transform: uppercase;">{{ $mod['data']['title'] ?? '未命名' }}</h2>
                    <div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 10px;"></div>
                    @if(!empty($mod['data']['subtitle']) || !empty($mod['data']['date']) || !empty($mod['data']['location']))
                        <div style="font-size: 12.5px; color: #888; margin-bottom: 8px; display: flex; gap: 12px; flex-wrap: wrap;">
                            @if(!empty($mod['data']['subtitle']))<span style="font-weight: 600; color: #555;">{{ $mod['data']['subtitle'] }}</span>@endif
                            @if(!empty($mod['data']['date']))<span>{{ $mod['data']['date'] }}</span>@endif
                            @if(!empty($mod['data']['location']))<span>{{ $mod['data']['location'] }}</span>@endif
                        </div>
                    @endif
                    @if(!empty(trim((string)($mod['data']['content'] ?? ''))))
                        <div style="font-size: 13.5px; color: #444; margin-bottom: 8px; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                    @endif
                    @if(!empty($mod['data']['items']))
                        <ul style="margin: 0; padding-left: 18px; color: #444;">
                            @foreach($mod['data']['items'] as $item)
                                @if(trim($item) !== '')<li style="margin-bottom: 5px; font-size: 13.5px;">{{ $item }}</li>@endif
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @if($mod['type'] === 'skill')
                <div style="margin-bottom: 28px;">
                    <h2 style="margin: 0 0 10px 0; font-size: 12px; font-weight: 600; color: #1a1a1a; letter-spacing: 1.5px; text-transform: uppercase;">{{ $mod['data']['title'] ?? '技能证书' }}</h2>
                    <div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 10px;"></div>
                    @if(!empty(array_filter($mod['data']['items'] ?? [], fn ($item) => trim((string) $item) !== '')))
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            @foreach($mod['data']['items'] ?? [] as $item)
                                @if(trim($item) !== '')<span style="font-size: 12px; color: #555;">· {{ $item }}</span>@endif
                            @endforeach
                        </div>
                    @elseif(!empty(trim((string)($mod['data']['content'] ?? ''))))
                        <div style="font-size: 13.5px; color: #444; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                    @endif
                </div>
            @endif

            @if($mod['type'] === 'summary')
                <div style="margin-bottom: 28px;">
                    <h2 style="margin: 0 0 10px 0; font-size: 12px; font-weight: 600; color: #1a1a1a; letter-spacing: 1.5px; text-transform: uppercase;">{{ $mod['data']['title'] ?? '自我评价' }}</h2>
                    <div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 10px;"></div>
                    <div style="font-size: 13.5px; color: #444; white-space: pre-wrap;">{{ $mod['data']['content'] ?? '' }}</div>
                </div>
            @endif
        @endforeach
    </div>
@else
    @include('user.resumes.templates._parser')
    <div class="resume-template-minimal" style="font-family: 'Instrument Sans', 'Georgia', 'PingFang SC', serif; color: #2d2d2d; line-height: 1.8; max-width: 720px; margin: 0 auto; padding: 48px 40px; background: #fff;">
        <div style="text-align: center; margin-bottom: 36px;">
            <h1 style="margin: 0 0 10px 0; font-size: 26px; font-weight: 400; color: #1a1a1a; letter-spacing: 2px; text-transform: uppercase;">{{ $resume->title }}</h1>
            @if($resume->target_job)<div style="font-size: 14px; color: #666; font-weight: 400; letter-spacing: 1px;">{{ $resume->target_job }}</div>@endif
            <div style="width: 40px; height: 1px; background: #ccc; margin: 16px auto 0;"></div>
        </div>
        @foreach($resumeSections as $section)
            <div style="margin-bottom: 28px;">
                @if($section['title'])
                    @php
                        $titleSize = match($section['level']) { 2 => '13px', 3 => '13px', default => '12px' };
                        $titleWeight = $section['level'] === 2 ? '600' : '500';
                        $spacing = $section['level'] === 2 ? 'letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 14px;' : 'margin-bottom: 10px;';
                    @endphp
                    <h{{ $section['level'] }} style="margin: 0; font-size: {{ $titleSize }}; font-weight: {{ $titleWeight }}; color: #1a1a1a; {{ $spacing }}">{{ $section['title'] }}</h{{ $section['level'] }}>
                    @if($section['level'] === 2)<div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 14px;"></div>@endif
                @endif
                @if(!empty($section['items']))
                    <ul style="margin: 0; padding-left: 18px; color: #444;">
                        @foreach($section['items'] as $item)<li style="margin-bottom: 6px; font-size: 14px;">{{ $item }}</li>@endforeach
                    </ul>
                @endif
                @php $paragraphs = array_filter(array_map('trim', explode("\n", trim($section['content'])))); @endphp
                @foreach($paragraphs as $paragraph)
                    @if(!str_starts_with($paragraph, '- ') && !str_starts_with($paragraph, '* '))<p style="margin: 0 0 8px 0; font-size: 14px; color: #444;">{{ $paragraph }}</p>@endif
                @endforeach
            </div>
        @endforeach
        @if(!empty($resume->highlights))
            <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e5e5;">
                <h2 style="margin: 0 0 14px 0; font-size: 13px; font-weight: 600; color: #1a1a1a; letter-spacing: 1.5px; text-transform: uppercase;">核心亮点</h2>
                <div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 14px;"></div>
                <ul style="margin: 0; padding-left: 18px; color: #444;">
                    @foreach($resume->highlights as $highlight)<li style="margin-bottom: 6px; font-size: 14px;">{{ $highlight }}</li>@endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
