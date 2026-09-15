@extends('layouts.admin')

@section('title', '外部招聘池')
@section('page-pretitle', '招聘数据')
@section('page-title', '外部招聘池')

@section('content')
<div class="row row-cards mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="h3 mb-0" id="stat-total">{{ $stats['total'] }}</div>
                <div class="text-secondary">总记录</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="h3 mb-0 text-warning" id="stat-pending">{{ $stats['pending'] }}</div>
                <div class="text-secondary">待审核</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="h3 mb-0 text-success" id="stat-approved">{{ $stats['approved'] }}</div>
                <div class="text-secondary">已通过</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="h3 mb-0 text-danger" id="stat-rejected">{{ $stats['rejected'] }}</div>
                <div class="text-secondary">已驳回</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-robot me-1"></i>智能审核</h3>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-auto">
                <span class="badge bg-warning-lt text-warning fs-6" id="stat-pending-inline">{{ $stats['pending'] }}</span>
                <span class="text-secondary ms-1">条待审核</span>
            </div>
            <div class="col-auto">
                <form method="POST" action="{{ route('admin.external-recruitments.auto-review') }}" class="d-inline" id="auto-review-form">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-auto-review">
                        <i class="ti ti-sparkles me-1"></i>智能审核
                    </button>
                </form>
            </div>
            <div class="col-auto">
                <form method="POST" action="{{ route('admin.external-recruitments.approve-all') }}" class="d-inline" id="approve-all-form">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm" id="btn-approve-all">
                        <i class="ti ti-checks me-1"></i>一键全部通过
                    </button>
                </form>
            </div>
            <div class="col">
                <div class="text-secondary small">
                    <strong>智能审核规则：</strong>
                    <span class="badge bg-success-lt text-success me-1">通过</span>有公司名 + 有链接 + 有岗位信息 + 非重复
                    <span class="mx-1">|</span>
                    <span class="badge bg-danger-lt text-danger me-1">驳回</span>无公司名 / 无有效链接 / 无岗位信息 / 重复记录
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">抓取同步</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.external-recruitments.sync') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label">采集来源</label>
                <select class="form-select" name="source">
                    <option value="offerstar">OfferStar</option>
                    <option value="qiuzhifangzhou-campus">求职方舟-校招</option>
                    <option value="qiuzhifangzhou-position">求职方舟-职位流（含社招）</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">抓取页数</label>
                <input type="number" class="form-control" name="pages" min="1" max="50" value="2">
            </div>
            <div class="col-md-2">
                <label class="form-label">抓取天数（校招）</label>
                <input type="number" class="form-control" name="days" min="1" max="180" value="30">
            </div>
            <div class="col-md-2">
                <label class="form-label">页间隔（秒）</label>
                <input type="number" class="form-control" name="delay" min="0" max="10" value="1">
            </div>
            <div class="col-md-3">
                <label class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="auto_approve" value="1">
                    <span class="form-check-label">导入后自动通过</span>
                </label>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">立即同步</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="row g-2 align-items-end w-100">
            <div class="col-md-2">
                <label class="form-label">状态</label>
                <select name="status" class="form-select">
                    <option value="">全部</option>
                    <option value="pending" @selected($status === 'pending')>待审核</option>
                    <option value="approved" @selected($status === 'approved')>已通过</option>
                    <option value="rejected" @selected($status === 'rejected')>已驳回</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">来源</label>
                <select name="source" class="form-select">
                    <option value="" @selected($source === '')>全部</option>
                    <option value="offerstar" @selected($source === 'offerstar')>OfferStar</option>
                    <option value="qiuzhifangzhou-campus" @selected($source === 'qiuzhifangzhou-campus')>求职方舟-校招</option>
                    <option value="qiuzhifangzhou-position" @selected($source === 'qiuzhifangzhou-position')>求职方舟-职位流（含社招）</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">招聘类型</label>
                <select name="recruitment_type" class="form-select">
                    <option value="" @selected(($recruitmentType ?? '') === '')>全部</option>
                    <option value="campus" @selected(($recruitmentType ?? '') === 'campus')>校招</option>
                    <option value="social" @selected(($recruitmentType ?? '') === 'social')>社招</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">关键词</label>
                <input type="text" class="form-control" name="keyword" value="{{ $keyword }}" placeholder="公司/岗位/行业/地点">
            </div>
            <div class="col-md-2">
                <label class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="today_only" value="1" {{ !empty($todayOnly) ? 'checked' : '' }}>
                    <span class="form-check-label">仅看今日导入</span>
                </label>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">筛选</button>
            </div>
            <div class="col-md-1">
                <a href="{{ route('admin.external-recruitments.index', ['reset' => 1]) }}" class="btn btn-outline-secondary w-100">重置</a>
            </div>
        </form>
    </div>

    {{-- 批量操作栏 --}}
    <div class="card-header border-top-0 pt-0 d-none" id="batch-bar">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="text-secondary">已选 <strong id="selected-count">0</strong> 条</span>
            <button type="button" class="btn btn-sm btn-outline-primary" data-action="select-pending">
                仅选待审核
            </button>
            <div class="btn-group btn-group-sm" role="group" aria-label="备注模板">
                <button type="button" class="btn btn-outline-secondary" data-action="fill-note" data-note="疑似重复岗位">重复岗位</button>
                <button type="button" class="btn btn-outline-secondary" data-action="fill-note" data-note="链接无效或已失效">链接失效</button>
                <button type="button" class="btn btn-outline-secondary" data-action="fill-note" data-note="岗位信息不完整">信息不完整</button>
                <button type="button" class="btn btn-outline-secondary" data-action="fill-note" data-note="与平台定位不符">低相关性</button>
            </div>
            <input type="text" id="batch-note-input" class="form-control form-control-sm" style="width: 260px;" placeholder="批量备注（可选）">
            <button type="submit" form="batch-review-form" formaction="{{ route('admin.external-recruitments.batch-approve') }}" class="btn btn-sm btn-success" data-action="batch-submit" data-batch-action="approve">
                <i class="ti ti-check me-1"></i>批量通过
            </button>
            <button type="submit" form="batch-review-form" formaction="{{ route('admin.external-recruitments.batch-reject') }}" class="btn btn-sm btn-danger" data-action="batch-submit" data-batch-action="reject">
                <i class="ti ti-x me-1"></i>批量驳回
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-action="clear-selection">取消选择</button>
        </div>
    </div>

    <form id="batch-review-form" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="review_note" id="batch-review-note" value="">
        <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
    </form>

    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th style="width: 36px;">
                        <input type="checkbox" class="form-check-input" id="select-all" data-action="toggle-select-all">
                    </th>
                    <th>公司/岗位</th>
                    <th>地点</th>
                    <th>行业</th>
                    <th>类型</th>
                    <th>投递/公告</th>
                    <th>状态</th>
                    <th>导入时间</th>
                    <th class="text-end">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recruitments as $item)
                    <tr data-id="{{ $item->id }}" data-status="{{ $item->review_status }}" class="{{ $item->review_status === 'pending' ? 'bg-warning-lt' : '' }}">
                        <td>
                            <input
                                type="checkbox"
                                class="form-check-input row-checkbox"
                                name="ids[]"
                                form="batch-review-form"
                                value="{{ $item->id }}"
                                data-action="update-batch-bar"
                                @disabled($item->review_status !== 'pending')
                                title="{{ $item->review_status !== 'pending' ? '仅待审核记录可批量操作' : '' }}"
                            >
                        </td>
                        <td>
                            <div class="fw-bold">{{ $item->company ?: '-' }}</div>
                            <div class="text-secondary small">{{ $item->positions ?: $item->title }}</div>
                        </td>
                        <td>{{ $item->work_location ?: '-' }}</td>
                        <td>{{ $item->industry ?: '-' }}</td>
                        <td>
                            <span class="badge {{ $item->recruitment_type === 'campus' ? 'bg-primary-lt text-primary' : 'bg-success-lt text-success' }}">
                                {{ $item->recruitment_type === 'campus' ? '校招' : '社招' }}
                            </span>
                        </td>
                        <td>
                            @php
                                $isQzfSource = str_starts_with((string) $item->source_name, 'qiuzhifangzhou-');
                                $isQzfUrl = static function (?string $url): bool {
                                    $url = trim((string) $url);
                                    if ($url === '') {
                                        return false;
                                    }
                                    $host = parse_url($url, PHP_URL_HOST);
                                    if (!is_string($host) || $host === '') {
                                        return false;
                                    }
                                    $host = mb_strtolower($host);
                                    return $host === 'qiuzhifangzhou.com'
                                        || $host === 'www.qiuzhifangzhou.com'
                                        || $host === 'api.qiuzhifangzhou.com'
                                        || str_ends_with($host, '.qiuzhifangzhou.com');
                                };
                                $adminApplyLink = $item->apply_url ?: null;
                                $adminAnnouncementLink = $item->announcement_url ?: null;
                                $adminSourceLink = $item->source_url ?: null;
                                if ($isQzfSource) {
                                    if ($adminApplyLink !== null && $isQzfUrl($adminApplyLink)) {
                                        $adminApplyLink = null;
                                    }
                                    if ($adminAnnouncementLink !== null && $isQzfUrl($adminAnnouncementLink)) {
                                        $adminAnnouncementLink = null;
                                    }
                                }
                                if ($adminApplyLink === null) {
                                    $adminApplyLink = $adminAnnouncementLink ?: ($isQzfSource ? null : $adminSourceLink);
                                }
                            @endphp
                            <div class="d-flex flex-column gap-1">
                                @if($adminApplyLink)
                                    <a href="{{ $adminApplyLink }}" target="_blank" rel="noopener">投递</a>
                                @endif
                                @if($adminAnnouncementLink && $adminAnnouncementLink !== $adminApplyLink)
                                    <a href="{{ $adminAnnouncementLink }}" target="_blank" rel="noopener">公告</a>
                                @endif
                                @if(!$adminApplyLink && !$adminAnnouncementLink)
                                    <span class="text-secondary">-</span>
                                @endif
                            </div>
                        </td>
                        <td class="status-cell">
                            @if($item->review_status === 'approved')
                                <span class="badge bg-success-lt text-success">已通过</span>
                            @elseif($item->review_status === 'rejected')
                                <span class="badge bg-danger-lt text-danger">已驳回</span>
                                @if($item->review_note)
                                    <i class="ti ti-info-circle text-secondary ms-1" title="{{ $item->review_note }}"></i>
                                @endif
                            @else
                                <span class="badge bg-warning-lt text-warning">待审核</span>
                            @endif
                        </td>
                        <td>{{ $item->imported_at?->format('Y-m-d H:i') ?: '-' }}</td>
                        <td class="text-end actions-cell">
                            @if($item->review_status === 'pending')
                                <button type="button" class="btn btn-sm btn-outline-success" data-action="single-approve" data-id="{{ $item->id }}">
                                    <i class="ti ti-check me-1"></i>通过
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-action="show-reject-modal" data-id="{{ $item->id }}">
                                    <i class="ti ti-x me-1"></i>驳回
                                </button>
                            @elseif($item->review_status === 'approved')
                                <button type="button" class="btn btn-sm btn-outline-danger" data-action="show-reject-modal" data-id="{{ $item->id }}">驳回</button>
                            @elseif($item->review_status === 'rejected')
                                <button type="button" class="btn btn-sm btn-outline-success" data-action="single-approve" data-id="{{ $item->id }}">通过</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-secondary py-4">暂无数据，请先执行同步。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $recruitments->links() }}
    </div>
