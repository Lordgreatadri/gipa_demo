<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#075b3b">
        <title>{{ $title ?? 'Staff workspace' }} | IOMP</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|manrope:600,700,800" rel="stylesheet">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="admin-body">
        <a class="skip-link" href="#admin-content">Skip to main content</a>
        <div class="admin-shell">
            <aside class="admin-sidebar" data-admin-sidebar>
                <a class="admin-brand" href="{{ route('staff.dashboard') }}">
                    <img src="{{ asset('images/gipa-logo.png') }}" alt="">
                    <span><strong>IOMP</strong><small>Staff workspace</small></span>
                </a>
                <nav aria-label="Staff navigation">
                    <a href="{{ route('staff.dashboard') }}" @class(['is-current' => request()->routeIs('staff.dashboard')])><span aria-hidden="true">01</span> Dashboard</a>
                    <a href="{{ route('staff.opportunities.index') }}" @class(['is-current' => request()->routeIs('staff.opportunities.*')])><span aria-hidden="true">02</span> Opportunities</a>
                    <a href="{{ route('staff.districts.index') }}" @class(['is-current' => request()->routeIs('staff.districts.*')])><span aria-hidden="true">03</span> Districts</a>
                    @can('opportunities.submit')<a href="{{ route('staff.reference-data.index') }}" @class(['is-current' => request()->routeIs('staff.reference-data.*')])><span aria-hidden="true">04</span> Reference data</a>@endcan
                    <span class="admin-nav-label">Oversight</span>
                    <a href="#"><span aria-hidden="true">05</span> Inquiries <i>Next</i></a>
                    <a href="#"><span aria-hidden="true">06</span> Audit log <i>Next</i></a>
                    <a href="#"><span aria-hidden="true">07</span> Reports <i>Next</i></a>
                </nav>
                <div class="admin-sidebar__footer"><span>{{ auth()->user()->name }}</span><small>{{ auth()->user()->roles->pluck('name')->join(', ') }}</small></div>
            </aside>
            <div class="admin-main">
                <header class="admin-topbar">
                    <button class="admin-menu-button" type="button" data-admin-menu aria-label="Open staff navigation" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg></button>
                    <div><span>Ghana Investment Promotion Authority</span><small>{{ now()->format('l, j F Y') }}</small></div>
                    <div class="admin-topbar__actions">
                        <button class="icon-button" type="button" data-theme-toggle aria-label="Switch to dark mode" title="Change colour theme"><svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2"></path></svg><svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.7 13.2A8.5 8.5 0 1 1 10.8 3.3a6.5 6.5 0 0 0 9.9 9.9Z"></path></svg></button>
                        <form action="{{ route('logout') }}" method="post">@csrf<button class="admin-signout" type="submit">Sign out</button></form>
                    </div>
                </header>
                <main class="admin-content" id="admin-content">
                    @if(session('status'))<div class="admin-alert" role="status">{{ session('status') }}</div>@endif
                    @if($errors->any())<div class="admin-alert admin-alert--error" role="alert"><strong>Action not completed.</strong><span>{{ $errors->first() }}</span></div>@endif
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>