<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · TaskMate</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
</head>
<body class="auth-body">
    @yield('content')

    @if (session('sukses'))<div data-toast="sukses" data-msg="{{ session('sukses') }}"></div>@endif
    @if (session('gagal'))<div data-toast="gagal" data-msg="{{ session('gagal') }}"></div>@endif
    @if (session('info'))<div data-toast="info" data-msg="{{ session('info') }}"></div>@endif

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
