<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'MizanFlow') }} - @yield('title', 'تسجيل الدخول')</title>

    <!-- Local Bundled Assets (Vite) - Zero CDN dependency -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-3 py-sm-4 py-md-5" 
      style="background-color: var(--mz-bg-app); background-image: radial-gradient(circle at 85% 15%, rgba(11, 87, 208, 0.05) 0%, transparent 45%), radial-gradient(circle at 15% 85%, rgba(11, 87, 208, 0.03) 0%, transparent 45%); min-height: 100vh;">
    <div class="container-fluid px-2 px-sm-3 px-md-4" style="max-width: 1180px;">
        @yield('content')
    </div>
</body>
</html>
