/**
 * Welcome Page Interactions
 * 轻量交互动效：滚动渐入、统计数字计数
 * 纯原生 JS，零依赖；尊重 prefers-reduced-motion
 */
(function () {
    'use strict';

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ===================== 1. 滚动渐入 (reveal on scroll) ===================== */
    function initReveal() {
        const els = document.querySelectorAll('.reveal');
        if (!els.length) return;

        if (reducedMotion || !('IntersectionObserver' in window)) {
            els.forEach((el) => el.classList.add('reveal-visible'));
            return;
        }

        const observer = new IntersectionObserver(
            (entries, obs) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const el = entry.target;
                        const delay = parseInt(el.dataset.revealDelay || '0', 10);
                        setTimeout(() => el.classList.add('reveal-visible'), delay);
                        obs.unobserve(el);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
        );

        els.forEach((el) => observer.observe(el));
    }

    /* ===================== 2. 统计数字计数 (count-up) ===================== */
    function parseTarget(text) {
        // 支持 "10,000+" "85%" "4.9" "500+"
        const cleaned = text.replace(/[,\s]/g, '').match(/[\d.]+/);
        return cleaned ? parseFloat(cleaned[0]) : null;
    }

    function animateCount(el) {
        const target = parseTarget(el.dataset.count || el.textContent);
        if (target === null || isNaN(target)) return;

        if (reducedMotion) {
            return; // 保留原始文本不动
        }

        const duration = 1400;
        const start = performance.now();
        const isDecimal = target % 1 !== 0;
        const original = el.dataset.countOriginal || el.textContent;
        const prefix = (original.match(/^[^\d.]*/) || [''])[0];
        const suffix = (original.match(/[^\d.]*$/) || [''])[0];

        function frame(now) {
            const progress = Math.min((now - start) / duration, 1);
            // easeOutCubic
            const eased = 1 - Math.pow(1 - progress, 3);
            const value = target * eased;
            const display = isDecimal ? value.toFixed(1) : Math.floor(value).toLocaleString('en-US');
            el.textContent = prefix + display + suffix;
            if (progress < 1) {
                requestAnimationFrame(frame);
            } else {
                el.textContent = original; // 确保最终值与后缀(+)完全一致
            }
        }
        requestAnimationFrame(frame);
    }

    function initCountUp() {
        const nums = document.querySelectorAll('[data-count]');
        if (!nums.length) return;

        if (reducedMotion || !('IntersectionObserver' in window)) {
            return; // 不动画
        }

        const observer = new IntersectionObserver(
            (entries, obs) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        animateCount(entry.target);
                        obs.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.5 }
        );
        nums.forEach((el) => observer.observe(el));
    }

    /* ===================== 3. 导航栏滚动阴影 ===================== */
    function initNavbarScroll() {
        var nav = document.getElementById('mainNav');
        if (!nav) return;
        var toggle = function () {
            if (window.scrollY > 20) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        };
        window.addEventListener('scroll', toggle, { passive: true });
        toggle();
    }

    /* ===================== 启动 ===================== */
    function init() {
        initNavbarScroll();
        initReveal();
        initCountUp();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