</div>

{{-- 驳回弹窗 --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">驳回原因</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">备注（可选）</label>
                    <textarea class="form-control" id="reject-note" rows="3" placeholder="请输入驳回原因..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirm-reject-btn">确认驳回</button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
(function() {
    let rejectTarget = { id: null };

    // ===== 智能审核 / 一键全部通过 =====
    var btnAutoReview = document.getElementById('btn-auto-review');
    var btnApproveAll = document.getElementById('btn-approve-all');

    if (btnAutoReview) {
        btnAutoReview.addEventListener('click', function(e) {
            e.preventDefault();
            var pending = parseInt(document.getElementById('stat-pending-inline').textContent) || 0;
            if (pending === 0) { alert('没有待审核记录'); return; }
            if (!confirm('将对 ' + pending + ' 条待审核记录执行智能审核（自动通过符合条件的、驳回不符合条件的），确认执行？')) return;
            document.getElementById('auto-review-form').submit();
        });
    }

    if (btnApproveAll) {
        btnApproveAll.addEventListener('click', function(e) {
            e.preventDefault();
            var pending = parseInt(document.getElementById('stat-pending-inline').textContent) || 0;
            if (pending === 0) { alert('没有待审核记录'); return; }
            if (!confirm('确认将 ' + pending + ' 条待审核记录全部通过？此操作不可撤销！')) return;
            document.getElementById('approve-all-form').submit();
        });
    }

    // ===== 事件委托 =====
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        var action = btn.dataset.action;

        switch (action) {
            case 'single-approve':
                singleApprove(parseInt(btn.dataset.id));
                break;
            case 'show-reject-modal':
                showRejectModal(parseInt(btn.dataset.id));
                break;
            case 'select-pending':
                selectPendingRows();
                break;
            case 'fill-note':
                fillBatchNoteTemplate(btn.dataset.note);
                break;
            case 'batch-submit':
                if (!confirmBatchSubmit(btn.dataset.batchAction)) {
                    e.preventDefault();
                }
                break;
            case 'clear-selection':
                clearSelection();
                break;
            case 'toggle-select-all':
                toggleSelectAll(btn);
                break;
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target.matches('[data-action="update-batch-bar"]')) {
            updateBatchBar();
        }
    });

    // 单条通过
    function singleApprove(id) {
        if (!confirm('确认通过该记录？')) return;
        fetch('{{ route('admin.external-recruitments.approve', ['externalRecruitment' => '__ID__']) }}'.replace('__ID__', id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.message) {
                updateRowStatus(id, 'approved');
                updateStats(data.stats);
            }
        })
        .catch(() => alert('操作失败，请重试'));
    }

    // 显示驳回弹窗
    function showRejectModal(id) {
        rejectTarget = { id: id };
        document.getElementById('reject-note').value = '';
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    }

    // 确认驳回
    document.getElementById('confirm-reject-btn').addEventListener('click', function() {
        const note = document.getElementById('reject-note').value.trim();
        const modal = bootstrap.Modal.getInstance(document.getElementById('rejectModal'));

        singleReject(rejectTarget.id, note, modal);
    });

    function singleReject(id, note, modal) {
        fetch('{{ route('admin.external-recruitments.reject', ['externalRecruitment' => '__ID__']) }}'.replace('__ID__', id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ review_note: note })
        })
        .then(r => r.json())
        .then(data => {
            if (data.message) {
                modal.hide();
                updateRowStatus(id, 'rejected', note);
                updateStats(data.stats);
            }
        })
        .catch(() => alert('操作失败，请重试'));
    }

    function confirmBatchSubmit(action) {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            alert('请先选择记录');
            return false;
        }

        const noteInput = document.getElementById('batch-review-note');
        const noteTextInput = document.getElementById('batch-note-input');
        const currentNote = (noteTextInput?.value || '').trim();
        if (action === 'reject') {
            if (currentNote === '') {
                const note = window.prompt('批量驳回备注（可选）', '');
                if (note === null) {
                    return false;
                }
                noteInput.value = note.trim();
                if (noteTextInput) {
                    noteTextInput.value = note.trim();
                }
            } else {
                noteInput.value = currentNote;
            }
        } else {
            noteInput.value = currentNote;
        }

        return confirm(`确认批量${action === 'approve' ? '通过' : '驳回'} ${ids.length} 条记录？`);
    }

    function fillBatchNoteTemplate(text) {
        const noteTextInput = document.getElementById('batch-note-input');
        if (!noteTextInput) {
            return;
        }
        noteTextInput.value = text;
        document.getElementById('batch-review-note').value = text;
    }

    const noteTextInput = document.getElementById('batch-note-input');
    if (noteTextInput) {
        noteTextInput.addEventListener('input', function() {
            document.getElementById('batch-review-note').value = this.value.trim();
        });
    }

    // 更新行状态
    function updateRowStatus(id, status, note) {
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (!row) return;

        row.dataset.status = status;
        row.classList.toggle('bg-warning-lt', status === 'pending');

        const statusCell = row.querySelector('.status-cell');
        if (status === 'approved') {
            statusCell.innerHTML = '<span class="badge bg-success-lt text-success">已通过</span>';
        } else {
            const noteIcon = note ? `<i class="ti ti-info-circle text-secondary ms-1" title="${note}"></i>` : '';
            statusCell.innerHTML = `<span class="badge bg-danger-lt text-danger">已驳回</span>${noteIcon}`;
        }

        const actionsCell = row.querySelector('.actions-cell');
        if (status === 'approved') {
            actionsCell.innerHTML = '<button type="button" class="btn btn-sm btn-outline-danger" data-action="show-reject-modal" data-id="' + id + '">驳回</button>';
        } else {
            actionsCell.innerHTML = '<button type="button" class="btn btn-sm btn-outline-success" data-action="single-approve" data-id="' + id + '">通过</button>';
        }

        // 取消勾选
        const checkbox = row.querySelector('.row-checkbox');
        if (checkbox) checkbox.checked = false;
        updateBatchBar();
    }

    // 更新统计
    function updateStats(stats) {
        if (!stats) return;
        document.getElementById('stat-total').textContent = stats.total;
        document.getElementById('stat-pending').textContent = stats.pending;
        document.getElementById('stat-approved').textContent = stats.approved;
        document.getElementById('stat-rejected').textContent = stats.rejected;
    }

    // 全选
    function toggleSelectAll(el) {
        document.querySelectorAll('.row-checkbox:not(:disabled)').forEach(cb => cb.checked = el.checked);
        updateBatchBar();
    }

    // 仅勾选待审核
    function selectPendingRows() {
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = !cb.disabled;
        });
        updateBatchBar();
    }

    // 更新批量操作栏
    function updateBatchBar() {
        const count = getSelectedIds().length;
        const bar = document.getElementById('batch-bar');
        document.getElementById('selected-count').textContent = count;
        bar.classList.toggle('d-none', count === 0);

        const selectAll = document.getElementById('select-all');
        const total = document.querySelectorAll('.row-checkbox:not(:disabled)').length;
        const checked = document.querySelectorAll('.row-checkbox:not(:disabled):checked').length;
        selectAll.checked = total > 0 && checked === total;
        selectAll.indeterminate = checked > 0 && checked < total;
    }

    function clearSelection() {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('select-all').checked = false;
        document.getElementById('select-all').indeterminate = false;
        updateBatchBar();
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => parseInt(cb.value, 10));
    }
})();
</script>
@endsection
