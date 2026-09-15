@extends('layouts.user')

@section('title', '帮助中心')
@section('page-pretitle', '帮助中心')
@section('page-title', '帮助中心')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/help-center.css') }}">
<link rel="stylesheet" href="{{ asset('css/pages/help-article-content.css') }}">
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        {{-- Search Hero --}}
        <div class="card card-md mb-4">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <i class="ti ti-help-hexagon text-primary" style="font-size:3rem"></i>
                </div>
                <h2 class="mb-2">帮助中心</h2>
                <p class="text-secondary mb-4">搜索常见问题、使用指南和教程</p>
                <form action="{{ route('public.help.search') }}" method="GET" class="row g-2 justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="text" name="q" class="form-control form-control-lg" placeholder="输入关键词搜索..." value="{{ request('q') }}">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="ti ti-search me-1"></i>搜索
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($categories->isEmpty())
        <div class="card">
            <div class="card-body help-center-empty">
                <div class="icon"><i class="ti ti-help-hexagon"></i></div>
                <h3>帮助中心正在建设中</h3>
                <p class="text-secondary">我们正在整理帮助文档，敬请期待。</p>
            </div>
        </div>
        @else
        {{-- Category Grid --}}
        <div class="row row-cards mb-4">
            @foreach($categories as $category)
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm help-category-card">
                    <a href="{{ route('public.help.category', ['categorySlug' => $category->slug]) }}" class="card-body text-decoration-none">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar bg-primary-lt me-3">
                                <i class="{{ $category->icon ?: 'ti ti-folder' }} text-primary" style="font-size:1.25rem"></i>
                            </span>
                            <div>
                                <h3 class="card-title mb-0">{{ $category->name }}</h3>
                                @if($category->articles_count > 0)
                                <small class="text-secondary">{{ $category->articles_count }} 篇文章</small>
                                @endif
                            </div>
                        </div>
                        @if($category->description)
                        <p class="text-secondary small mb-3">{{ Str::limit($category->description, 60) }}</p>
                        @endif
                        @php $topArticles = $category->articles->take(3); @endphp
                        @if($topArticles->isNotEmpty())
                        <ul class="list-unstyled mb-0 help-article-list">
                            @foreach($topArticles as $article)
                            <li class="text-truncate">
                                <i class="ti ti-file-text text-secondary me-1 small"></i>
                                {{ $article->title }}
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Recent Articles --}}
        @if($recentArticles->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-clock me-2 text-muted"></i>最新文章
                </h3>
            </div>
            <div class="list-group list-group-flush">
                @foreach($recentArticles as $article)
                <a href="{{ route('public.help.show', ['categorySlug' => $article->category?->slug, 'articleSlug' => $article->slug]) }}"
                   class="list-group-item list-group-item-action">
                    <div class="row align-items-center">
                        <div class="col text-truncate">
                            <span class="badge bg-azure-lt me-2">{{ $article->category?->name }}</span>
                            <span class="fw-medium">{{ $article->title }}</span>
                            @if($article->excerpt)
                            <div class="text-secondary small mt-1">{{ Str::limit($article->excerpt, 80) }}</div>
                            @endif
                        </div>
                        <div class="col-auto text-secondary small">
                            <i class="ti ti-calendar me-1"></i>{{ $article->published_at?->format('Y-m-d') }}
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
