@extends('layouts.user')

@section('title', '版本历史 - ' . $resume->title)

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <a href="{{ route('user.resumes.show', $resume) }}" class="btn btn-link text-secondary p-0"><i class="ti ti-arrow-left me-1"></i>返回简历</a>
                <h2 class="page-title">版本历史</h2>
                <div class="text-secondary">{{ $resume->title }} · 共 {{ $versions->total() }} 个版本</div>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#snapshotModal">
                    <i class="ti ti-camera me-1"></i>保存快照
                </button>
                <a href="{{ route('user.resumes.versions.compare', $resume) }}" class="btn btn-outline-primary"><i class="ti ti-arrows-exchange me-1"></i>版本对比</a>
            </div>
        </div>
    </div>
</div>

{{-- 手动创建快照弹窗 --}}
<div class="modal fade" id="snapshotModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="{{ route('user.resumes.versions.snapshot', $resume) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">保存版本快照</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">快照标签</label>
                        <input type="text" name="label" class="form-control" placeholder="如：投递前终版" maxlength="100">
                        <div class="form-hint">给当前版本起个名字，方便后续查找</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">变更说明</label>
                        <textarea name="change_summary" class="form-control" rows="2" placeholder="如：优化了项目描述" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-success"><i class="ti ti-camera me-1"></i>保存快照</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        @if($versions->isEmpty())
            <div class="empty">
                <p class="empty-title">暂无历史版本</p>
                <p class="empty-subtitle text-secondary">每次编辑简历内容后，系统会自动保存一个版本快照。你也可以手动保存快照来标记重要节点。</p>
            </div>
        @else
            {{-- 版本时间线 --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">版本记录</h3>
                    <div class="card-actions">
                        <span class="text-secondary small">最多保留 50 个版本，手动标记版本优先保留</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th style="width:170px;">保存时间</th>
                                <th style="width:80px;">来源</th>
                                <th>标签</th>
                                <th>标题</th>
                                <th>目标岗位</th>
                                <th style="width:60px;">模块数</th>
                                <th>变更说明</th>
                                <th style="width:200px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($versions as $i => $version)
                                <tr class="{{ $version->source === 'manual' ? 'table-info' : '' }}">
                                    <td class="text-secondary">{{ $versions->total() - $versions->firstItem() - $i + 1 }}</td>
                                    <td class="small">{{ $version->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        @if($version->source === 'manual')
                                            @if(str_starts_with($version->change_summary ?? '', '切换赛道'))
                                                <span class="badge bg-success-lt">🧭 赛道</span>
                                            @else
                                                <span class="badge bg-info">📌 手动</span>
                                            @endif
                                        @elseif($version->source === 'restore')
                                            <span class="badge bg-warning">回滚</span>
                                        @elseif($version->source === 'optimize')
                                            <span class="badge bg-purple-lt">AI优化</span>
                                        @else
                                            <span class="badge bg-light text-dark">自动</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('user.resumes.versions.label', [$resume, $version]) }}" method="POST" class="d-inline-flex gap-1 align-items-center">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="label" value="{{ $version->label ?? '' }}" placeholder="添加标签" class="form-control form-control-sm" style="width:110px;font-size:.75rem;" maxlength="100">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1" title="保存"><i class="ti ti-check" style="font-size:.7rem;"></i></button>
                                        </form>
                                    </td>
                                    <td class="fw-medium">{{ $version->title }}</td>
                                    <td class="small text-secondary">{{ $version->target_job ?: '-' }}</td>
                                    <td class="text-center">
                                        @if($version->module_count !== null)
                                            <span class="badge bg-light text-dark">{{ $version->module_count }}</span>
                                        @else
                                            <span class="text-secondary">-</span>
                                        @endif
                                    </td>
                                    <td class="small text-secondary">{{ $version->change_summary ?: '-' }}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <form action="{{ route('user.resumes.versions.restore', [$resume, $version]) }}" method="POST" onsubmit="return confirm('恢复到此版本？当前内容会自动保存为新版本。')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="恢复此版本"><i class="ti ti-restore"></i></button>
                                            </form>
                                            @if($loop->iteration < $versions->count())
                                                <a href="{{ route('user.resumes.versions.compare', ['resume' => $resume, 'a' => $version->id, 'b' => $versions[$loop->index + 1]->id ?? $version->id]) }}" class="btn btn-sm btn-outline-secondary" title="与下一版本对比">
                                                    <i class="ti ti-arrows-exchange"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3">{{ $versions->links() }}</div>
        @endif
    </div>
</div>

<style>
.badge-purple-lt { background: #f0e6ff; color: #7c3aed; }
</style>
@endsection
