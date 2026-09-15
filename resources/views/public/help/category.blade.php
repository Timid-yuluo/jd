@extends('layouts.user')

@section('title', $category->name . ' - 帮助中心')
@section('page-pretitle', '帮助中心')
@section('page-title', $category->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/help-center.css') }}">
<link rel="stylesheet" href="{{ asset('css/pages/help-article-content.css') }}">
@endpush

@section('content')
<div class="row row-cards">
    {{-- Breadcrumb --}}
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('public.help.index') }}">帮助中心</a></li>
                <li class="breadcrumb-item active">{{ $category->name }}</li>
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
                <a href="{{ route('public.help.category', ['categorySlug' => $cat->slug]) }}" class="list-group-item list-group-item-action {{ $cat->id === $category->id ? 'active' : '' }}">
                    @if($cat->icon)<i class="{{ $cat->icon }} me-1"></i>@endif
                    {{ $cat->name }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Article list --}}
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $category->name }}</h3>
                @if($category->description)
                <div class="card-subtitle mt-1 text-secondary">{{ $category->description }}</div>
                @endif
            </div>
            <div class="list-group list-group-flush">
                @forelse($articles as $article)
                <a href="{{ route('public.help.show', ['categorySlug' => $category->slug, 'articleSlug' => $article->slug]) }}" class="list-group-item list-group-item-action">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <i class="ti ti-file-text text-secondary"></i>
                        </div>
                        <div class="col">
                            <div class="fw-bold">{{ $article->title }}</div>
                            @if($article->excerpt)
                            <div class="text-secondary small">{{ Str::limit($article->excerpt, 100) }}</div>
                            @endif
                        </div>
                        <div class="col-auto text-secondary small">
                            {{ $article->published_at?->format('Y-m-d') }}
                        </div>
                    </div>
                </a>
                @empty
                <div class="list-group-item text-secondary text-center py-4">该分类暂无文章</div>
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
