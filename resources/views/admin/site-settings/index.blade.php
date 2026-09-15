@extends('layouts.admin')

@section('title', '网站设置')
@section('page-pretitle', '系统设置')
@section('page-title', '网站基本设置')

@section('content')
<div
    data-site-settings-page="1"
    data-route-admin-site-settings-clear-cache="{{ route('admin.site-settings.clear-cache') }}"
    data-route-admin-site-settings-export="{{ route('admin.site-settings.export') }}"
    data-route-admin-site-settings-sanitize-head-snippets="{{ route('admin.site-settings.sanitize-head-snippets') }}"
>
<div class="row row-cards">
    <div class="col-lg-8">
        <form action="{{ route('admin.site-settings.update') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a href="#tab-general" class="nav-link active" data-bs-toggle="tab" role="tab" aria-selected="true">
                                <i class="ti ti-info-circle me-1"></i>基础信息
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-appearance" class="nav-link" data-bs-toggle="tab" role="tab" aria-selected="false">
                                <i class="ti ti-palette me-1"></i>外观主题
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-features" class="nav-link" data-bs-toggle="tab" role="tab" aria-selected="false">
                                <i class="ti ti-toggle-right me-1"></i>功能开关
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-security" class="nav-link" data-bs-toggle="tab" role="tab" aria-selected="false">
                                <i class="ti ti-shield-lock me-1"></i>安全
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-integration" class="nav-link" data-bs-toggle="tab" role="tab" aria-selected="false">
                                <i class="ti ti-plug me-1"></i>集成
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-advanced" class="nav-link" data-bs-toggle="tab" role="tab" aria-selected="false">
                                <i class="ti ti-code me-1"></i>高级
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        {{-- 基础信息 --}}
                        <div class="tab-pane active show" id="tab-general" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label required">站点名称</label>
                                <input type="text" name="site_name" class="form-control" value="{{ old('site_name', $settings['site_name'] ?? config('app.name')) }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">站点副标题</label>
                                <input type="text" name="site_subtitle" class="form-control" value="{{ old('site_subtitle', $settings['site_subtitle'] ?? '') }}" placeholder="例如：AI 求职与成长平台">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">站点地址</label>
                                <input type="url" name="site_url" class="form-control" value="{{ old('site_url', $settings['site_url'] ?? '') }}" placeholder="https://example.com">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">站点 Logo 地址</label>
                                    <input type="url" name="site_logo_url" class="form-control" value="{{ old('site_logo_url', $settings['site_logo_url'] ?? '') }}" placeholder="https://example.com/logo.png">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Favicon 地址</label>
                                    <input type="url" name="favicon_url" class="form-control" value="{{ old('favicon_url', $settings['favicon_url'] ?? '') }}" placeholder="https://example.com/favicon.ico">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">上传站点 Logo</label>
                                    <input type="file" name="site_logo_file" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg">
                                    <div class="form-hint">上传后将覆盖 Logo 地址，建议透明 PNG 或 SVG。</div>
                                    @if(!empty($settings['site_logo_url']))
                                        <div class="mt-2 d-flex align-items-center">
                                            <img src="{{ $settings['site_logo_url'] }}" alt="当前 Logo" style="max-height: 42px;">
                                            <label class="form-check ms-3">
                                                <input class="form-check-input" type="checkbox" name="remove_site_logo" value="1">
                                                <span class="form-check-label text-danger">删除</span>
                                            </label>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">上传 Favicon</label>
                                    <input type="file" name="favicon_file" class="form-control" accept=".png,.jpg,.jpeg,.webp,.ico">
                                    <div class="form-hint">建议 32x32 或 48x48。</div>
                                    @if(!empty($settings['favicon_url']))
                                        <div class="mt-2 d-flex align-items-center">
                                            <img src="{{ $settings['favicon_url'] }}" alt="当前 Favicon" style="width: 20px; height: 20px;">
                                            <a href="{{ $settings['favicon_url'] }}" class="ms-2" target="_blank">查看</a>
                                            <label class="form-check ms-3">
                                                <input class="form-check-input" type="checkbox" name="remove_favicon" value="1">
                                                <span class="form-check-label text-danger">删除</span>
                                            </label>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">联系邮箱</label>
                                <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">版权声明</label>
                                <input type="text" name="copyright_text" class="form-control" value="{{ old('copyright_text', $settings['copyright_text'] ?? '') }}" placeholder="© 2026 Your Company. All rights reserved.">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ICP备案号</label>
                                    <input type="text" name="icp_number" class="form-control" value="{{ old('icp_number', $settings['icp_number'] ?? '') }}" placeholder="京ICP备xxxxxxxx号">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">公安备案号</label>
                                    <input type="text" name="police_record_number" class="form-control" value="{{ old('police_record_number', $settings['police_record_number'] ?? '') }}" placeholder="京公网安备xxxxxxxx号">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">公安备案链接</label>
                                <input type="url" name="police_record_url" class="form-control" value="{{ old('police_record_url', $settings['police_record_url'] ?? '') }}" placeholder="https://beian.mps.gov.cn/">
                                <div class="form-hint">用于页脚公安备案号跳转链接，可留空。</div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">微信链接</label>
                                    <input type="url" name="wechat_url" class="form-control" value="{{ old('wechat_url', $settings['wechat_url'] ?? '') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">GitHub 链接</label>
                                    <input type="url" name="github_url" class="form-control" value="{{ old('github_url', $settings['github_url'] ?? '') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">LinkedIn 链接</label>
                                    <input type="url" name="linkedin_url" class="form-control" value="{{ old('linkedin_url', $settings['linkedin_url'] ?? '') }}">
                                </div>
                            </div>
                        </div>

                        {{-- 外观主题 --}}
                        <div class="tab-pane" id="tab-appearance" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">主题模式</label>
                                    <select name="theme_layout_mode" class="form-select">
                                        <option value="light" {{ ($settings['theme_layout_mode'] ?? 'light') === 'light' ? 'selected' : '' }}>浅色模式</option>
                                        <option value="dark" {{ ($settings['theme_layout_mode'] ?? '') === 'dark' ? 'selected' : '' }}>深色模式</option>
                                        <option value="auto" {{ ($settings['theme_layout_mode'] ?? '') === 'auto' ? 'selected' : '' }}>跟随系统</option>
                                    </select>
                                    <div class="form-hint">选择后台管理界面的主题风格</div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">主题主色</label>
                                    <div class="input-group">
                                        <input type="color" id="color-picker" class="form-control form-control-color" value="{{ $settings['theme_primary_color'] ?? '#206bc4' }}" style="max-width: 50px;">
                                        <input type="text" name="theme_primary_color" class="form-control" value="{{ $settings['theme_primary_color'] ?? '#206bc4' }}" placeholder="#206bc4" id="color-input">
                                    </div>
                                    <div class="form-hint">品牌主题色，用于按钮、链接等</div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">简历编辑器滚动模式</label>
                                    <select name="editor_scroll_mode" class="form-select">
                                        <option value="natural" {{ ($settings['editor_scroll_mode'] ?? config('resume.editor.scroll_mode', 'natural')) === 'natural' ? 'selected' : '' }}>自然滚动（推荐）</option>
                                        <option value="split" {{ ($settings['editor_scroll_mode'] ?? config('resume.editor.scroll_mode', 'natural')) === 'split' ? 'selected' : '' }}>左右独立滚动</option>
                                    </select>
                                    <div class="form-hint">自然滚动兼容性更好；独立滚动适合大屏双栏编辑。</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="theme_sidebar_collapsed" value="1" {{ ($settings['theme_sidebar_collapsed'] ?? false) ? 'checked' : '' }}>
                                    <span class="form-check-label">默认收起侧边栏</span>
                                </label>
                                <div class="form-hint">登录后侧边栏默认收起状态</div>
                            </div>

                            <hr class="my-4">
                            <h4 class="mb-3">SEO 设置</h4>

                            <div class="mb-3">
                                <label class="form-label">SEO 关键词</label>
                                <input type="text" name="seo_keywords" class="form-control" value="{{ old('seo_keywords', $settings['seo_keywords'] ?? '') }}" placeholder="AI求职, 简历优化, 模拟面试">
                                <div class="form-hint">多个关键词用逗号分隔，用于搜索引擎优化</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">SEO 描述</label>
                                <textarea name="seo_description" rows="3" class="form-control" placeholder="用于搜索引擎的站点描述">{{ old('seo_description', $settings['seo_description'] ?? '') }}</textarea>
                                <div class="form-hint">建议 150-160 个字符，描述网站主要内容</div>
                            </div>
                        </div>

                        {{-- 功能开关 --}}
                        <div class="tab-pane" id="tab-features" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="card bg-warning-lt">
                                        <div class="card-body">
                                            <label class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" {{ ($settings['maintenance_mode'] ?? false) ? 'checked' : '' }}>
                                                <span class="form-check-label fw-bold">维护模式</span>
                                            </label>
                                            <div class="text-secondary small">开启后普通用户无法访问前台，仅管理员可登录</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <label class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" name="allow_registration" value="1" {{ ($settings['allow_registration'] ?? true) ? 'checked' : '' }}>
                                                <span class="form-check-label fw-bold">允许用户注册</span>
                                            </label>
                                            <div class="text-secondary small">关闭后禁止新用户注册，已有用户不受影响</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <label class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" name="email_verification_required" value="1" {{ ($settings['email_verification_required'] ?? false) ? 'checked' : '' }}>
                                                <span class="form-check-label fw-bold">强制邮箱验证</span>
                                            </label>
                                            <div class="text-secondary small">注册后必须验证邮箱才能使用完整功能</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <label class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" name="allow_guest_resume_view" value="1" {{ ($settings['allow_guest_resume_view'] ?? false) ? 'checked' : '' }}>
                                                <span class="form-check-label fw-bold">允许游客查看简历</span>
                                            </label>
                                            <div class="text-secondary small">未登录用户可查看公开的简历内容</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">维护公告</label>
                                <textarea name="maintenance_notice" rows="4" class="form-control" placeholder="系统维护通知（可为空）">{{ old('maintenance_notice', $settings['maintenance_notice'] ?? '') }}</textarea>
                                <div class="form-hint">维护模式下显示给用户的通知信息，支持 HTML</div>
                            </div>
                        </div>

                        {{-- 安全设置 --}}
                        <div class="tab-pane" id="tab-security" role="tabpanel">
                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <label class="form-label">最大登录尝试次数</label>
                                    <input type="number" name="max_login_attempts" class="form-control" value="{{ $settings['max_login_attempts'] ?? 5 }}" min="1" max="10">
                                    <div class="form-hint">超过后锁定账户15分钟，防止暴力破解</div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label class="form-label">密码最小长度</label>
                                    <input type="number" name="password_min_length" class="form-control" value="{{ $settings['password_min_length'] ?? 8 }}" min="6" max="32">
                                    <div class="form-hint">新密码必须达到的最小字符数</div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label class="form-label">会话有效期（分钟）</label>
                                    <input type="number" name="session_lifetime" class="form-control" value="{{ $settings['session_lifetime'] ?? 120 }}" min="10" max="480">
                                    <div class="form-hint">登录后无操作自动退出时间</div>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="ti ti-info-circle me-1"></i>
                                安全提示：建议定期修改管理员密码，并启用两步验证（如支持）。
                            </div>
                        </div>

                        {{-- 集成 --}}
                        <div class="tab-pane" id="tab-integration" role="tabpanel">
                            <h4 class="mb-3">邮件设置</h4>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">发件人邮箱</label>
                                    <input type="email" name="mail_from_address" class="form-control" value="{{ $settings['mail_from_address'] ?? '' }}" placeholder="noreply@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">发件人名称</label>
                                    <input type="text" name="mail_from_name" class="form-control" value="{{ $settings['mail_from_name'] ?? '' }}" placeholder="{{ config('app.name') }}">
                                </div>
                            </div>

                            <hr class="my-4">
                            <h4 class="mb-3">
                                <i class="ti ti-login-2 me-2"></i>快捷登录与账号绑定
                            </h4>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auth_quick_login_enabled" value="1" {{ ($settings['auth_quick_login_enabled'] ?? false) ? 'checked' : '' }}>
                                        <span class="form-check-label fw-bold">启用快捷登录入口</span>
                                    </label>
                                    <div class="form-hint">在登录页展示第三方快捷登录入口（需完成 OAuth 回调开发）。</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auth_account_binding_enabled" value="1" {{ ($settings['auth_account_binding_enabled'] ?? false) ? 'checked' : '' }}>
                                        <span class="form-check-label fw-bold">启用账号绑定能力</span>
                                    </label>
                                    <div class="form-hint">允许用户在个人中心进行第三方账号绑定/解绑。</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auth_provider_github_enabled" value="1" {{ ($settings['auth_provider_github_enabled'] ?? false) ? 'checked' : '' }}>
                                        <span class="form-check-label">GitHub 登录</span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auth_provider_alipay_enabled" value="1" {{ ($settings['auth_provider_alipay_enabled'] ?? false) ? 'checked' : '' }}>
                                        <span class="form-check-label">支付宝登录</span>
                                    </label>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auth_quick_login_auto_register" value="1" {{ ($settings['auth_quick_login_auto_register'] ?? false) ? 'checked' : '' }}>
                                        <span class="form-check-label">未绑定自动注册</span>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auth_quick_login_link_by_email" value="1" {{ ($settings['auth_quick_login_link_by_email'] ?? true) ? 'checked' : '' }}>
                                        <span class="form-check-label">同邮箱自动关联</span>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">OAuth 状态TTL（秒）</label>
                                    <input type="number" min="60" max="1800" name="auth_oauth_state_ttl_seconds" class="form-control" value="{{ $settings['auth_oauth_state_ttl_seconds'] ?? 600 }}">
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><i class="ti ti-brand-github me-1"></i>GitHub OAuth 配置</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Client ID</label>
                                            <input type="text" name="auth_github_client_id" class="form-control" value="{{ $settings['auth_github_client_id'] ?? '' }}" placeholder="GitHub OAuth App Client ID">
                                            <div class="form-hint">请填写 GitHub OAuth App 的 `Client ID`，不要填写纯数字的 `App ID`。</div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Client Secret</label>
                                            <input type="password" name="auth_github_client_secret" class="form-control" value="{{ $settings['auth_github_client_secret'] ?? '' }}" placeholder="GitHub OAuth Client Secret">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">回调地址</label>
                                            <input type="url" name="auth_github_redirect_url" class="form-control" value="{{ $settings['auth_github_redirect_url'] ?? '' }}" placeholder="https://example.com/auth/oauth/github/callback">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><i class="ti ti-brand-alipay me-1"></i>支付宝登录配置</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">应用 ID</label>
                                            <input type="text" name="auth_alipay_app_id" class="form-control" value="{{ $settings['auth_alipay_app_id'] ?? '' }}" placeholder="支付宝应用ID">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">公钥</label>
                                            <textarea name="auth_alipay_public_key" rows="3" class="form-control font-monospace small" placeholder="支付宝公钥">{{ $settings['auth_alipay_public_key'] ?? '' }}</textarea>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">私钥</label>
                                            <textarea name="auth_alipay_private_key" rows="3" class="form-control font-monospace small" placeholder="应用私钥">{{ $settings['auth_alipay_private_key'] ?? '' }}</textarea>
                                        </div>
                                        <div class="col-md-12 mb-0">
                                            <label class="form-label">回调地址</label>
                                            <input type="url" name="auth_alipay_redirect_url" class="form-control" value="{{ $settings['auth_alipay_redirect_url'] ?? '' }}" placeholder="https://example.com/auth/oauth/alipay/callback">
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="auth_alipay_verify_sign_enabled" value="1" {{ ($settings['auth_alipay_verify_sign_enabled'] ?? true) ? 'checked' : '' }}>
                                            <span class="form-check-label">启用支付宝响应验签</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auth_binding_allow_unbind" value="1" {{ ($settings['auth_binding_allow_unbind'] ?? true) ? 'checked' : '' }}>
                                        <span class="form-check-label">允许解绑第三方账号</span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auth_binding_require_password_confirm" value="1" {{ ($settings['auth_binding_require_password_confirm'] ?? true) ? 'checked' : '' }}>
                                        <span class="form-check-label">绑定/解绑需密码确认</span>
                                    </label>
                                </div>
                            </div>
                            <div class="alert alert-info mb-3">
                                <i class="ti ti-info-circle me-1"></i><strong>通用配置顺序：</strong>
                                先创建平台应用并拿到密钥，再填写本页参数并保存，最后开启“快捷登录入口”与对应渠道开关。
                            </div>
                            <div class="row g-3 mb-2">
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="fw-bold mb-2"><i class="ti ti-brand-github me-1"></i>GitHub 配置说明</div>
                                        <div class="small text-secondary">
                                            1. 进入 GitHub `Settings` → `Developer settings` → `OAuth Apps` 创建应用。<br>
                                            2. 将 GitHub 的 `Client ID` / `Client Secret` 分别填入本页对应字段。<br>
                                            3. `Authorization callback URL` 建议填写：<code>https://你的域名/auth/oauth/github/callback</code>。<br>
                                            4. 请使用 GitHub `OAuth Apps` 的 `Client ID / Client Secret`，不要误填 GitHub App 的纯数字 `App ID`。<br>
                                            5. 平台后台与本页回调地址必须完全一致（协议、域名、路径都一致）。
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="fw-bold mb-2"><i class="ti ti-brand-alipay me-1"></i>支付宝配置说明</div>
                                        <div class="small text-secondary">
                                            1. 在支付宝开放平台创建网页应用并获取 `应用ID`。<br>
                                            2. 将支付宝公钥、应用私钥分别填入本页字段。<br>
                                            3. 登录回调地址建议填写：<code>https://你的域名/auth/oauth/alipay/callback</code>。<br>
                                            4. 如开启签名校验，请确保网关、公钥、签名算法与服务端代码一致。
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-hint mb-0">提示：配置完成后请同步实现 OAuth 路由、回调签名校验、绑定冲突处理与解绑审计日志。</div>

                            <hr class="my-4">
                            <h4 class="mb-3">
                                <i class="ti ti-credit-card me-2"></i>支付方式配置
                            </h4>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="payment_alipay_enabled" value="1" {{ ($settings['payment_alipay_enabled'] ?? false) ? 'checked' : '' }}>
                                        <span class="form-check-label fw-bold">启用支付宝支付</span>
                                    </label>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><i class="ti ti-brand-alipay me-1"></i>支付宝支付参数</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">应用 AppID</label>
                                            <input type="text" name="payment_alipay_app_id" class="form-control" value="{{ $settings['payment_alipay_app_id'] ?? '' }}" placeholder="支付宝支付应用 AppID">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">支付回调地址</label>
                                            <input type="url" name="payment_alipay_notify_url" class="form-control" value="{{ $settings['payment_alipay_notify_url'] ?? '' }}" placeholder="https://你的域名/alipay-pay/notify">
                                        </div>
                                        <div class="col-md-6 mb-0">
                                            <label class="form-label">应用私钥</label>
                                            <textarea name="payment_alipay_private_key" rows="3" class="form-control font-monospace small" placeholder="支付宝应用私钥">{{ $settings['payment_alipay_private_key'] ?? '' }}</textarea>
                                        </div>
                                        <div class="col-md-6 mb-0">
                                            <label class="form-label">支付宝公钥</label>
                                            <textarea name="payment_alipay_public_key" rows="3" class="form-control font-monospace small" placeholder="支付宝公钥">{{ $settings['payment_alipay_public_key'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info mb-3">
                                <i class="ti ti-info-circle me-1"></i>建议先配置并校验支付参数，再对外开启支付开关。未接入真实下单前，前台仅展示待支付状态。
                            </div>

                            <hr class="my-4">
                            <h4 class="mb-3">第三方统计代码</h4>

                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="ti ti-brand-google me-1"></i>Google Analytics (GA4)
                                </label>
                                <textarea name="analytics_google" rows="3" class="form-control font-monospace small" placeholder="<!-- Google tag (gtag.js) -->...">{{ $settings['analytics_google'] ?? '' }}</textarea>
                                <div class="form-hint">粘贴完整的 Google Analytics 跟踪代码</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="ti ti-chart-pie me-1"></i>百度统计
                                </label>
                                <textarea name="analytics_baidu" rows="3" class="form-control font-monospace small" placeholder="<!-- 百度统计代码 -->...">{{ $settings['analytics_baidu'] ?? '' }}</textarea>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">
                                    <i class="ti ti-brand-microsoft me-1"></i>Microsoft Clarity
                                </label>
                                <textarea name="analytics_clarity" rows="3" class="form-control font-monospace small" placeholder='<script type="text/javascript">...'>{{ $settings['analytics_clarity'] ?? '' }}</textarea>
                                <div class="form-hint">用户行为热图分析工具</div>
                            </div>

                            <hr class="my-4">
                            <h4 class="mb-3">
                                <i class="ti ti-scan me-2"></i>OCR 图片识别
                            </h4>
                            <div class="alert alert-info">
                                <i class="ti ti-info-circle me-1"></i>
                                配置OCR服务后，用户可以通过上传职位截图自动识别职位信息。推荐使用百度AI OCR（每月50000次免费额度）。
                                <a href="https://ai.baidu.com/tech/ocr" target="_blank" class="alert-link">申请百度AI账号</a>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">OCR 提供商</label>
                                <select name="ocr_provider" class="form-select">
                                    <option value="">未配置</option>
                                    <option value="baidu" {{ ($settings['ocr_provider'] ?? '') === 'baidu' ? 'selected' : '' }}>百度AI OCR（推荐）</option>
                                    <option value="tencent" {{ ($settings['ocr_provider'] ?? '') === 'tencent' ? 'selected' : '' }}>腾讯云OCR</option>
                                    <option value="aliyun" {{ ($settings['ocr_provider'] ?? '') === 'aliyun' ? 'selected' : '' }}>阿里云OCR</option>
                                </select>
                            </div>

                            <div id="ocr-baidu-config" class="ocr-config-section {{ ($settings['ocr_provider'] ?? '') === 'baidu' ? '' : 'd-none' }}">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">API Key</label>
                                        <input type="text" name="baidu_ocr_api_key" class="form-control" value="{{ $settings['baidu_ocr_api_key'] ?? '' }}" placeholder="百度AI应用的API Key">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Secret Key</label>
                                        <input type="password" name="baidu_ocr_secret_key" class="form-control" value="{{ $settings['baidu_ocr_secret_key'] ?? '' }}" placeholder="百度AI应用的Secret Key">
                                    </div>
                                </div>
                            </div>

                            <div id="ocr-tencent-config" class="ocr-config-section {{ ($settings['ocr_provider'] ?? '') === 'tencent' ? '' : 'd-none' }}">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SecretId</label>
                                        <input type="text" name="tencent_ocr_secret_id" class="form-control" value="{{ $settings['tencent_ocr_secret_id'] ?? '' }}" placeholder="腾讯云SecretId">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SecretKey</label>
                                        <input type="password" name="tencent_ocr_secret_key" class="form-control" value="{{ $settings['tencent_ocr_secret_key'] ?? '' }}" placeholder="腾讯云SecretKey">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">地域</label>
                                    <select name="tencent_ocr_region" class="form-select">
                                        <option value="ap-beijing" {{ ($settings['tencent_ocr_region'] ?? 'ap-beijing') === 'ap-beijing' ? 'selected' : '' }}>华北地区(北京)</option>
                                        <option value="ap-shanghai" {{ ($settings['tencent_ocr_region'] ?? '') === 'ap-shanghai' ? 'selected' : '' }}>华东地区(上海)</option>
                                        <option value="ap-guangzhou" {{ ($settings['tencent_ocr_region'] ?? '') === 'ap-guangzhou' ? 'selected' : '' }}>华南地区(广州)</option>
                                    </select>
                                </div>
                            </div>

                            <div id="ocr-aliyun-config" class="ocr-config-section {{ ($settings['ocr_provider'] ?? '') === 'aliyun' ? '' : 'd-none' }}">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">AccessKey ID</label>
                                        <input type="text" name="aliyun_ocr_access_key_id" class="form-control" value="{{ $settings['aliyun_ocr_access_key_id'] ?? '' }}" placeholder="阿里云AccessKey ID">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">AccessKey Secret</label>
                                        <input type="password" name="aliyun_ocr_access_key_secret" class="form-control" value="{{ $settings['aliyun_ocr_access_key_secret'] ?? '' }}" placeholder="阿里云AccessKey Secret">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 高级 --}}
                        <div class="tab-pane" id="tab-advanced" role="tabpanel">
                            <h4 class="mb-3">自定义代码</h4>

                            <div class="mb-3">
                                <label class="form-label">&lt;head&gt; 自定义代码</label>
                                <textarea name="custom_head_code" rows="6" class="form-control font-monospace small" placeholder="<meta name=...> 或 <link rel=...>">{{ $settings['custom_head_code'] ?? '' }}</textarea>
                                <div class="form-hint">将被插入到每个页面的 &lt;/head&gt; 标签前，可用于添加自定义 meta、CSS 等</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">&lt;body&gt; 自定义代码</label>
                                <textarea name="custom_body_code" rows="6" class="form-control font-monospace small" placeholder="<script>...">{{ $settings['custom_body_code'] ?? '' }}</textarea>
                                <div class="form-hint">将被插入到每个页面的 &lt;/body&gt; 标签前，可用于添加自定义 JavaScript</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>保存所有设置
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        {{-- 快捷操作 --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-bolt me-1"></i>快捷操作</h3>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-warning" data-site-settings-action="clear-cache">
                        <i class="ti ti-refresh me-1"></i>清除系统缓存
                    </button>
                    <button type="button" class="btn btn-outline-danger" data-site-settings-action="sanitize-head-snippets">
                        <i class="ti ti-sparkles me-1"></i>清理头部异常代码
                    </button>
                    <button type="button" class="btn btn-outline-info" data-site-settings-action="export-settings">
                        <i class="ti ti-download me-1"></i>导出设置备份
                    </button>
                    @php $isDown = file_exists(storage_path('framework/down')); @endphp
                    <button type="button" class="btn {{ $isDown ? 'btn-success' : 'btn-outline-secondary' }}" onclick="fetch('{{ route('admin.site-settings.toggle-maintenance') }}',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}}).then(r=>r.json()).then(d=>location.reload())">
                        <i class="ti ti-{{ $isDown ? 'player-play' : 'tools' }} me-1"></i>{{ $isDown ? '关闭维护模式' : '开启维护模式' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">说明</h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">- 设置项会保存在数据库并自动缓存。</li>
                    <li class="mb-2">- 站点名称会展示在后台导航栏。</li>
                    <li>- 如需前台展示，可按同样方式读取设置项。</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">最近配置变更</h3>
            </div>
            <div class="card-body p-0">
                @if(($recentAuditLogs ?? collect())->isEmpty())
                    <div class="p-3 text-secondary">暂无变更记录</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-vcenter mb-0">
                            <thead>
                                <tr>
                                    <th>键名</th>
                                    <th>操作者</th>
                                    <th>时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentAuditLogs as $log)
                                    <tr>
                                        <td>
                                            <div>{{ $log->setting_key }}</div>
                                            <div class="text-secondary small text-truncate" style="max-width: 220px;">
                                                {{ ($log->old_value ?? 'NULL') }} → {{ ($log->new_value ?? 'NULL') }}
                                            </div>
                                        </td>
                                        <td>{{ $log->changedByUser?->name ?? '系统' }}</td>
                                        <td class="text-secondary small">{{ $log->created_at?->format('m-d H:i:s') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
@php($siteSettingsScriptVersion = @filemtime(public_path('js/pages/admin-site-settings-index-2.js')) ?: time())
<script src="{{ asset('js/pages/admin-site-settings-index-2.js') }}?v={{ $siteSettingsScriptVersion }}"></script>
@endpush
