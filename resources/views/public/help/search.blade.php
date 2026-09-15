@extends('layouts.user')

@section('title', ($keyword ? "搜索「{$keyword}」" : '搜索') . ' - 帮助中心')
@section('page-pretitle', '帮助中心')
@section('page-title', $keyword ? "搜索「{$keyword}」" : '搜索文章')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/help-center.css') }}">
<link rel="stylesheet" href="{{ asset('css/pages/help-article-content.css') }}">
@endpush

@section('content')
<div class="row row-cards">
    {{-- Search bar --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('public.help.search') }}" method="GET" class="row g-2 align-items-end">
                    <div class="col-md-8">
                        <input type="text" name="q" class="form-control form-control-lg" placeholder="搜索帮助文章..." value="{{ $keyword }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="ti ti-search me-1"></i>搜索
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('public.help.index') }}">帮助中心</a></li>
                <li class="breadcrumb-item active">搜索</li>
            </ol>
        </nav>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">分类</h3>
            </div>
            <div class="list-group list-group-flush">
                @foreach($categories as $cat)
                <a href="{{ route('public.help.category', ['categorySlug' => $cat->slug]) }}" class="list-group-item list-group-item-action">
                    @if($cat->icon)<i class="{{ $cat->icon }} me-1"></i>@endif
                    {{ $cat->name }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Results --}}
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    @if($keyword)
                    搜索 "{{ $keyword }}" 的结果（{{ $articles->total() }} 条）
                    @else
                    全部文章
                    @endif
                </h3>
            </div>
            <div class="list-group list-group-flush">
                @forelse($articles as $article)
                <a href="{{ route('public.help.show', ['categorySlug' => $article->category?->slug, 'articleSlug' => $article->slug]) }}" class="list-group-item list-group-item-action">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <i class="ti ti-file-text text-secondary"></i>
                        </div>
                        <div class="col">
                            <div class="fw-bold">
                                @if($keyword)
                                    {!! preg_replace('/(' . preg_quote(e($keyword), '/') . ')/iu', '<mark>$1</mark>', e($article->title)) !!}
                                @else
                                    {{ $article->title }}
                                @endif
                            </div>
                            <div>
                                <span class="badge bg-primary-lt text-primary me-1">{{ $article->category?->name }}</span>
                                @if($article->excerpt)
                                <span class="text-secondary small">
                                    @if($keyword)
                                        {!! preg_replace('/(' . preg_quote(e($keyword), '/') . ')/iu', '<mark>$1</mark>', e(Str::limit($article->excerpt, 100))) !!}
                                    @else
                                        {{ Str::limit($article->excerpt, 100) }}
                                    @endif
                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-auto text-secondary small">
                            {{ $article->published_at?->format('Y-m-d') }}
                        </div>
                    </div>
                </a>
                @empty
                <div class="list-group-item text-secondary text-center py-4">
                    @if($keyword)
                    未找到与 "{{ $keyword }}" 相关的文章
                    @else
                    暂无文章
                    @endif
                </div>
                @endforelse
            </div>
            @if($articles->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $articles->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
