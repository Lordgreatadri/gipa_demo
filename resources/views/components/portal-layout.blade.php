<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#075b3b">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $description ?? 'Manage your investment interests and onboarding with GIPA.' }}">
        <title>{{ $title ?? 'Investor workspace' }} | IOMP</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|manrope:600,700,800" rel="stylesheet">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="portal-body">
        <a class="skip-link" href="#portal-content">Skip to main content</a>
        <div class="admin-shell portal-app">
            <aside class="admin-sidebar portal-sidebar" data-admin-sidebar>
                <a class="admin-brand" href="{{ route('investor.dashboard') }}">
                    <img src="{{ asset('images/gipa-logo.png') }}" alt="">
                    <span><strong>IOMP</strong><small>Investor workspace</small></span>
                </a>
                <nav class="portal-navigation" aria-label="Investor navigation">
                    <a href="{{ route('investor.dashboard') }}#overview"><i data-lucide="layout-dashboard" aria-hidden="true"></i><span>Overview</span></a>
                    <a href="{{ route('investor.dashboard') }}#matches"><i data-lucide="briefcase-business" aria-hidden="true"></i><span>Opportunity matches</span></a>
                    <a href="{{ route('investor.dashboard') }}#mandate"><i data-lucide="gauge" aria-hidden="true"></i><span>Investment mandate</span></a>
                    <a href="{{ route('investor.dashboard') }}#profile"><i data-lucide="landmark" aria-hidden="true"></i><span>Investor profile</span></a>
                    <a href="{{ route('investor.dashboard') }}#kyc"><i data-lucide="badge-check" aria-hidden="true"></i><span>KYC onboarding</span></a>
                    <a href="{{ route('opportunities.index') }}"><i data-lucide="search" aria-hidden="true"></i><span>Explore opportunities</span></a>
                    <a href="{{ route('platform.guide') }}"><i data-lucide="files" aria-hidden="true"></i><span>Investor guide</span></a>
                </nav>
                <div class="admin-sidebar__footer">
                    <span>{{ auth()->user()->name }}</span>
                    <small>{{ auth()->user()->organization ?: auth()->user()->email }}</small>
                </div>
            </aside>
            <div class="admin-main">
                <header class="admin-topbar portal-topbar">
                    <button class="admin-menu-button" type="button" data-admin-menu aria-label="Open investor navigation" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg></button>
                    <div><span>Ghana Investment Promotion Authority</span><small>Secure investor portal</small></div>
                    <div class="admin-topbar__actions">
                        <a class="portal-public-link" href="{{ route('home') }}">Public website</a>
                        <button class="icon-button" type="button" data-theme-toggle aria-label="Switch to dark mode" title="Change colour theme"><svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2"></path></svg><svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.7 13.2A8.5 8.5 0 1 1 10.8 3.3a6.5 6.5 0 0 0 9.9 9.9Z"></path></svg></button>
                        <form action="{{ route('logout') }}" method="post">@csrf<button class="admin-signout" type="submit">Sign out</button></form>
                    </div>
                </header>
                <main class="portal-content" id="portal-content">{{ $slot }}</main>
            </div>
        </div>
        <x-assistant-widget />
    </body>
</html>