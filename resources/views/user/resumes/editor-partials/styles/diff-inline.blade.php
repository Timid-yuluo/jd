@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/user-resumes-editor-partials-styles-diff-inline.css') }}?v={{ @filemtime(public_path('css/pages/user-resumes-editor-partials-styles-diff-inline.css')) ?: time() }}">
@endpush
