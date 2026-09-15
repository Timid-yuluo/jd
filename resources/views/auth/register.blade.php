@extends('layouts.auth')

@section('title', '注册')

@if(!empty($turnstileSiteKey))
@push('auth-head')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endpush
@endif
@push('auth-head')
    <style>
        .pw-strength-bar {
            height: 4px;
            border-radius: 2px;
            background: var(--color-border-primary);
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .pw-strength-bar-fill {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        .pw-checklist {
            list-style: none;
            padding: 0;
            margin: 0.5rem 0 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.25rem 0.75rem;
        }
        .pw-checklist li {
            font-size: 0.78rem;
            color: var(--color-text-muted);
            display: flex;
            align-items: center;
            gap: 0.35rem;
            transition: color 0.2s ease;
        }
        .pw-checklist li .bi {
            font-size: 0.85rem;
            transition: color 0.2s ease;
        }
        .pw-checklist li.pw-met {
            color: var(--color-success-text, #16a34a);
        }
        .pw-checklist li.pw-met .bi {
            color: var(--color-success-text, #16a34a);
        }
        .email-suggest-wrap {
            position: relative;
        }
        .email-suggest-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 100;
            background: var(--color-bg-card, #fff);
            border: 1.5px solid var(--color-border-primary);
            border-top: none;
            border-radius: 0 0 0.75rem 0.75rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }
        .email-suggest-list.show {
            display: block;
        }
        .email-suggest-item {
            padding: 0.5rem 0.875rem;
            font-size: 0.9rem;
            color: var(--color-text-primary);
            cursor: pointer;
            transition: background 0.15s;
        }
        .email-suggest-item:hover,
        .email-suggest-item.active {
            background: var(--color-bg-hover, #f0f9ff);
            color: var(--color-accent, #0ea5e9);
        }
        .email-suggest-item .domain-part {
            color: var(--color-accent, #0ea5e9);
            font-weight: 600;
        }
        .email-suggest-item .local-part {
            color: var(--color-text-muted);
        }
        .pw-toggle-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--color-text-muted);
            cursor: pointer;
            padding: 0.25rem;
            font-size: 1rem;
            line-height: 1;
        }
        .pw-toggle-btn:hover {
            color: var(--color-text-secondary);
        }
        .pw-input-wrap {
            position: relative;
        }
        .pw-input-wrap .auth-form-control {
            padding-right: 2.5rem;
        }
    </style>
@endpush

@section('auth-nav-action')
    <a href="{{ route('login') }}" class="auth-btn-outline text-decoration-none">登录</a>
@endsection

@section('auth-content')
    <div class="auth-wrapper">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-card-header">
                    <div class="auth-card-icon">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h2 class="auth-card-title">创建账号</h2>
                    <p class="auth-card-subtitle">加入 {{ $siteName }}，开启智能求职之旅</p>
                </div>

                <div class="auth-card-body">
                    @if ($errors->any())
                        <div class="auth-alert auth-alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.store') }}" autocomplete="off" novalidate id="registerForm">
                        @csrf

                        <div class="auth-form-group">
                            <label class="auth-form-label">姓名</label>
                            <input type="text" name="name" class="auth-form-control w-100" value="{{ old('name') }}" placeholder="请输入你的姓名" required autofocus>
                        </div>

                        <div class="auth-form-group">
                            <label class="auth-form-label">邮箱地址</label>
                            <div class="email-suggest-wrap">
                                <input type="email" name="email" id="emailInput" class="auth-form-control w-100" value="{{ old('email') }}" placeholder="请输入邮箱地址" required autocomplete="off">
                                <div class="email-suggest-list" id="emailSuggestList"></div>
                            </div>
                        </div>

                        <div class="auth-form-row">
                            <div class="auth-form-group">
                                <label class="auth-form-label">学校（选填）</label>
                                <input type="text" name="school" class="auth-form-control w-100" value="{{ old('school') }}" placeholder="就读学校">
                            </div>
                            <div class="auth-form-group">
                                <label class="auth-form-label">专业（选填）</label>
                                <input type="text" name="major" class="auth-form-control w-100" value="{{ old('major') }}" placeholder="所学专业">
                            </div>
                        </div>

                        <div class="auth-form-group">
                            <label class="auth-form-label">密码</label>
                            <div class="pw-input-wrap">
                                <input type="password" name="password" id="passwordInput" class="auth-form-control w-100" placeholder="请设置密码" required>
                                <button type="button" class="pw-toggle-btn" id="pwToggleBtn" tabindex="-1" aria-label="显示密码">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="pw-strength-bar"><div class="pw-strength-bar-fill" id="pwStrengthFill"></div></div>
                            <ul class="pw-checklist" id="pwChecklist">
                                <li data-rule="length"><i class="bi bi-circle"></i> 至少 {{ $passwordMinLength }} 位</li>
                                <li data-rule="upper"><i class="bi bi-circle"></i> 包含大写字母</li>
                                <li data-rule="lower"><i class="bi bi-circle"></i> 包含小写字母</li>
                                <li data-rule="number"><i class="bi bi-circle"></i> 包含数字</li>
                                <li data-rule="symbol"><i class="bi bi-circle"></i> 包含符号</li>
                            </ul>
                        </div>

                        <div class="auth-form-group">
                            <label class="auth-form-label">确认密码</label>
                            <div class="pw-input-wrap">
                                <input type="password" name="password_confirmation" id="pwConfirmInput" class="auth-form-control w-100" placeholder="请再次输入密码" required>
                                <button type="button" class="pw-toggle-btn" id="pwConfirmToggleBtn" tabindex="-1" aria-label="显示密码">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div id="pwMatchHint" style="font-size:0.78rem;margin-top:0.3rem;display:none;"></div>
                        </div>

                        @if(!empty($turnstileSiteKey))
                        <div class="auth-form-group d-flex justify-content-center">
                            <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="auto"></div>
                        </div>
                        @endif

                        <button type="submit" class="auth-btn-primary w-100">
                            <i class="bi bi-person-plus me-2"></i>注册
                        </button>
                    </form>
                </div>

                <div class="auth-card-footer">
                    <p class="auth-footer-text mb-0">
                        已有账号？<a href="{{ route('login') }}">返回登录</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('auth-scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
(function() {
    const MIN_LEN = {{ $passwordMinLength }};

    // ==================== Password Toggle ====================
    function setupPwToggle(inputId, btnId) {
        const input = document.getElementById(inputId);
        const btn = document.getElementById(btnId);
        if (!input || !btn) return;
        btn.addEventListener('click', function() {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.querySelector('i').className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }
    setupPwToggle('passwordInput', 'pwToggleBtn');
    setupPwToggle('pwConfirmInput', 'pwConfirmToggleBtn');

    // ==================== Password Realtime Check ====================
    const pwInput = document.getElementById('passwordInput');
    const pwConfirm = document.getElementById('pwConfirmInput');
    const checklist = document.getElementById('pwChecklist');
    const strengthFill = document.getElementById('pwStrengthFill');
    const matchHint = document.getElementById('pwMatchHint');

    const rules = {
        length: function(v) { return v.length >= MIN_LEN; },
        upper:  function(v) { return /[A-Z]/.test(v); },
        lower:  function(v) { return /[a-z]/.test(v); },
        number: function(v) { return /[0-9]/.test(v); },
        symbol: function(v) { return /[^A-Za-z0-9]/.test(v); },
    };

    const strengthColors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'];

    function updateChecklist() {
        const val = pwInput.value;
        let passed = 0;
        checklist.querySelectorAll('li[data-rule]').forEach(function(li) {
            const rule = li.dataset.rule;
            const ok = val.length > 0 && rules[rule](val);
            li.classList.toggle('pw-met', ok);
            li.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
            if (ok) passed++;
        });
        // strength bar
        const pct = val.length === 0 ? 0 : Math.min(passed / 5 * 100, 100);
        strengthFill.style.width = pct + '%';
        if (val.length === 0) {
            strengthFill.style.backgroundColor = 'transparent';
        } else {
            const idx = Math.max(0, Math.min(passed - 1, 4));
            strengthFill.style.backgroundColor = strengthColors[idx];
        }
    }

    function checkMatch() {
        const val = pwConfirm.value;
        if (val.length === 0) {
            matchHint.style.display = 'none';
            return;
        }
        matchHint.style.display = 'block';
        if (val === pwInput.value) {
            matchHint.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#16a34a"></i> 密码一致';
            matchHint.style.color = '#16a34a';
        } else {
            matchHint.innerHTML = '<i class="bi bi-x-circle-fill" style="color:#ef4444"></i> 两次密码不一致';
            matchHint.style.color = '#ef4444';
        }
    }

    pwInput.addEventListener('input', function() {
        updateChecklist();
        if (pwConfirm.value.length > 0) checkMatch();
    });
    pwConfirm.addEventListener('input', checkMatch);

    // ==================== Email Domain Suggest ====================
    const emailInput = document.getElementById('emailInput');
    const suggestList = document.getElementById('emailSuggestList');
    const domains = ['qq.com', '163.com', '126.com', 'gmail.com', 'foxmail.com', 'outlook.com', 'hotmail.com', 'sina.com', 'yeah.net', 'sohu.com', 'icloud.com', 'edu.cn'];
    let activeIdx = -1;

    function getLocalPart(val) {
        const atIdx = val.indexOf('@');
        return atIdx === -1 ? val : val.substring(0, atIdx);
    }

    function getDomainPart(val) {
        const atIdx = val.indexOf('@');
        return atIdx === -1 ? '' : val.substring(atIdx + 1).toLowerCase();
    }

    function renderSuggestions() {
        const val = emailInput.value.trim();
        if (val.length === 0) {
            hideSuggestions();
            return;
        }

        const local = getLocalPart(val);
        const hasAt = val.includes('@');
        const typed = hasAt ? getDomainPart(val) : '';

        if (hasAt && local.length === 0) {
            hideSuggestions();
            return;
        }

        var filtered;
        if (hasAt) {
            filtered = domains.filter(function(d) {
                return d.startsWith(typed) && d !== typed;
            });
        } else {
            filtered = domains;
        }

        if (filtered.length === 0) {
            hideSuggestions();
            return;
        }

        suggestList.innerHTML = filtered.map(function(d, i) {
            return '<div class="email-suggest-item' + (i === activeIdx ? ' active' : '') + '" data-domain="' + d + '">'
                + '<span class="local-part">' + local + '</span>@<span class="domain-part">' + d + '</span></div>';
        }).join('');
        suggestList.classList.add('show');
    }

    function hideSuggestions() {
        suggestList.classList.remove('show');
        suggestList.innerHTML = '';
        activeIdx = -1;
    }

    function selectDomain(domain) {
        var local = getLocalPart(emailInput.value);
        emailInput.value = local + '@' + domain;
        hideSuggestions();
        emailInput.focus();
    }

    emailInput.addEventListener('input', function() {
        activeIdx = -1;
        renderSuggestions();
    });

    emailInput.addEventListener('keydown', function(e) {
        const items = suggestList.querySelectorAll('.email-suggest-item');
        if (!suggestList.classList.contains('show') || items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = (activeIdx + 1) % items.length;
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = (activeIdx - 1 + items.length) % items.length;
        } else if (e.key === 'Enter' && activeIdx >= 0) {
            e.preventDefault();
            selectDomain(items[activeIdx].dataset.domain);
            return;
        } else if (e.key === 'Tab' && activeIdx >= 0) {
            e.preventDefault();
            selectDomain(items[activeIdx].dataset.domain);
            return;
        } else if (e.key === 'Escape') {
            hideSuggestions();
            return;
        } else {
            return;
        }
        items.forEach(function(el, i) { el.classList.toggle('active', i === activeIdx); });
    });

    suggestList.addEventListener('mousedown', function(e) {
        const item = e.target.closest('.email-suggest-item');
        if (item) {
            e.preventDefault();
            selectDomain(item.dataset.domain);
        }
    });

    emailInput.addEventListener('blur', function() {
        setTimeout(hideSuggestions, 150);
    });

    emailInput.addEventListener('focus', function() {
        if (emailInput.value.trim().length > 0) renderSuggestions();
    });
})();
</script>
@endpush
