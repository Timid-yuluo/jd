@extends('layouts.user')

@section('title', '优化对比 - ' . $resume->title)

@section('page-pretitle', '简历管理')
@section('page-title', '优化前后对比')

@section('page-actions')
<div class="d-flex gap-2">
    <a href="{{ route('user.resumes.editor', $resume) }}" class="btn btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i>返回编辑器
    </a>
    <button type="button" class="btn btn-outline-info" onclick="window.print()">
        <i class="ti ti-printer me-1"></i>打印报告
    </button>
</div>
@endsection

@section('content')
@php $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP; @endphp
<div id="optCompareApp">
    <script type="application/json" id="optData">{!! json_encode(['beforeModules' => $beforeModules, 'afterModules' => $afterModules, 'highlights' => $highlights, 'riskTips' => $riskTips, 'applyUrl' => route('user.resumes.optimize-compare.apply', [$resume, $session]), 'csrf' => csrf_token(), 'updatedAt' => $resumeUpdatedAt], $jsonFlags) !!}</script>
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-green-lt">已完成</span>
                    @php $sc = is_array($session->config ?? null) ? $session->config : []; @endphp
                    @if(!empty($sc['target_job']))<span class="text-muted small">目标岗位：<strong>{{ $sc['target_job'] }}</strong></span>@endif
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm" id="optSelectAllBtn">全选</button>
                    <button class="btn btn-outline-secondary btn-sm" id="optClearAllBtn">清空</button>
                    <button class="btn btn-primary btn-sm" id="optApplyBtn" disabled>
                        <i class="ti ti-check me-1"></i>应用选中 (<span id="optSelectedCount">0</span>)
                    </button>
                </div>
            </div>

            <div class="row g-3" id="optModuleList"></div>
        </div>
    </div>
</div>
@endsection

