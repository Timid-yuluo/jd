@push('scripts')
<script src="{{ asset('vendor/html2pdf.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/Sortable.min.js') }}"></script>
@include('user.resumes.editor-partials.editor-script-config')
@include('user.resumes.editor-partials.editor-script-logic')
@endpush
