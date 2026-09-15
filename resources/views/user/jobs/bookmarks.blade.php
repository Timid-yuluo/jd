@extends('layouts.user')

@section('title', '岗位收藏')

@section('page-pretitle', '智能分析')
@section('page-title', '岗位收藏')

@section('page-actions')
<a href="{{ route('user.jobs.analyze') }}" class="btn btn-outline-secondary">
    <i class="ti ti-arrow-left me-1"></i>返回分析
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        @if($bookmarks->isNotEmpty())
            <div class="list-group list-group-flush">
                @foreach($bookmarks as $bm)
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <div class="flex-fill">
                            <div class="fw-medium">{{ $bm->title ?: '未命名岗位' }}</div>
                            @if($bm->company)
                                <div class="text-secondary small">{{ $bm->company }}</div>
                            @endif
                            <div class="text-secondary small mt-1">{{ \Illuminate\Support\Str::limit($bm->job_description, 80) }}</div>
                            <div class="text-secondary small">{{ $bm->created_at?->format('Y-m-d H:i') }}</div>
                        </div>
                        <div class="d-flex flex-column gap-1">
                            <a href="{{ route('user.jobs.analyze', ['from_bookmark' => $bm->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-sparkles me-1"></i>分析
                            </a>
                            <form action="{{ route('user.jobs.bookmarks.destroy', $bm) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">取消收藏</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-3">{{ $bookmarks->links() }}</div>
        @else
            <div class="text-center text-secondary py-5">
                <i class="ti ti-bookmark-off fs-1 d-block mb-2"></i>
                暂无收藏岗位，在分析页面粘贴 JD 后可收藏备用。
            </div>
        @endif
    </div>
</div>
@endsection
