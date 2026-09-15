@extends('layouts.user')

@section('title', $article->title . ' - 帮助中心')
@section('page-pretitle', '帮助中心')
@section('page-title', $article->title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/help-center.css') }}">
<link rel="stylesheet" href="{{ asset('css/pages/help-article-content.css') }}">
@endpush

@section('meta')
@if($article->excerpt)
<meta name="description" content="{{ $article->excerpt }}">
@endif
@if($article->meta_keywords)
<meta name="keywords" content="{{ $article->meta_keywords }}">
@endif
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('public.help.index') }}">帮助中心</a></li>
                <li class="breadcrumb-item"><a href="{{ route('public.help.category', ['categorySlug' => $category->slug]) }}">{{ $category->name }}</a></li>
                <li class="breadcrumb-item active text-truncate" style="max-width:300px">{{ $article->title }}</li>
            </ol>
        </nav>

        {{-- Article content --}}
        <div class="card">
            <div class="card-header">
                <h2 class="card-title mb-1">{{ $article->title }}</h2>
                <div class="text-secondary small d-flex flex-wrap gap-3">
                    <span><i class="ti ti-calendar me-1"></i>{{ $article->published_at?->format('Y-m-d H:i') }}</span>
                    <span><i class="ti ti-eye me-1"></i>{{ $article->view_count }} 次浏览</span>
                    <span class="badge bg-azure-lt">{{ $category->name }}</span>
                </div>
            </div>
            <div class="card-body help-article-content">
                {!! App\Support\HtmlPurifier::clean($article->content) !!}
            </div>
        </div>

        {{-- Prev/Next navigation --}}
        @php
            $prevArticle = $category->articles()
                ->where('is_published', true)
                ->where('id', '!=', $article->id)
                ->where('sort_order', '<=', $article->sort_order)
                ->where('id', '<', $article->id)
                ->orderByDesc('sort_order')->orderByDesc('id')
                ->first();
            $nextArticle = $category->articles()
                ->where('is_published', true)
                ->where('id', '!=', $article->id)
                ->where('sort_order', '>=', $article->sort_order)
                ->where('id', '>', $article->id)
                ->orderBy('sort_order')->orderBy('id')
                ->first();
        @endphp
        @if($prevArticle || $nextArticle)
        <div class="card mt-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                @if($prevArticle)
                <a href="{{ route('public.help.show', ['categorySlug' => $category->slug, 'articleSlug' => $prevArticle->slug]) }}" class="text-reset text-decoration-none" style="max-width:45%">
                    <div class="text-secondary small mb-1"><i class="ti ti-arrow-left me-1"></i>上一篇</div>
                    <div class="fw-medium text-truncate">{{ $prevArticle->title }}</div>
                </a>
                @else
                <div></div>
                @endif
                @if($nextArticle)
                <a href="{{ route('public.help.show', ['categorySlug' => $category->slug, 'articleSlug' => $nextArticle->slug]) }}" class="text-reset text-decoration-none text-end" style="max-width:45%">
                    <div class="text-secondary small mb-1">下一篇<i class="ti ti-arrow-right ms-1"></i></div>
                    <div class="fw-medium text-truncate">{{ $nextArticle->title }}</div>
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- Back to category --}}
        <div class="text-center mt-3">
            <a href="{{ route('public.help.category', ['categorySlug' => $category->slug]) }}" class="btn btn-ghost-secondary">
                <i class="ti ti-arrow-left me-1"></i>返回「{{ $category->name }}」
            </a>
        </div>
    </div>
</div>
@endsection