<style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
@media print {
    .page-header, .btn, [data-action] { display: none !important; }
    .page-body { padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; break-inside: avoid; }
    body { font-size: 12px; }
}
</style>

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
(function() {
    var rawData = JSON.parse(document.getElementById('optData').textContent);
    var beforeModules = rawData.beforeModules || [];
    var afterModules = rawData.afterModules || [];
    var highlights = rawData.highlights || [];
    var riskTips = rawData.riskTips || [];
    var applyUrl = rawData.applyUrl || '';
    var csrfToken = rawData.csrf || '';
    var updatedAt = rawData.updatedAt || null;

    function norm(modules) {
        var result = [], count = {};
        (modules || []).forEach(function(m) {
            var type = (m.type || '').trim();
            if (!type) return;
            count[type] = (count[type] || 0) + 1;
            var key = m.module_key || (type + ':' + count[type]);
            var data = (m.data && typeof m.data === 'object') ? m.data : {};
            var title = (data.title || (type === 'personal' ? '个人信息' : type)).trim();
            var lines = [];
            if (type === 'personal') {
                [data.name, data.phone, data.email, data.location].forEach(function(v) { if (v) lines.push(v); });
            }
            Object.keys(data).forEach(function(k) {
                if (['title','subtitle','date','location','sort_order','module_key','id','uuid','created_at','updated_at'].indexOf(k) >= 0) return;
                var val = data[k];
                if (Array.isArray(val)) { val.forEach(function(item) { if (typeof item === 'string' && item.trim()) lines.push(item); }); }
                else if (typeof val === 'string' && val.trim() && lines.indexOf(val.trim()) < 0) lines.push(val.trim());
            });
            result.push({ key: key, type: type, title: title, lines: lines, text: lines.join('\n') });
        });
        return result;
    }

    var before = norm(beforeModules), after = norm(afterModules);
    var beforeMap = {}, afterMap = {};
    before.forEach(function(r) { beforeMap[r.key] = r; });
    after.forEach(function(r) { afterMap[r.key] = r; });
    var allKeys = after.map(function(r) { return r.key; });
    before.forEach(function(r) { if (afterMap[r.key]) return; allKeys.push(r.key); });

    var rows = allKeys.map(function(key) {
        var b = beforeMap[key], a = afterMap[key];
        var bText = b ? b.text : '', aText = a ? a.text : '';
        var changed = bText !== aText;
        var title = (a || b).title;
        var type = (a || b).type;
        var isHighRisk = type === 'personal' || /姓名|电话|邮箱|个人信息/.test(title);
        return { key: key, title: title, type: type, changed: changed, highRisk: isHighRisk,
                 bLines: b ? b.lines : [], aLines: a ? a.lines : [],
                 bText: bText, aText: aText };
    });

    var selected = {};
    rows.forEach(function(r) { if (r.changed && !r.highRisk) selected[r.key] = true; });

    function render() {
        var container = document.getElementById('optModuleList');
        container.innerHTML = '';
        rows.forEach(function(row) {
            var col = document.createElement('div');
            col.className = 'col-12';
            var isChecked = !!selected[row.key];
            var badge = row.changed
                ? (row.highRisk ? '<span class="badge bg-warning-lt text-warning ms-2">高风险</span>' : '<span class="badge bg-green-lt text-green ms-2">有变化</span>')
                : '<span class="badge bg-secondary-lt ms-2">无变化</span>';
            var borderClass = isChecked ? 'border-primary' : '';
            var bgClass = row.highRisk ? 'bg-warning-lt' : '';

            col.innerHTML = '<div class="border rounded p-3 ' + borderClass + ' ' + bgClass + '">'
                + '<div class="d-flex align-items-center mb-2">'
                + (row.changed ? '<input type="checkbox" class="form-check-input me-2 opt-check" data-key="' + row.key + '"' + (isChecked ? ' checked' : '') + '>' : '')
                + '<strong>' + row.title + '</strong>' + badge
                + '</div>'
                + '<div class="row g-2"><div class="col-md-6"><div class="small text-muted mb-1">原文</div><div class="small bg-light-subtle rounded p-2" style="white-space:pre-wrap;max-height:200px;overflow:auto;">' + (row.bText || '<em class="text-muted">无</em>') + '</div></div>'
                + '<div class="col-md-6"><div class="small text-muted mb-1">优化后</div><div class="small rounded p-2" style="white-space:pre-wrap;max-height:200px;overflow:auto;background:var(--tblr-bg-surface);">' + diffHtml(row.bText, row.aText) + '</div></div></div>'
                + '</div>';
            container.appendChild(col);
        });
        updateCount();
        bindChecks();
    }

    function diffHtml(before, after) {
        if (!after) return '<em class="text-muted">无</em>';
        if (!before) return '<span style="color:#16a34a;">' + esc(after) + '</span>';
        if (before === after) return esc(after);
        var bLines = before.split('\n'), aLines = after.split('\n');
        var result = [];
        aLines.forEach(function(line, i) {
            if (i < bLines.length && line === bLines[i]) { result.push(esc(line)); }
            else { result.push('<span style="background:#dcfce7;color:#166534;padding:1px 3px;border-radius:3px;">' + esc(line) + '</span>'); }
        });
        return result.join('\n');
    }

    function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function bindChecks() {
        document.querySelectorAll('.opt-check').forEach(function(cb) {
            cb.addEventListener('change', function() {
                var key = this.getAttribute('data-key');
                if (this.checked) selected[key] = true; else delete selected[key];
                var card = this.closest('.border.rounded');
                if (card) card.className = card.className.replace(/border-primary/g, '') + (selected[key] ? ' border-primary' : '');
                updateCount();
            });
        });
    }

    function updateCount() {
        var count = Object.keys(selected).length;
        document.getElementById('optSelectedCount').textContent = count;
        document.getElementById('optApplyBtn').disabled = count === 0;
    }

    document.getElementById('optSelectAllBtn').addEventListener('click', function() { rows.forEach(function(r) { if (r.changed) selected[r.key] = true; }); render(); });
    document.getElementById('optClearAllBtn').addEventListener('click', function() { selected = {}; render(); });
    document.getElementById('optApplyBtn').addEventListener('click', function() {
        var keys = Object.keys(selected);
        if (keys.length === 0) return;
        var selections = keys.map(function(k) { return { module_key: k, action: 'replace' }; });
        var hasHighRisk = rows.some(function(r) { return selected[r.key] && r.highRisk; });

        showConfirmModal(selections, keys, hasHighRisk);
    });

    function showConfirmModal(selections, keys, hasHighRisk) {
        var modal = document.createElement('div');
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:9999;display:flex;align-items:center;justify-content:center;';
        modal.innerHTML = '<div style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);"></div>'
            + '<div style="position:relative;background:#fff;border-radius:12px;width:420px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,0.18);overflow:hidden;">'
            + '<div style="padding:24px 24px 16px;"><h5 style="margin:0;font-size:1rem;">确认应用优化内容</h5><p style="margin:8px 0 0;color:#666;font-size:0.85rem;">即将替换 <strong style="color:#0ea5e9;">' + keys.length + '</strong> 个模块的内容' + (hasHighRisk ? '，包含<strong style="color:#f59e0b;">高风险模块</strong>' : '') + '。</p>'
            + (hasHighRisk ? '<div style="margin-top:12px;padding:10px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:0.8rem;color:#92400e;">⚠️ 包含个人信息等高风险模块，请确认后继续。</div>' : '')
            + '<div style="margin-top:12px;max-height:160px;overflow:auto;background:#f9fafb;border-radius:8px;padding:10px 12px;">' + selectedRowsSummary(keys) + '</div></div>'
            + '<div style="padding:12px 24px;background:#f9fafb;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:8px;">'
            + '<button style="padding:6px 16px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#374151;cursor:pointer;font-size:0.85rem;" id="optModalCancel">取消</button>'
            + '<button style="padding:6px 16px;border:none;border-radius:6px;background:#0ea5e9;color:#fff;cursor:pointer;font-size:0.85rem;" id="optModalConfirm">确认应用</button>'
            + '</div></div>';
        document.body.appendChild(modal);

        modal.querySelector('#optModalCancel').addEventListener('click', function() { modal.remove(); });
        modal.querySelector('#optModalConfirm').addEventListener('click', function() {
            modal.remove();
            doApply(selections, keys);
        });
        modal.addEventListener('click', function(e) { if (e.target === modal) modal.remove(); });
    }

    function selectedRowsSummary(keys) {
        var selectedRows = rows.filter(function(r) { return keys.indexOf(r.key) >= 0; });
        return '<table style="width:100%;font-size:0.8rem;border-collapse:collapse;">'
            + selectedRows.map(function(r) {
                var tag = r.highRisk ? '<span style="background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:4px;font-size:0.7rem;">高风险</span>'
                    : '<span style="background:#dcfce7;color:#166534;padding:2px 6px;border-radius:4px;font-size:0.7rem;">有变化</span>';
                return '<tr><td style="padding:4px 8px;border-bottom:1px solid #e5e7eb;">' + r.title + '</td><td style="padding:4px 8px;border-bottom:1px solid #e5e7eb;text-align:right;">' + tag + '</td></tr>';
            }).join('')
            + '</table>';
    }

    function doApply(selections, keys) {
        var btn = document.getElementById('optApplyBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 应用中...';

        fetch(applyUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ resume_updated_at: updatedAt, selections: selections })
        }).then(function(resp) {
            return resp.text().then(function(text) {
                if (!resp.ok) { throw new Error('HTTP ' + resp.status + ': ' + text.substring(0, 200)); }
                var result;
                try { result = JSON.parse(text); } catch(e) { throw new Error('无效响应'); }
                if (!result.success) throw new Error(result.message || '应用失败');
                window.location.href = result.redirect_url;
            });
        }).catch(function(err) {
            alert('应用失败: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check me-1"></i>应用选中 (<span id="optSelectedCount">' + keys.length + '</span>)';
        });
    }

    render();
})();
</script>
@endpush
