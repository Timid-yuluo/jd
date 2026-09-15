@extends('layouts.user')

@section('title', '历史岗位匹配分析')

@section('page-pretitle', '智能分析')
@section('page-title', '历史岗位匹配分析')

@section('page-actions')
<a href="{{ route('user.jobs.analyze') }}" class="btn btn-primary">
    <i class="ti ti-arrow-left me-2"></i>返回分析页
</a>
<button type="button" class="btn btn-outline-danger d-none" id="batchDeleteBtn">
    <i class="ti ti-trash me-1"></i>批量删除 (<span id="batchDeleteCount">0</span>)
</button>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">历史记录</h3>
    </div>
    <div class="card-body">
        @if($analyses->isEmpty())
            <div class="text-secondary">暂无历史分析记录。</div>
        @else
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="form-check-input" id="selectAll"></th>
                            <th>ID</th>
                            <th>时间</th>
                            <th>简历</th>
                            <th>评分</th>
                            <th>摘要</th>
                            <th class="text-end">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($analyses as $item)
                            @php
                                $score = (int) ($item->match_score ?? 0);
                            @endphp
                            <tr>
                                <td><input type="checkbox" class="form-check-input row-check" data-id="{{ $item->id }}"></td>
                                <td>#{{ $item->id }}</td>
                                <td>{{ $item->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $item->resume?->title ?? '简历已删除' }}</td>
                                <td>{{ $score }}</td>
                                <td>{{ \Illuminate\Support\Str::limit((string) $item->summary, 50) ?: '-' }}</td>
                                <td class="text-end">
                                    <div class="btn-list justify-content-end">
                                        <a href="{{ route('user.jobs.analyze.history', ['analysis' => $item->id]) }}" class="btn btn-sm btn-outline-primary">查看</a>
                                        <a href="{{ route('user.jobs.analyze', ['from_history' => $item->id]) }}" class="btn btn-sm btn-outline-secondary">复用</a>
                                        <form action="{{ route('user.jobs.analyze.history.destroy', ['analysis' => $item->id]) }}" method="POST" data-confirm-submit="确认删除这条历史分析记录吗？">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $analyses->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
document.addEventListener('DOMContentLoaded', function() {
    var selectAll = document.getElementById('selectAll');
    var batchBtn = document.getElementById('batchDeleteBtn');
    var countSpan = document.getElementById('batchDeleteCount');

    function updateBatchBtn() {
        var checked = document.querySelectorAll('.row-check:checked');
        if (checked.length > 0) {
            batchBtn.classList.remove('d-none');
            countSpan.textContent = checked.length;
        } else {
            batchBtn.classList.add('d-none');
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.row-check').forEach(function(cb) { cb.checked = selectAll.checked; });
            updateBatchBtn();
        });
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-check')) updateBatchBtn();
    });

    if (batchBtn) {
        batchBtn.addEventListener('click', function() {
            var ids = [];
            document.querySelectorAll('.row-check:checked').forEach(function(cb) { ids.push(cb.dataset.id); });
            if (!ids.length || !confirm('确认删除选中的 ' + ids.length + ' 条记录？')) return;
            fetch('{{ route("user.jobs.analyze.history.batch-destroy") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ids: ids})
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.ok) location.reload();
                else alert(data.message || '删除失败');
            }).catch(function() { alert('请求失败'); });
        });
    }
});
</script>
@endsection
