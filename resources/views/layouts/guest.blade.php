<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script>(function(){try{if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark');}}catch(e){}})();</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
<main class="login-page">
    <section class="login-story">
        <a class="brand auth-brand" href="{{ url('/') }}" aria-label="{{ config('app.name') }}">
            <span class="brand-mark">{{ config('navigation.brand.mark') }}</span>
            <span>
                <strong>{{ config('navigation.brand.title') }}</strong>
                <small>{{ config('navigation.brand.subtitle') }}</small>
            </span>
        </a>
        <div class="auth-story-copy">
            <div class="eyebrow">Makkah · Madinah · one shared view</div>
            <h1>Rooming made clear.<br>Accounts kept close.</h1>
            <p>A composed operations desk for the teams coordinating every stay, arrival and company account.</p>
        </div>
        <small class="auth-story-foot">{{ config('app.name') }}</small>
    </section>

    <section class="login-form-side">
        <div class="login-card">
            {{ $slot }}
        </div>
    </section>
</main>
<script src="{{ asset('js/shell.js') }}" defer></script>
</body>
</html>
