    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                {{-- 加载上一份简历 --}}
                @if(isset($latestResume) && $latestResume)
                <div class="mb-3">
                    <div class="border rounded p-3" style="background:#f8fafc;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="small fw-medium">上一份简历</div>
                            <span class="badge bg-secondary-lt" style="font-size:.65rem;">{{ $latestResume->updated_at?->diffForHumans() }}</span>
                        </div>
                        <div class="small text-secondary mb-2">{{ $latestResume->title }} · {{ $latestResume->modules->count() }} 个模块</div>
                        <button type="button" class="btn btn-sm btn-outline-primary w-100" id="btnLoadLatest" data-action="load-latest">
                            <i class="ti ti-copy me-1"></i>重新加载到当前表单
                        </button>
                    </div>
                </div>
                <script type="application/json" id="latestResumeData">{!! json_encode(array_merge($latestResume->only(['title','target_job','target_company','template']), ['modules' => $latestResume->modules->map(fn($m) => ['type'=>$m->type,'data'=>$m->data,'sort_order'=>$m->sort_order])->values()]), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!}</script>
                @endif

                {{-- 赛道选择 --}}
                @php
                    $createTracks = \App\Models\CareerTrack::active()->ordered()->get()->groupBy('category');
                    $createCategoryLabels = \App\Models\CareerTrack::categoryLabels();
                @endphp
                <div class="mb-3">
                    <div style="font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 8px;">
                        <i class="ti ti-compass me-1"></i>求职赛道（可选）
                    </div>
                    <select name="career_track_id" class="form-select form-select-sm" onchange="document.getElementById('hidden-career-track-id').value=this.value">
                        <option value="">不选择，使用通用策略</option>
                        @foreach($createTracks as $category => $group)
                            <optgroup label="{{ $createCategoryLabels[$category] ?? $category }}">
                                @foreach($group as $track)
                                    <option value="{{ $track->id }}">{{ $track->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div class="form-hint" style="font-size:11px;">选择赛道后，AI优化将使用专属策略</div>
                </div>

                {{-- 完成度圆环 --}}
                <div class="text-center mb-4">
                    <div style="position: relative; display: inline-block; width: 120px; height: 120px;">
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            <circle class="progress-ring-bg" cx="60" cy="60" r="52"></circle>
                            <circle class="progress-ring-fill" id="progress-ring" cx="60" cy="60" r="52" stroke-dasharray="326.73" stroke-dashoffset="326.73"></circle>
                        </svg>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                            <div style="font-size: 24px; font-weight: 700; color: #2563eb;" id="progress-percent">0%</div>
                            <div style="font-size: 11px; color: #9ca3af;">完成度</div>
                        </div>
                    </div>
                </div>

                {{-- 当前步骤提示 --}}
                <div class="step-hint-card">
                    <div class="step-hint-title">
                        <i class="ti ti-bulb"></i>当前步骤提示
                    </div>
                    <div class="step-hint-body" id="step-hint-body">
                        填写简历标题和目标岗位，让 HR 快速了解你的求职方向。
                    </div>
                </div>

                {{-- 各模块状态 --}}
                <div class="mb-3">
                    <div style="font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 10px;">填写进度</div>
                    <div id="module-status-list">
                        @foreach([
                            ['key' => 'basic', 'icon' => 'ti-user', 'label' => '基本信息'],
                            ['key' => 'education', 'icon' => 'ti-school', 'label' => '教育经历'],
                            ['key' => 'experience', 'icon' => 'ti-briefcase', 'label' => '实习经历'],
                            ['key' => 'project', 'icon' => 'ti-code', 'label' => '项目经验'],
                            ['key' => 'skill', 'icon' => 'ti-certificate', 'label' => '技能证书'],
                            ['key' => 'certificate', 'icon' => 'ti-award', 'label' => '获奖情况'],
                        ] as $item)
                        <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom: 1px solid #f3f4f6;" data-status-key="{{ $item['key'] }}">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti {{ $item['icon'] }}" style="color: #9ca3af; font-size: 14px;"></i>
                                <span style="font-size: 13px; color: #4b5563;">{{ $item['label'] }}</span>
                            </div>
                            <span class="badge bg-secondary-lt" style="font-size: 11px;" data-status-badge="{{ $item['key'] }}">待填写</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- 一键优化全部 --}}
                <button type="button" class="btn-batch-optimize mb-3" id="btn-batch-optimize" data-action="batch-optimize">
                    <i class="ti ti-robot"></i>一键优化全部模块
                </button>

                {{-- AI 实时分析 --}}
                <div class="step-hint-card" id="ai-analysis-card" style="background: #f0fdf4; border-color: #bbf7d0; display: none;">
                    <div class="step-hint-title" style="color: #15803d;">
                        <i class="ti ti-sparkles"></i>AI 分析
                    </div>
                    <div class="step-hint-body" style="font-size: 12px;" id="ai-analysis-body">
                        点击模块卡片上的「AI 优化」按钮，获取智能优化建议。
                    </div>
                </div>

                {{-- 通用建议 --}}
                <div class="step-hint-card" style="background: #fffbeb; border-color: #fde68a;">
                    <div class="step-hint-title" style="color: #d97706;">
                        <i class="ti ti-flame"></i>优化建议
                    </div>
                    <div class="step-hint-body" style="font-size: 12px;">
                        <div class="mb-2">使用量化结果，例如「提升 30% 转化率」。</div>
                        <div class="mb-2">项目经历写明技术栈、职责和成果。</div>
                        <div>目标岗位尽量具体，便于 ATS 评分。</div>
                    </div>
                </div>

                <div class="alert alert-info mb-0 mt-3" style="border-radius: 10px; font-size: 12px;">
                    <i class="ti ti-automation me-1"></i>系统每 5 秒自动保存草稿到本地
                </div>
            </div>
        </div>
    </div>
