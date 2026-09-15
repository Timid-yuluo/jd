@props([
    'icon' => 'ti-package',
    'title' => '暂无数据',
    'description' => '',
    'actionText' => '',
    'actionUrl' => '',
])

<div class="text-center py-5">
    <i class="ti {{ $icon }} text-secondary fs-1 mb-2"></i>
    <div class="text-secondary">{{ $title }}</div>
    @if($description)
        <div class="text-secondary small mt-1">{{ $description }}</div>
    @endif
    @if($actionText && $actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-primary btn-sm mt-3">
            {!! $actionText !!}
        </a>
    @endif
    @isset($slots['action'])
        <div class="mt-3">{{ $slots['action'] }}</div>
    @endisset
</div>
