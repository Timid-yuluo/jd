@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/user-resumes-editor-partials-styles-sidebar.css') }}?v={{ @filemtime(public_path('css/pages/user-resumes-editor-partials-styles-sidebar.css')) ?: time() }}">
@endpush
