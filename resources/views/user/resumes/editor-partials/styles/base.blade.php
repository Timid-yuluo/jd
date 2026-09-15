@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/user-resumes-editor-partials-styles-base.css') }}?v={{ @filemtime(public_path('css/pages/user-resumes-editor-partials-styles-base.css')) ?: time() }}">
@endpush
