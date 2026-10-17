<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trim(strip_tags((string) ($header ?? ''))) ?: 'Dashboard' }} · {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script>(function(){try{if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark');}}catch(e){}})();</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-fixes.css') }}">
    @stack('styles')
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Main navigation">
        <div class="brand">
            <span class="brand-mark">{{ config('navigation.brand.mark') }}</span>
            <div>
                <strong>{{ config('navigation.brand.title') }}</strong>
                <small>{{ config('navigation.brand.subtitle') }}</small>
            </div>
        </div>

        <x-sidebar-nav />

        <div class="sidebar-foot">
            <span class="user-line">{{ auth()->user()->name }}</span>
            <span>{{ ucfirst(auth()->user()->role) }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="signout">Sign out</button>
            </form>
        </div>
    </aside>
    <div class="mobile-backdrop" id="backdrop" data-action="close-nav"></div>

    <main class="main">
        <header class="topbar">
            <div class="top-left">
                <button type="button" class="menu-toggle icon-btn" data-action="open-nav" aria-label="Open menu">☰</button>
                <div>
                    <div class="crumb">{{ config('app.name') }}</div>
                    <div class="top-title">{{ $header ?? '' }}</div>
                </div>
            </div>
            <div class="top-actions">
                <button type="button" data-action="theme" aria-label="Switch light or dark appearance">◐</button>
                <x-notifications />
                <a class="profile" href="{{ route('profile.edit') }}">
                    <span class="avatar">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="hide-mobile">{{ auth()->user()->name }}<small>{{ ucfirst(auth()->user()->role) }}</small></span>
                </a>
            </div>
        </header>

        <div class="content">
            {{ $slot }}
        </div>
    </main>

    <x-flash />
</div>
<script src="{{ asset('js/shell.js') }}" defer></script>
@stack('scripts')
</body>
</html>
