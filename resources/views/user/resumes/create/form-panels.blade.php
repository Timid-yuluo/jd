<div class="row">
    <div class="col-lg-8">
        @if(($resumeQuota['allowed'] ?? true) === false)
            <div class="alert alert-warning">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <i class="ti ti-alert-triangle me-1"></i>
                        当前套餐最多可创建 {{ (int) ($resumeQuota['limit'] ?? 0) }} 份简历，已使用 {{ (int) ($resumeQuota['used'] ?? 0) }} 份。
                    </div>
                    <a href="{{ route('user.membership.pricing') }}" class="btn btn-sm btn-primary">
                        <i class="ti ti-crown me-1"></i>升级套餐
                    </a>
                </div>
            </div>
        @endif
        <form action="{{ route('user.resumes.store') }}" method="POST" id="resume-form" data-resume-quota-allowed="{{ ($resumeQuota['allowed'] ?? true) ? '1' : '0' }}" data-action="submit-form">
            @csrf
            <input type="hidden" name="title" id="hidden-title">
            <input type="hidden" name="target_job" id="hidden-target-job">
            <input type="hidden" name="target_company" id="hidden-target-company">
            <input type="hidden" name="template" id="hidden-template">
            <input type="hidden" name="content_raw" id="hidden-content-raw">
            <input type="hidden" name="modules" id="hidden-modules">
            <input type="hidden" name="career_track_id" id="hidden-career-track-id">

            <div class="card">
                <div class="card-body">
                    {{-- 导入区域 --}}
                    <div class="import-zone" id="import-zone" data-action="import-resume">
                        <div class="mb-2">
                            <i class="ti ti-file-upload" style="font-size: 32px; color: #9ca3af;"></i>
                        </div>
                        <div style="font-size: 14px; color: #374151; font-weight: 600;">导入已有简历</div>
                        <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">支持 DOC、DOCX、PDF 格式，系统将自动解析并填充到各模块</div>
                    </div>
                    <input type="file" id="resume-file" accept=".doc,.docx,.pdf,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" style="display: none;" data-action="import-file">
                    <div id="import-status" class="alert alert-info mb-3 d-none"></div>

                    {{-- 步骤指示器 --}}
                    <div class="resume-steps" id="step-indicator">
                        @foreach([
                            '1' => '基本信息',
                            '2' => '教育经历',
                            '3' => '实习经历',
                            '4' => '项目经验',
                            '5' => '技能证书',
                            '6' => '获奖情况'
                        ] as $num => $label)
                        <div class="step-node" data-step="{{ $num }}" data-action="step-click" data-step-num="{{ $num }}">
                            <div class="step-circle">
                                @if($num == '1')
                                    <i class="ti ti-user"></i>
                                @elseif($num == '2')
                                    <i class="ti ti-school"></i>
                                @elseif($num == '3')
                                    <i class="ti ti-briefcase"></i>
                                @elseif($num == '4')
                                    <i class="ti ti-code"></i>
                                @elseif($num == '5')
                                    <i class="ti ti-certificate"></i>
                                @else
                                    <i class="ti ti-award"></i>
                                @endif
                            </div>
                            <div class="step-label">{{ $label }}</div>
                        </div>
                        @endforeach
                    </div>

                    {{-- 步骤 1：基本信息 --}}
                    <div class="step-panel" data-step="1">
                        <div style="margin-bottom: 20px;">
                            <h4 style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 4px;">基本信息</h4>
                            <p style="font-size: 13px; color: #6b7280; margin: 0;">填写简历标题和个人信息，让 HR 第一时间了解你</p>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label-modern required">简历标题</label>
                                    <input type="text" class="form-control-modern w-100" id="input-title" placeholder="例如：前端开发工程师简历" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label-modern">目标岗位</label>
                                    <input type="text" class="form-control-modern w-100" id="input-target-job" placeholder="例如：前端开发工程师">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label-modern">目标公司</label>
                                    <input type="text" class="form-control-modern w-100" id="input-target-company" placeholder="例如：字节跳动">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label-modern">模板风格</label>
                                    <div class="row g-2" id="template-picker">
                                        @php
                                            $templates = [
                                                'classic' => ['label' => '经典', 'desc' => '传统上下布局', 'color' => '#2563eb', 'layout' => 'top-bottom'],
                                                'modern' => ['label' => '现代', 'desc' => '双栏侧边栏', 'color' => '#7c3aed', 'layout' => 'sidebar'],
                                                'minimal' => ['label' => '极简', 'desc' => '优雅留白', 'color' => '#6b7280', 'layout' => 'minimal'],
                                                'timeline' => ['label' => '时间轴', 'desc' => '时间线布局', 'color' => '#0891b2', 'layout' => 'timeline'],
                                                'creative' => ['label' => '创意', 'desc' => '个性设计', 'color' => '#e11d48', 'layout' => 'creative'],
                                                'elegant' => ['label' => '典雅', 'desc' => '高端质感', 'color' => '#b45309', 'layout' => 'elegant'],
                                            ];
                                        @endphp
                                        @foreach($templates as $key => $tpl)
                                        <div class="col-6 col-sm-4">
                                            <label class="template-card d-block cursor-pointer" data-template="{{ $key }}">
                                                <input type="radio" name="template_radio" value="{{ $key }}" class="d-none" {{ $key === 'classic' ? 'checked' : '' }}>
                                                <div class="template-thumb p-2 rounded border text-center" style="height:80px;background:linear-gradient(135deg, {{ $tpl['color'] }}11 0%, {{ $tpl['color'] }}08 100%);border-color:{{ $key === 'classic' ? $tpl['color'] : '#e5e7eb' }};transition:border-color .15s,box-shadow .15s;">
                                                    <div style="height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                                                        @if($tpl['layout'] === 'sidebar')
                                                            <div style="width:70%;height:100%;display:flex;gap:2px;">
                                                                <div style="width:35%;background:{{ $tpl['color'] }}22;border-radius:2px;"></div>
                                                                <div style="flex:1;display:flex;flex-direction:column;gap:2px;">
                                                                    <div style="height:15%;background:{{ $tpl['color'] }}33;border-radius:1px;"></div>
                                                                    <div style="height:25%;background:#e5e7eb;border-radius:1px;"></div>
                                                                    <div style="height:25%;background:#e5e7eb;border-radius:1px;"></div>
                                                                    <div style="height:25%;background:#e5e7eb;border-radius:1px;"></div>
                                                                </div>
                                                            </div>
                                                        @elseif($tpl['layout'] === 'minimal')
                                                            <div style="width:80%;height:100%;display:flex;flex-direction:column;gap:3px;padding:4px 0;">
                                                                <div style="height:12%;width:50%;background:{{ $tpl['color'] }}33;border-radius:1px;margin:0 auto;"></div>
                                                                <div style="height:20%;background:#f3f4f6;border-radius:1px;"></div>
                                                                <div style="height:20%;background:#f3f4f6;border-radius:1px;"></div>
                                                                <div style="height:20%;background:#f3f4f6;border-radius:1px;"></div>
                                                            </div>
                                                        @elseif($tpl['layout'] === 'timeline')
                                                            <div style="width:75%;height:100%;display:flex;flex-direction:column;gap:2px;padding-left:8px;border-left:2px solid {{ $tpl['color'] }}44;">
                                                                <div style="height:20%;background:{{ $tpl['color'] }}22;border-radius:1px;"></div>
                                                                <div style="height:20%;background:#e5e7eb;border-radius:1px;"></div>
                                                                <div style="height:20%;background:#e5e7eb;border-radius:1px;"></div>
                                                                <div style="height:20%;background:#e5e7eb;border-radius:1px;"></div>
                                                            </div>
                                                        @elseif($tpl['layout'] === 'creative')
                                                            <div style="width:75%;height:100%;display:flex;flex-direction:column;gap:2px;">
                                                                <div style="height:25%;background:linear-gradient(90deg,{{ $tpl['color'] }}33,{{ $tpl['color'] }}11);border-radius:2px;"></div>
                                                                <div style="display:flex;gap:2px;height:50%;">
                                                                    <div style="flex:1;background:#e5e7eb;border-radius:1px;"></div>
                                                                    <div style="flex:1;background:#e5e7eb;border-radius:1px;"></div>
                                                                </div>
                                                                <div style="height:15%;background:#e5e7eb;border-radius:1px;"></div>
                                                            </div>
                                                        @elseif($tpl['layout'] === 'elegant')
                                                            <div style="width:75%;height:100%;display:flex;flex-direction:column;gap:2px;">
                                                                <div style="height:18%;border-bottom:1px solid {{ $tpl['color'] }}44;display:flex;align-items:center;justify-content:center;">
                                                                    <div style="width:40%;height:60%;background:{{ $tpl['color'] }}33;border-radius:1px;"></div>
                                                                </div>
                                                                <div style="height:22%;background:#f5f0eb;border-radius:1px;"></div>
                                                                <div style="height:22%;background:#f5f0eb;border-radius:1px;"></div>
                                                                <div style="height:22%;background:#f5f0eb;border-radius:1px;"></div>
                                                            </div>
                                                        @else
                                                            <div style="width:75%;height:100%;display:flex;flex-direction:column;gap:2px;">
                                                                <div style="height:18%;background:{{ $tpl['color'] }}33;border-radius:1px;"></div>
                                                                <div style="height:22%;background:#e5e7eb;border-radius:1px;"></div>
                                                                <div style="height:22%;background:#e5e7eb;border-radius:1px;"></div>
                                                                <div style="height:22%;background:#e5e7eb;border-radius:1px;"></div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="text-center mt-1">
                                                    <div class="small fw-semibold" style="color:{{ $tpl['color'] }};">{{ $tpl['label'] }}</div>
                                                    <div class="text-muted" style="font-size:.65rem;">{{ $tpl['desc'] }}</div>
                                                </div>
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                    <input type="hidden" name="template" id="hidden-template" value="classic">
                                </div>
                            </div>
                        </div>
                        <div style="border-top: 1px solid #f3f4f6; padding-top: 20px; margin-top: 8px;">
                            <h5 style="font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 16px;">个人信息</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label-modern">姓名</label>
                                        <input type="text" class="form-control-modern w-100" id="input-name" placeholder="你的真实姓名">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label-modern">电话</label>
                                        <input type="text" class="form-control-modern w-100" id="input-phone" placeholder="常用手机号码">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label-modern">邮箱</label>
                                        <input type="text" class="form-control-modern w-100" id="input-email" placeholder="常用邮箱地址">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label-modern">所在地</label>
                                        <input type="text" class="form-control-modern w-100" id="input-location" placeholder="例如：北京">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label-modern">性别</label>
                                        <input type="text" class="form-control-modern w-100" id="input-gender" placeholder="例如：男/女">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label-modern">生日</label>
                                        <input type="text" class="form-control-modern w-100" id="input-birthday" placeholder="例如：2004-06-01">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label-modern">微信</label>
                                        <input type="text" class="form-control-modern w-100" id="input-wechat" placeholder="微信号">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label-modern">GitHub</label>
                                        <input type="text" class="form-control-modern w-100" id="input-github" placeholder="GitHub 用户名或链接">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label-modern">作品集</label>
                                        <input type="text" class="form-control-modern w-100" id="input-website" placeholder="个人网站 / 作品集链接">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-2 d-flex align-items-center justify-content-between">
                                        <label class="form-label-modern mb-0">自定义字段</label>
                                        <button type="button" class="btn-ai-generate" data-action="add-custom-field">
                                            <i class="ti ti-plus"></i>添加字段
                                        </button>
                                    </div>
                                    <div id="personal-custom-fields"></div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label-modern mb-0">个人简介</label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn-ai-generate" data-action="ai-generate-personal">
                                            <i class="ti ti-wand"></i>AI 生成
                                        </button>
                                        <button type="button" class="btn-ai-optimize" data-action="ai-optimize-personal">
                                            <i class="ti ti-sparkles"></i>AI 优化
                                        </button>
                                    </div>
                                </div>
                                <textarea class="form-control-modern w-100" id="input-personal-content" rows="3" placeholder="一句话介绍自己，概括核心优势与职业定位"></textarea>
                                <div class="ai-result-panel d-none" id="personal-ai-result"></div>
                            </div>
                        </div>
                    </div>

                    {{-- 步骤 2：教育经历 --}}
                    <div class="step-panel d-none" data-step="2">
                        <div style="margin-bottom: 20px;">
                            <h4 style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 4px;">教育经历</h4>
                            <p style="font-size: 13px; color: #6b7280; margin: 0;">展示你的学历背景，按时间从近到远添加</p>
                        </div>
                        <div id="list-education"></div>
                        <button type="button" class="btn-add-module" data-action="add-item" data-section="education">
                            <i class="ti ti-plus"></i>添加教育经历
                        </button>
                    </div>

                    {{-- 步骤 3：实习经历 --}}
                    <div class="step-panel d-none" data-step="3">
                        <div style="margin-bottom: 20px;">
                            <h4 style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 4px;">实习经历</h4>
                            <p style="font-size: 13px; color: #6b7280; margin: 0;">描述你的实习经验，突出职责与量化成果</p>
                        </div>
                        <div id="list-experience"></div>
                        <button type="button" class="btn-add-module" data-action="add-item" data-section="experience">
                            <i class="ti ti-plus"></i>添加实习经历
                        </button>
                    </div>

                    {{-- 步骤 4：项目经验 --}}
                    <div class="step-panel d-none" data-step="4">
                        <div style="margin-bottom: 20px;">
                            <h4 style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 4px;">项目经验</h4>
                            <p style="font-size: 13px; color: #6b7280; margin: 0;">展示你的项目作品，说明技术栈与个人贡献</p>
                        </div>
                        <div id="list-project"></div>
                        <button type="button" class="btn-add-module" data-action="add-item" data-section="project">
                            <i class="ti ti-plus"></i>添加项目经验
                        </button>
                    </div>

                    {{-- 步骤 5：技能证书 --}}
                    <div class="step-panel d-none" data-step="5">
                        <div style="margin-bottom: 20px;">
                            <h4 style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 4px;">技能证书</h4>
                            <p style="font-size: 13px; color: #6b7280; margin: 0;">列出你的专业技能和相关证书</p>
                        </div>
                        <div id="list-skill"></div>
                        <button type="button" class="btn-add-module" data-action="add-item" data-section="skill">
                            <i class="ti ti-plus"></i>添加技能或证书
                        </button>
                    </div>

                    {{-- 步骤 6：获奖情况 --}}
                    <div class="step-panel d-none" data-step="6">
                        <div style="margin-bottom: 20px;">
                            <h4 style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 4px;">获奖情况</h4>
                            <p style="font-size: 13px; color: #6b7280; margin: 0;">展示你的荣誉奖项，增强竞争力</p>
                        </div>
                        <div id="list-certificate"></div>
                        <button type="button" class="btn-add-module" data-action="add-item" data-section="certificate">
                            <i class="ti ti-plus"></i>添加奖项或荣誉
                        </button>
                        <div class="alert alert-info mt-3 mb-0" style="border-radius: 10px; background: #eff6ff; border-color: #bfdbfe; color: #1e40af;">
                            <i class="ti ti-info-circle me-2"></i>点击「保存简历」后将创建简历并跳转到详情页，你可以在编辑器中继续完善。
                        </div>
                    </div>

                    {{-- 导航按钮 --}}
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-4" style="border-top: 1px solid #f3f4f6;">
                        <button type="button" class="nav-btn nav-btn-secondary" id="btn-prev" data-action="change-step" data-step-dir="-1">
                            <i class="ti ti-arrow-left"></i>上一步
                        </button>
                        <div class="d-flex gap-2">
                            <a href="{{ route('user.resumes.index') }}" class="nav-btn nav-btn-secondary">取消</a>
                            <button type="button" class="nav-btn nav-btn-primary" id="btn-next" data-action="change-step" data-step-dir="1" {{ ($resumeQuota['allowed'] ?? true) ? '' : 'disabled' }}>
                                下一步<i class="ti ti-arrow-right"></i>
                            </button>
                            <button type="submit" class="nav-btn nav-btn-primary d-none" id="btn-submit" {{ ($resumeQuota['allowed'] ?? true) ? '' : 'disabled' }}>
                                <i class="ti ti-device-floppy"></i>保存简历
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
