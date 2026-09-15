@auth
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
(function() {
    var STORAGE_KEY = 'hq35_guide_dismissed';
    var dismissed = {};
    try { dismissed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch(e) {}

    function isDismissed(id) { return dismissed[id] === true; }
    function markDismissed(id) {
        dismissed[id] = true;
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(dismissed)); } catch(e) {}
    }

    document.querySelectorAll('[data-guide-id]').forEach(function(el) {
        var guideId = el.getAttribute('data-guide-id');
        if (isDismissed(guideId)) return;

        var text = el.getAttribute('data-guide-text') || '';
        var position = el.getAttribute('data-guide-position') || 'bottom';
        if (!text) return;

        var bubble = document.createElement('div');
        bubble.className = 'guide-bubble guide-bubble--' + position;
        bubble.innerHTML = '<div class="guide-bubble-content">' +
            '<p class="mb-2 small">' + text + '</p>' +
            '<div class="d-flex justify-content-end gap-1">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary guide-bubble-dismiss">知道了</button>' +
            '</div></div>' +
            '<div class="guide-bubble-arrow"></div>';

        el.style.position = el.style.position || 'relative';
        el.appendChild(bubble);
        requestAnimationFrame(function() { bubble.classList.add('show'); });

        bubble.querySelector('.guide-bubble-dismiss').addEventListener('click', function(e) {
            e.stopPropagation();
            bubble.classList.remove('show');
            setTimeout(function() { bubble.remove(); }, 300);
            markDismissed(guideId);
        });
    });
})();
</script>
<style>
.guide-bubble {
    position: absolute;
    z-index: 1050;
    opacity: 0;
    transform: translateY(6px);
    transition: opacity .3s, transform .3s;
    pointer-events: none;
    max-width: 280px;
    width: max-content;
}
.guide-bubble.show {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}
.guide-bubble--bottom { top: 100%; left: 50%; transform: translateX(-50%) translateY(6px); margin-top: 8px; }
.guide-bubble--bottom.show { transform: translateX(-50%) translateY(0); }
.guide-bubble--top { bottom: 100%; left: 50%; transform: translateX(-50%) translateY(-6px); margin-bottom: 8px; }
.guide-bubble--top.show { transform: translateX(-50%) translateY(0); }
.guide-bubble--right { left: 100%; top: 50%; transform: translateY(-50%) translateX(6px); margin-left: 8px; }
.guide-bubble--right.show { transform: translateY(-50%) translateX(0); }
.guide-bubble--left { right: 100%; top: 50%; transform: translateY(-50%) translateX(-6px); margin-right: 8px; }
.guide-bubble--left.show { transform: translateY(-50%) translateX(0); }
.guide-bubble-content {
    background: #1e293b;
    color: #f1f5f9;
    border-radius: 8px;
    padding: 10px 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,.2);
}
.guide-bubble-arrow {
    position: absolute;
    width: 0; height: 0;
}
.guide-bubble--bottom .guide-bubble-arrow {
    top: -6px; left: 50%; margin-left: -6px;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-bottom: 6px solid #1e293b;
}
.guide-bubble--top .guide-bubble-arrow {
    bottom: -6px; left: 50%; margin-left: -6px;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid #1e293b;
}
</style>
@endauth
