<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#075b3b">
        <meta name="description" content="{{ $description ?? 'Discover verified investment opportunities across Ghana.' }}">
        <title>{{ $title ?? 'Investment opportunities' }} | IOMP</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|manrope:600,700,800" rel="stylesheet">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        <a class="skip-link" href="#main-content">Skip to main content</a>
        <div class="official-bar">
            <div class="shell official-bar__inner">
                <span class="flag-mark" aria-hidden="true"><i></i><i></i><i></i></span>
                <span>An official investment platform of the Republic of Ghana</span>
                <span class="official-bar__status"><i aria-hidden="true"></i> Platform online</span>
            </div>
        </div>
        <header class="site-header" data-header>
            <div class="shell site-header__inner">
                <a class="brand" href="{{ route('home') }}" aria-label="IOMP home">
                    <img class="brand__logo" src="{{ asset('images/gipa-logo.png') }}" alt="">
                    <span class="brand__name"><strong>IOMP</strong><small>Investment Opportunities Mapping Project</small></span>
                </a>
                <nav class="desktop-nav" aria-label="Primary navigation">
                    <a href="{{ route('opportunities.index') }}" @class(['is-current' => request()->routeIs('opportunities.*')])>Opportunities</a>
                    <a href="{{ route('districts.index') }}#investment-map">Investment map</a>
                    <a href="{{ route('districts.index') }}" @class(['is-current' => request()->routeIs('districts.*')])>Districts</a>
                    <a href="{{ route('home') }}#insights">Insights</a>
                </nav>
                <div class="header-actions">
                    <button class="icon-button" type="button" data-theme-toggle aria-label="Switch to dark mode" title="Change colour theme">
                        <svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"></path></svg>
                        <svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.7 13.2A8.5 8.5 0 1 1 10.8 3.3a6.5 6.5 0 0 0 9.9 9.9Z"></path></svg>
                    </button>
                    @guest
                        <a class="button button--outline header-login" href="{{ route('login') }}">Investor login</a>
                    @else
                        <a class="button button--outline header-login" href="{{ auth()->user()->isStaff() ? route('staff.dashboard') : route('investor.dashboard') }}">{{ auth()->user()->isStaff() ? 'Staff workspace' : 'Investor workspace' }}</a>
                    @endguest
                    <button class="icon-button menu-button" type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-navigation" aria-label="Open navigation">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
                    </button>
                </div>
            </div>
            <nav class="mobile-nav" id="mobile-navigation" data-mobile-nav aria-label="Mobile navigation" hidden>
                <a href="{{ route('opportunities.index') }}">Opportunities</a>
                <a href="{{ route('districts.index') }}#investment-map">Investment map</a>
                <a href="{{ route('districts.index') }}">Districts</a>
                <a href="{{ route('platform.guide') }}">Investor guide</a>
                @guest
                    <a href="{{ route('login') }}">Investor login</a>
                @else
                    <a href="{{ auth()->user()->isStaff() ? route('staff.dashboard') : route('investor.dashboard') }}">{{ auth()->user()->isStaff() ? 'Staff workspace' : 'Investor workspace' }}</a>
                @endguest
            </nav>
        </header>
        <main id="main-content">{{ $slot }}</main>
        <footer class="site-footer">
            <div class="shell site-footer__main">
                <div class="footer-brand"><a class="brand brand--footer" href="{{ route('home') }}"><img class="brand__logo" src="{{ asset('images/gipa-logo.png') }}" alt=""><span class="brand__name"><strong>IOMP</strong><small>Investment Opportunities Mapping Project</small></span></a><p>A trusted national platform for discovering verified investment opportunities across Ghana.</p></div>
                <div class="footer-links"><strong>Explore</strong><a href="{{ route('opportunities.index') }}">Opportunities</a><a href="{{ route('districts.index') }}#investment-map">Investment map</a><a href="{{ route('districts.index') }}">Districts</a></div>
                <div class="footer-links"><strong>Support</strong><a href="mailto:info@example.gov.gh">info@example.gov.gh</a><a href="tel:+233000000000">+233 (0) 00 000 0000</a><span>Accra, Ghana</span></div>
                <div class="footer-links"><strong>Platform</strong><a href="{{ route('platform.guide') }}">Investor guide</a><a href="{{ route('api.documentation') }}">API documentation</a>@guest<a href="{{ route('login') }}">Investor login</a>@else<a href="{{ auth()->user()->isStaff() ? route('staff.dashboard') : route('investor.dashboard') }}">Workspace</a>@endguest</div>
            </div>
            <div class="shell site-footer__bottom"><span>&copy; {{ date('Y') }} IOMP. Republic of Ghana.</span><div><span>Privacy</span><span>Terms</span><span>Data policy</span></div></div>
        </footer>
        <div class="connection-status" data-connection-status role="status" aria-live="polite" hidden></div>
    </body>
</html>