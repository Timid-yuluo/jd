@php
$modules = (isset($forceModules) && !empty($forceModules)) ? $forceModules : ($resume->modules->isNotEmpty() ? $resume->modules->toArray() : []);
$accent = match($theme ?? 'blue') {
    'coral' => '#c0392b',
    'green' => '#1e8449',
    default => '#2c3e50',
};
$light = match($theme ?? 'blue') {
    'coral' => '#fdf2f2',
    'green' => '#f0fdf4',
    default => '#f8fafc',
};
$isPdfRender = ($exportMode ?? null) === 'pdf';
@endphp

@if(!empty($modules))
    <div class="resume-template-elegant" style="font-family: 'Instrument Sans', 'Georgia', 'PingFang SC', serif; color: #2c3e50; line-height: 1.75; max-width: 800px; margin: 0 auto; padding: 40px 36px; background: #fff;">
        <div style="width: 60px; height: 4px; background: {{ $accent }}; margin-bottom: 24px;"></div>

        @foreach($modules as $mod)
            @if($mod['type'] === 'personal')
                <div style="margin-bottom: 32px;">
                    @if(!empty($mod['data']['avatar']))
                        <div style="margin-bottom: 10px;">
                            <img src="{{ $mod['data']['avatar'] }}" alt="头像" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid {{ $accent }};">
                        </div>
                    @endif
                    <h1 style="margin: 0 0 12px 0; font-size: 32px; font-weight: 400; color: {{ $accent }}; letter-spacing: 3px; text-transform: uppercase;">{{ $mod['data']['name'] ?? '姓名' }}</h1>
                    <div style="font-size: 13px; color: #7f8c8d; letter-spacing: 0.5px;{{ $isPdfRender ? '' : ' display: flex; gap: 24px; flex-wrap: wrap;' }}">
                        @if(!empty($mod['data']['phone']))<span>{{ $mod['data']['phone'] }}</span>@endif
                        @if(!empty($mod['data']['email']))<span style="{{ $isPdfRender ? 'margin-left: 16px;' : '' }}">{{ $mod['data']['email'] }}</span>@endif
                        @if(!empty($mod['data']['location']))<span style="{{ $isPdfRender ? 'margin-left: 16px;' : '' }}">{{ $mod['data']['location'] }}</span>@endif
                    </div>
                </div>
            @endif
        @endforeach

        @if($isPdfRender)
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 62%; vertical-align: top; padding-right: 18px;">
            @else
        <div style="display: flex; gap: 36px;">
            <div style="flex: 0 0 62%;">
            @endif
                @foreach($modules as $mod)
                    @if(in_array($mod['type'], ['experience','project']))
                        <div style="margin-bottom: 28px;">
                            <h3 style="margin: 0 0 14px 0; font-size: 12px; font-weight: 600; color: {{ $accent }}; letter-spacing: 2px; text-transform: uppercase;">{{ $mod['data']['title'] ?? '实习经历' }}</h3>
                            <div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 12px;"></div>
                            @if(!empty($mod['data']['subtitle']) || !empty($mod['data']['date']) || !empty($mod['data']['location']))
                                <div style="font-size: 12.5px; color: #7f8c8d; margin-bottom: 8px;{{ $isPdfRender ? '' : ' display: flex; gap: 12px; flex-wrap: wrap;' }}">
                                    @if(!empty($mod['data']['subtitle']))<span style="font-weight: 600; color: #34495e;">{{ $mod['data']['subtitle'] }}</span>@endif
                                    @if(!empty($mod['data']['date']))<span style="{{ $isPdfRender ? 'margin-left: 12px;' : '' }}">{{ $mod['data']['date'] }}</span>@endif
                                    @if(!empty($mod['data']['location']))<span style="{{ $isPdfRender ? 'margin-left: 12px;' : '' }}">{{ $mod['data']['location'] }}</span>@endif
                                </div>
                            @endif
                            @if(!empty(trim((string)($mod['data']['content'] ?? ''))))
                                <div style="font-size: 13px; color: #5d6d7e; line-height: 1.75; margin-bottom: 8px; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                            @endif
                            @if(!empty($mod['data']['items']))
                                @foreach($mod['data']['items'] as $item)
                                    @if(trim($item) !== '')
                                        <div style="margin-bottom: 10px; padding-left: 16px; position: relative; font-size: 13.5px; color: #34495e;">
                                            <span style="position: absolute; left: 0; top: 8px; width: 5px; height: 5px; background: {{ $accent }}; border-radius: 50%;"></span>{{ $item }}
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    @endif

                    @if($mod['type'] === 'objective')
                        <div style="margin-bottom: 28px;">
                            <h3 style="margin: 0 0 14px 0; font-size: 12px; font-weight: 600; color: {{ $accent }}; letter-spacing: 2px; text-transform: uppercase;">求职意向</h3>
                            <div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 12px;"></div>
                            @if(!empty($mod['data']['target_job']))<div style="font-size: 13.5px; margin-bottom: 6px; color: #34495e;"><strong>{{ $mod['data']['target_job'] }}</strong></div>@endif
                            @if(!empty($mod['data']['content']))<div style="font-size: 13px; color: #5d6d7e; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>@endif
                        </div>
                    @endif
                @endforeach
            @if($isPdfRender)
                </td>
                <td style="width: 38%; vertical-align: top; background: {{ $light }}; padding: 24px; border-radius: 4px;">
            @else
            </div>

            <div style="flex: 1; background: {{ $light }}; padding: 24px; border-radius: 4px;">
            @endif
                @foreach($modules as $mod)
                    @if($mod['type'] === 'education')
                        <div style="margin-bottom: 24px;">
                            <h3 style="margin: 0 0 10px 0; font-size: 12px; font-weight: 600; color: {{ $accent }}; letter-spacing: 2px; text-transform: uppercase;">{{ $mod['data']['title'] ?? '教育经历' }}</h3>
                            <div style="width: 30px; height: 2px; background: {{ $accent }}; margin-bottom: 10px;"></div>
                            @if(!empty($mod['data']['subtitle']) || !empty($mod['data']['date']) || !empty($mod['data']['location']))
                                <div style="font-size: 12.5px; color: #7f8c8d; margin-bottom: 8px;{{ $isPdfRender ? '' : ' display: flex; gap: 12px; flex-wrap: wrap;' }}">
                                    @if(!empty($mod['data']['subtitle']))<span style="font-weight: 600; color: #34495e;">{{ $mod['data']['subtitle'] }}</span>@endif
                                    @if(!empty($mod['data']['date']))<span style="{{ $isPdfRender ? 'margin-left: 12px;' : '' }}">{{ $mod['data']['date'] }}</span>@endif
                                    @if(!empty($mod['data']['location']))<span style="{{ $isPdfRender ? 'margin-left: 12px;' : '' }}">{{ $mod['data']['location'] }}</span>@endif
                                </div>
                            @endif
                            @if(!empty(trim((string)($mod['data']['content'] ?? ''))))
                                <div style="font-size: 13px; color: #5d6d7e; line-height: 1.75; margin-bottom: 8px; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                            @endif
                            @if(!empty($mod['data']['items']))
                                @foreach($mod['data']['items'] as $item)
                                    @if(trim($item) !== '')<div style="font-size: 13px; color: #34495e; margin-bottom: 6px;">{{ $item }}</div>@endif
                                @endforeach
                            @endif
                        </div>
                    @endif

                    @if($mod['type'] === 'skill')
                        <div style="margin-bottom: 24px;">
                            <h3 style="margin: 0 0 10px 0; font-size: 12px; font-weight: 600; color: {{ $accent }}; letter-spacing: 2px; text-transform: uppercase;">{{ $mod['data']['title'] ?? '技能证书' }}</h3>
                            <div style="width: 30px; height: 2px; background: {{ $accent }}; margin-bottom: 10px;"></div>
                            @if(!empty(array_filter($mod['data']['items'] ?? [], fn ($item) => trim((string) $item) !== '')))
                                @foreach($mod['data']['items'] ?? [] as $item)
                                    @if(trim($item) !== '')<div style="font-size: 13px; color: #34495e; margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px dotted #ddd;">{{ $item }}</div>@endif
                                @endforeach
                            @elseif(!empty(trim((string)($mod['data']['content'] ?? ''))))
                                <div style="font-size: 13px; color: #34495e; line-height: 1.75; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                            @endif
                        </div>
                    @endif

                    @if($mod['type'] === 'certificate')
                        <div style="margin-bottom: 24px;">
                            <h3 style="margin: 0 0 10px 0; font-size: 12px; font-weight: 600; color: {{ $accent }}; letter-spacing: 2px; text-transform: uppercase;">{{ $mod['data']['title'] ?? '获奖情况' }}</h3>
                            <div style="width: 30px; height: 2px; background: {{ $accent }}; margin-bottom: 10px;"></div>
                            @if(!empty($mod['data']['items']))
                                @foreach($mod['data']['items'] as $item)
                                    @if(trim($item) !== '')<div style="font-size: 12px; color: #5d6d7e; margin-bottom: 4px;">• {{ $item }}</div>@endif
                                @endforeach
                            @elseif(!empty(trim((string)($mod['data']['content'] ?? ''))))
                                <div style="font-size: 12.5px; color: #5d6d7e; line-height: 1.75; white-space: pre-wrap;">{{ $mod['data']['content'] }}</div>
                            @endif
                        </div>
                    @endif

                    @if($mod['type'] === 'summary')
                        <div style="margin-bottom: 24px;">
                            <h3 style="margin: 0 0 10px 0; font-size: 12px; font-weight: 600; color: {{ $accent }}; letter-spacing: 2px; text-transform: uppercase;">{{ $mod['data']['title'] ?? '自我评价' }}</h3>
                            <div style="width: 30px; height: 2px; background: {{ $accent }}; margin-bottom: 10px;"></div>
                            <div style="font-size: 12.5px; color: #5d6d7e; line-height: 1.8; white-space: pre-wrap;">{{ $mod['data']['content'] ?? '' }}</div>
                        </div>
                    @endif
                @endforeach
            @if($isPdfRender)
                </td>
            </tr>
        </table>
            @else
            </div>
        </div>
            @endif

        <div style="width: 60px; height: 4px; background: {{ $accent }}; margin-top: 32px;"></div>
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
    </div>
@endif
