@extends('layouts.user')

@section('title', '智能岗位推荐')
@section('page-pretitle', '岗位推荐')
@section('page-title', '智能岗位推荐')

@section('page-actions')
    {{-- #19 配额提示 --}}
    <div class="me-2 text-secondary small">
        今日剩余：<span class="badge bg-{{ $remainingQuota > 0 ? 'success' : 'danger' }}-lt">{{ $remainingQuota }}/{{ $dailyQuota }}</span>
    </div>
    <form action="{{ route('user.recommendations.generate') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary" @disabled($remainingQuota <= 0)>
            <i class="ti ti-sparkles me-1"></i>生成推荐
        </button>
    </form>
    {{-- #25 配额耗尽补救入口 --}}
    @if($remainingQuota <= 0)
        <a href="{{ route('user.resumes.index') }}" class="btn btn-outline-info ms-2" title="完善简历资料可获额外生成次数">
            <i class="ti ti-plus me-1"></i>完善简历 +1 次
        </a>
    @endif
@endsection

@section('content')
<div class="row mb-4">
    <div class="col-6 col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="metric">
                    <div class="metric-value text-primary">{{ $stats['total'] }}</div>
                    <div class="metric-label text-secondary">总推荐</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="metric">
                    <div class="metric-value text-info">{{ $stats['new'] }}</div>
                    <div class="metric-label text-secondary">新推荐</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="metric">
                    <div class="metric-value text-success">{{ $stats['high_match'] }}</div>
                    <div class="metric-label text-secondary">高匹配</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="metric">
                    <div class="metric-value">{{ $stats['applied'] }}</div>
                    <div class="metric-label text-secondary">已投递</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('user.recommendations.index') }}" class="d-flex gap-2 align-items-end flex-wrap">
            <div class="flex-fill" style="min-width: 160px;">
                <label class="form-label small text-secondary">城市</label>
                <select name="city" class="form-select form-select-sm">
                    <option value="">全部城市</option>
                    @foreach($cities as $city)
                        <option value="{{ $city }}" @selected(request('city') === $city)>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label small text-secondary">排序</label>
                <select name="sort" class="form-select form-select-sm">
                    <option value="score" @selected(request('sort', 'score') === 'score')>匹配分优先</option>
                    <option value="time" @selected(request('sort') === 'time')>最新优先</option>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="ti ti-search me-1"></i>筛选
            </button>
            @if($recommendations->isNotEmpty())
            <button type="button" class="btn btn-sm btn-outline-secondary ms-auto batch-dismiss-btn">
                <i class="ti ti-x me-1"></i>批量忽略
            </button>
            @endif
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible">
    <i class="ti ti-check me-2"></i>{{ session('success') }}
    <a href="{{ route('user.recommendations.progress') }}" class="alert-link ms-2">查看进度</a>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible">
    <i class="ti ti-alert-circle me-2"></i>{{ session('error') }}
</div>
@endif

@if($recommendations->isEmpty())
{{-- #20 空状态引导 --}}
<div class="card">
    <div class="card-body text-center py-5">
        <i class="ti ti-sparkles fs-1 text-secondary"></i>
        <h3 class="mt-3">暂无推荐岗位</h3>
        <p class="text-secondary">点击"生成推荐"按钮，AI 将根据你的简历智能匹配岗位</p>
        <div class="mt-3">
            @if($remainingQuota > 0)
                <form action="{{ route('user.recommendations.generate') }}" method="POST" class="d-inline-block">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-sparkles me-1"></i>立即生成推荐
                    </button>
                </form>
            @else
                <div class="alert alert-warning d-inline-block">今日生成次数已达上限，请明日再来</div>
            @endif
            <a href="{{ route('user.resumes.index') }}" class="btn btn-outline-primary ms-2">
                <i class="ti ti-file me-1"></i>完善简历
            </a>
        </div>
    </div>
</div>
@else
{{-- #16 复选框批量 --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center">
            <label class="form-check m-0">
                <input type="checkbox" class="form-check-input select-all-cb" />
                <span class="form-check-label small ms-1">全选</span>
            </label>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-3 batch-dismiss-selected-btn" disabled>
                <i class="ti ti-x me-1"></i>批量忽略选中
            </button>
        </div>
    </div>
</div>

@foreach($recommendations as $recommendation)
    @include('user.job-recommendations.partials.job-card', ['recommendation' => $recommendation])
@endforeach

<div class="mt-4">
    {{ $recommendations->links() }}
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 标记已投递
    document.querySelectorAll('.apply-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            try {
                const res = await fetch('/user/recommendations/' + id + '/apply', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok) {
                    location.reload();
                } else {
                    alert(data.message || '操作失败');
                }
            } catch (e) { alert('网络错误'); }
        });
    });

    // 忽略单条 #23：5 秒撤销 Toast
    document.querySelectorAll('.dismiss-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (!confirm('确定忽略此推荐吗？')) return;
            const id = this.dataset.id;
            const card = this.closest('.job-recommendation-card');
            try {
                const res = await fetch('/user/recommendations/' + id + '/dismiss', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok) {
                    // #23 隐藏卡片 + 显示 5 秒撤销 Toast
                    card.style.display = 'none';
                    showUndoToast(data.message || '已忽略', () => {
                        // 撤销：恢复显示
                        card.style.display = '';
                    });
                } else {
                    alert(data.message || '操作失败');
                }
            } catch (e) { alert('网络错误'); }
        });
    });

    // #23 撤销 Toast：5 秒倒计时，点击撤销恢复卡片（不调接口，因 dismiss 已生效但卡片未真正删除）
    function showUndoToast(msg, undoCallback) {
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-bg-warning border-0 position-fixed top-0 start-50 translate-middle-x mt-3';
        toast.style.zIndex = '1080';
        toast.innerHTML = '<div class="d-flex"><div class="toast-body">' + msg + '</div><button type="button" class="btn btn-sm btn-link text-white text-decoration-none ms-2 undo-btn">撤销</button><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        document.body.appendChild(toast);
        const bs = new bootstrap.Toast(toast, { delay: 5000 });
        bs.show();
        toast.querySelector('.undo-btn').addEventListener('click', () => {
            undoCallback();
            bs.hide();
        });
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    // 批量忽略选中项（#16）
    const selectAllCb = document.querySelector('.select-all-cb');
    const batchSelectedBtn = document.querySelector('.batch-dismiss-selected-btn');

    if (selectAllCb) {
        selectAllCb.addEventListener('change', function() {
            document.querySelectorAll('.job-cb').forEach(cb => cb.checked = this.checked);
            updateBatchBtn();
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('job-cb')) {
                updateBatchBtn();
            }
        });

        function updateBatchBtn() {
            const checked = document.querySelectorAll('.job-cb:checked');
            if (batchSelectedBtn) {
                batchSelectedBtn.disabled = checked.length === 0;
                batchSelectedBtn.textContent = '';
                const icon = document.createElement('i');
                icon.className = 'ti ti-x me-1';
                batchSelectedBtn.appendChild(icon);
                batchSelectedBtn.appendChild(document.createTextNode(`批量忽略选中 (${checked.length})`));
            }
        }

        if (batchSelectedBtn) {
            batchSelectedBtn.addEventListener('click', async function() {
                const ids = Array.from(document.querySelectorAll('.job-cb:checked')).map(cb => cb.value);
                if (ids.length === 0) return;
                if (!confirm(`确定忽略选中的 ${ids.length} 条推荐吗？`)) return;

                try {
                    const res = await fetch('/user/recommendations/batch-dismiss', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ ids: ids })
                    });
                    const data = await res.json();
                    if (res.ok) {
                        // #22 局部 DOM 移除已忽略卡片，避免整页 reload
                        ids.forEach(id => {
                            const card = document.querySelector('.job-recommendation-card[data-id="' + id + '"]');
                            if (card) {
                                card.style.transition = 'opacity 0.3s';
                                card.style.opacity = '0';
                                setTimeout(() => card.remove(), 300);
                            }
                        });
                        // 更新统计数字
                        const totalEl = document.querySelector('.stat-total');
                        if (totalEl) {
                            totalEl.textContent = Math.max(0, parseInt(totalEl.textContent) - ids.length);
                        }
                        // 取消全选
                        if (selectAllCb) selectAllCb.checked = false;
                        updateBatchBtn();
                        // 显示 Toast
                        showToast(data.message || '已批量忽略', 'success');
                    } else {
                        alert(data.message || '操作失败');
                    }
                } catch (e) { alert('网络错误'); }
            });
        }
    }

    // #22 简易 Toast
    function showToast(msg, type) {
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-bg-' + (type === 'success' ? 'success' : 'danger') + ' border-0 position-fixed top-0 start-50 translate-middle-x mt-3';
        toast.style.zIndex = '1080';
        toast.innerHTML = '<div class="d-flex"><div class="toast-body">' + msg + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        document.body.appendChild(toast);
        const bs = new bootstrap.Toast(toast, { delay: 2000 });
        bs.show();
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    // #4 标记已查看：改用 IntersectionObserver 懒触发，避免一进列表页瞬间 15 个请求
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const card = entry.target;
                    const id = card.dataset.id;
                    if (!card.dataset.viewed) {
                        // 标记为正在请求，避免重复触发
                        card.dataset.viewed = 'pending';
                        fetch('/user/recommendations/' + id + '/view', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        }).then(() => {
                            card.dataset.viewed = '1';
                        }).catch(() => {
                            // 失败时清除标记，允许下次进入视口重试
                            card.dataset.viewed = '';
                        });
                    }
                    // 进入视口后停止观察该卡片
                    observer.unobserve(card);
                }
            });
        }, { rootMargin: '100px' }); // 提前 100px 触发

        document.querySelectorAll('.job-recommendation-card').forEach(card => observer.observe(card));
    } else {
        // 降级：不支持 IntersectionObserver 的浏览器只在点击时标记
        document.querySelectorAll('.job-recommendation-card').forEach(card => {
            card.addEventListener('click', function() {
                const id = this.dataset.id;
                if (!this.dataset.viewed) {
                    fetch('/user/recommendations/' + id + '/view', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    }).catch(() => {});
                }
            });
        });
    }
});
</script>
@endpush
