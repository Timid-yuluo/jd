/**
 * Theme Switcher - 深色/浅色主题切换控制器
 * 支持：localStorage 持久化、系统主题监听、键盘快捷键、右键菜单
 */
(function () {
    'use strict';

    const CONFIG = {
        STORAGE_KEY: 'theme-preference',
        THEME_ATTR: 'data-theme',
        BS_THEME_ATTR: 'data-bs-theme',
        MENU_CLASS: 'theme-toggle-menu',
        MENU_OPEN_CLASS: 'show',
        DEBOUNCE_MS: 100,
    };

    const LABELS = {
        light: '浅色',
        dark: '深色',
        auto: '自动',
    };

    const ICONS = {
        light: 'bi-sun-fill',
        dark: 'bi-moon-fill',
        auto: 'bi-circle-half',
    };

    const html = document.documentElement;
    let mediaQuery = null;
    let debounceTimer = null;

    /* ===================== 核心工具函数 ===================== */

    function getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function getStoredTheme() {
        try {
            return localStorage.getItem(CONFIG.STORAGE_KEY);
        } catch {
            return null;
        }
    }

    function setStoredTheme(theme) {
        try {
            localStorage.setItem(CONFIG.STORAGE_KEY, theme);
        } catch {
            /* 忽略私有模式下的存储异常 */
        }
    }

    function resolveTheme(preference) {
        if (preference === 'auto') {
            return getSystemTheme();
        }
        return preference === 'dark' ? 'dark' : 'light';
    }

    /* ===================== 主题应用 ===================== */

    function applyTheme(preference) {
        const resolved = resolveTheme(preference);
        html.setAttribute(CONFIG.THEME_ATTR, preference);
        html.setAttribute(CONFIG.BS_THEME_ATTR, resolved);
        updateAllUI(preference);

        // 触发自定义事件，供其他组件监听
        window.dispatchEvent(new CustomEvent('themechange', {
            detail: { preference, resolved },
        }));
    }

    /* ===================== UI 更新 ===================== */

    function updateAllUI(preference) {
        updateToggleButtons(preference);
        updateDropdownMenus(preference);
    }

    function updateToggleButtons(preference) {
        document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
            const iconLight = btn.querySelector('.theme-icon-light');
            const iconDark = btn.querySelector('.theme-icon-dark');
            const iconAuto = btn.querySelector('.theme-icon-auto');
            const label = btn.querySelector('.theme-toggle-label');

            if (iconLight) iconLight.style.display = preference === 'light' ? '' : 'none';
            if (iconDark) iconDark.style.display = preference === 'dark' ? '' : 'none';
            if (iconAuto) iconAuto.style.display = preference === 'auto' ? '' : 'none';
            if (label) label.textContent = LABELS[preference] || LABELS.auto;
        });
    }

    function updateDropdownMenus(preference) {
        document.querySelectorAll('.' + CONFIG.MENU_CLASS).forEach((menu) => {
            menu.querySelectorAll('[data-theme-set]').forEach((opt) => {
                const theme = opt.getAttribute('data-theme-set');
                opt.classList.toggle('active', theme === preference);
            });
        });
    }

    /* ===================== 主题切换 ===================== */

    function cycleTheme() {
        const current = getStoredTheme() || 'auto';
        const next = current === 'light' ? 'dark' : current === 'dark' ? 'auto' : 'light';
        setStoredTheme(next);
        applyTheme(next);
    }

    function setTheme(preference) {
        setStoredTheme(preference);
        applyTheme(preference);
        closeAllMenus();
    }

    /* ===================== 下拉菜单 ===================== */

    function closeAllMenus() {
        document.querySelectorAll('.' + CONFIG.MENU_CLASS).forEach((m) => {
            m.classList.remove(CONFIG.MENU_OPEN_CLASS);
        });
    }

    function toggleMenu(btn) {
        let menu = btn.querySelector('.' + CONFIG.MENU_CLASS);
        if (!menu) {
            menu = btn.parentElement?.querySelector('.' + CONFIG.MENU_CLASS);
        }
        if (!menu) return;

        const isOpen = menu.classList.contains(CONFIG.MENU_OPEN_CLASS);
        closeAllMenus();
        if (!isOpen) {
            menu.classList.add(CONFIG.MENU_OPEN_CLASS);
        }
    }

    function buildDropdownMenu(btn) {
        if (btn.querySelector('.' + CONFIG.MENU_CLASS)) return;

        const menu = document.createElement('div');
        menu.className = CONFIG.MENU_CLASS;

        const current = getStoredTheme() || 'auto';

        Object.entries(LABELS).forEach(([theme, label]) => {
            const opt = document.createElement('button');
            opt.className = 'theme-toggle-option' + (theme === current ? ' active' : '');
            opt.setAttribute('data-theme-set', theme);
            opt.setAttribute('type', 'button');
            opt.innerHTML = `<i class="bi ${ICONS[theme]}"></i><span>${label}</span>`;
            menu.appendChild(opt);
        });

        btn.style.position = 'relative';
        btn.appendChild(menu);
    }

    /* ===================== 系统主题监听 ===================== */

    function initSystemListener() {
        if (mediaQuery) return;

        mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', () => {
            if (!getStoredTheme() || getStoredTheme() === 'auto') {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => applyTheme('auto'), CONFIG.DEBOUNCE_MS);
            }
        });
    }

    /* ===================== 事件绑定 ===================== */

    function initEvents() {
        // 点击切换
        document.addEventListener('click', (e) => {
            const toggle = e.target.closest('[data-theme-toggle]');
            if (toggle) {
                e.preventDefault();
                e.stopPropagation();
                const menu = toggle.querySelector('.' + CONFIG.MENU_CLASS);
                if (menu?.classList.contains(CONFIG.MENU_OPEN_CLASS)) {
                    closeAllMenus();
                } else {
                    cycleTheme();
                }
                return;
            }

            const setter = e.target.closest('[data-theme-set]');
            if (setter) {
                e.preventDefault();
                e.stopPropagation();
                const theme = setter.getAttribute('data-theme-set');
                if (theme in LABELS) {
                    setTheme(theme);
                }
                return;
            }

            if (!e.target.closest('.' + CONFIG.MENU_CLASS)) {
                closeAllMenus();
            }
        });

        // 右键菜单
        document.addEventListener('contextmenu', (e) => {
            const toggle = e.target.closest('[data-theme-toggle]');
            if (toggle) {
                e.preventDefault();
                e.stopPropagation();
                buildDropdownMenu(toggle);
                toggleMenu(toggle);
            }
        });

        // 键盘快捷键 Ctrl+Shift+D
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                cycleTheme();
            }
        });
    }

    /* ===================== 初始化 ===================== */

    function removeFoucFix() {
        const foucStyle = document.getElementById('theme-fouc-fix');
        if (foucStyle) {
            foucStyle.remove();
        }
    }

    function init() {
        const stored = getStoredTheme();
        const preference = stored || 'auto';
        applyTheme(preference);
        initSystemListener();
        initEvents();

        /* dark-mode.css 已加载，移除 FOUC 修复样式 */
        removeFoucFix();
    }

    // DOM 就绪后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
