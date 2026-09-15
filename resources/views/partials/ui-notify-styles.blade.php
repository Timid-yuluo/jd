@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/partials-ui-notify.css') }}">
@endpush

@auth
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/partials-ui-notify-2.css') }}">
@endpush
@endauth
