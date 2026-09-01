<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#075b3b">
        <meta name="description" content="Discover verified investment opportunities across Ghana's regions and districts.">

        <title>IOMP | Ghana Investment Opportunities</title>

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
                <a class="brand" href="{{ url('/') }}" aria-label="IOMP home">
                    <span class="brand__seal" aria-hidden="true">IO</span>
                    <span class="brand__name"><strong>IOMP</strong><small>Investment Opportunities Mapping Project</small></span>
                </a>

                <nav class="desktop-nav" aria-label="Primary navigation">
                    <a href="{{ route('opportunities.index') }}">Opportunities</a>
                    <a href="#map">Investment map</a>
                    <a href="#districts">Districts</a>
                    <a href="#insights">Insights</a>
                </nav>

                <div class="header-actions">
                    <button class="icon-button" type="button" data-theme-toggle aria-label="Switch to dark mode" title="Change colour theme">
                        <svg class="theme-icon theme-icon--sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"></path></svg>
                        <svg class="theme-icon theme-icon--moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.7 13.2A8.5 8.5 0 1 1 10.8 3.3a6.5 6.5 0 0 0 9.9 9.9Z"></path></svg>
                    </button>
                    <a class="button button--outline header-login" href="{{ route('staff.login') }}">Staff login</a>
                    <button class="icon-button menu-button" type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-navigation" aria-label="Open navigation">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
                    </button>
                </div>
            </div>

            <nav class="mobile-nav" id="mobile-navigation" data-mobile-nav aria-label="Mobile navigation" hidden>
                <a href="{{ route('opportunities.index') }}">Opportunities</a>
                <a href="#map">Investment map</a>
                <a href="#districts">Districts</a>
                <a href="#insights">Insights</a>
                <a href="{{ route('staff.login') }}">Staff login</a>
            </nav>
        </header>

        <main id="main-content">
            <section class="hero" aria-labelledby="hero-title">
                <div class="hero__scene" aria-hidden="true">
                    <svg class="ghana-map" viewBox="0 0 440 620" role="presentation">
                        <defs>
                            <clipPath id="ghana-outline">
                                <path d="M420 489.4 274.3 542.5 222.6 573.7 138.9 600 56.1 574.2 60.3 538.4 20 460.2 44.2 357.6 83.4 281.4 58.7 152.2 46.1 83.8 48.2 32.3 209.7 28 250.7 34.7 280.7 20 323.7 27.2 316.9 55.5 355.6 102.4 355.5 168.3 364.3 239.8 387.7 272.9 367.1 354.7 374.5 399.9 399.3 457.5 420 489.4Z" />
                            </clipPath>
                            <linearGradient id="ghana-fill" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#14835a" />
                                <stop offset="1" stop-color="#07543a" />
                            </linearGradient>
                        </defs>
                        <path class="ghana-map__shadow" d="M420 489.4 274.3 542.5 222.6 573.7 138.9 600 56.1 574.2 60.3 538.4 20 460.2 44.2 357.6 83.4 281.4 58.7 152.2 46.1 83.8 48.2 32.3 209.7 28 250.7 34.7 280.7 20 323.7 27.2 316.9 55.5 355.6 102.4 355.5 168.3 364.3 239.8 387.7 272.9 367.1 354.7 374.5 399.9 399.3 457.5 420 489.4Z" />
                        <path class="ghana-map__land" d="M420 489.4 274.3 542.5 222.6 573.7 138.9 600 56.1 574.2 60.3 538.4 20 460.2 44.2 357.6 83.4 281.4 58.7 152.2 46.1 83.8 48.2 32.3 209.7 28 250.7 34.7 280.7 20 323.7 27.2 316.9 55.5 355.6 102.4 355.5 168.3 364.3 239.8 387.7 272.9 367.1 354.7 374.5 399.9 399.3 457.5 420 489.4Z" />
                        <g class="ghana-map__regions" clip-path="url(#ghana-outline)">
                            <path d="M-10 130C90 170 190 115 450 150M-10 250C100 210 245 300 450 235M-10 375C130 325 275 430 450 355M-10 490C120 450 250 525 450 470" />
                            <path d="M115-10C95 140 165 245 125 650M235-10C205 135 285 310 230 650M345-10C300 160 390 350 330 650" />
                        </g>
                        <path class="ghana-map__coast" d="M420 489.4 274.3 542.5 222.6 573.7 138.9 600 56.1 574.2" />
                    </svg>
                    <button class="map-pin map-pin--one" type="button" tabindex="-1"><i></i><span>Agro-processing<br><strong>GHS 24M</strong></span></button>
                    <button class="map-pin map-pin--two" type="button" tabindex="-1"><i></i><span>Solar infrastructure<br><strong>USD 8.2M</strong></span></button>
                    <button class="map-pin map-pin--three" type="button" tabindex="-1"><i></i><span>Logistics hub<br><strong>USD 12M</strong></span></button>
                    <div class="map-key"><span><i></i> Active opportunities</span><strong>Ghana</strong></div>
                </div>

                <div class="shell hero__content">
                    <p class="eyebrow"><span></span> Ghana's authoritative investment opportunity platform</p>
                    <h1 id="hero-title">Ghana's investment opportunities, <em>mapped.</em></h1>
                    <p class="hero__lead">Discover verified projects, regional strengths and investable opportunities across every district.</p>

                    <form class="opportunity-search" action="{{ route('opportunities.index') }}" method="get" role="search">
                        <label class="search-field">
                            <span class="sr-only">Search investment opportunities</span>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
                            <input type="search" name="query" placeholder="Search by opportunity, sector or district">
                        </label>
                        <label class="region-field">
                            <span class="sr-only">Region</span>
                            <select name="region">
                                <option value="">All regions</option>
                                @foreach($filters['regions'] as $region)<option value="{{ $region->uuid }}">{{ $region->name }}</option>@endforeach
                            </select>
                        </label>
                        <button class="button button--gold" type="submit">Explore opportunities</button>
                    </form>

                    <div class="hero__meta" aria-label="Platform summary">
                        <span><strong>16</strong> regions</span>
                        <span><strong>261</strong> districts</span>
                        <span><strong>120+</strong> mapped opportunities</span>
                    </div>
                </div>

                <a class="hero__scroll" href="#opportunities"><span>Explore the platform</span><i aria-hidden="true"></i></a>
            </section>

            <section class="trust-strip" aria-label="Platform qualities">
                <div class="shell trust-strip__inner">
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 7 3v5c0 4.7-2.9 8-7 10-4.1-2-7-5.3-7-10V6l7-3Z"></path><path d="m9 12 2 2 4-4"></path></svg> Verified public data</span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18"></path></svg> Nationwide coverage</span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V9l8-5 8 5v11M8 20v-6h8v6"></path></svg> Government-backed</span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"></path></svg> Data-led decisions</span>
                </div>
            </section>

            <section class="section section--opportunities" id="opportunities" aria-labelledby="opportunities-title">
                <div class="shell">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow eyebrow--dark">Investment pipeline</p>
                            <h2 id="opportunities-title">Featured opportunities</h2>
                            <p>Explore high-potential projects reviewed for investor discovery.</p>
                        </div>
                        <a class="text-link" href="{{ route('opportunities.index') }}">View all opportunities <span aria-hidden="true">→</span></a>
                    </div>

                    <div class="opportunity-grid">
                        <article class="opportunity-card">
                            <div class="opportunity-card__visual opportunity-card__visual--agri"><span class="status-pill">Active</span><span class="sector-icon" aria-hidden="true">Ag</span><div class="visual-bars"><i></i><i></i><i></i><i></i><i></i></div></div>
                            <div class="opportunity-card__body">
                                <p class="card-meta">Agriculture &amp; agro-processing</p>
                                <h3><a href="#">Integrated cassava processing facility</a></h3>
                                <p class="location"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg> Ejisu Municipal, Ashanti Region</p>
                                <div class="card-investment"><span>Investment required</span><strong>GHS 24M</strong></div>
                            </div>
                        </article>

                        <article class="opportunity-card">
                            <div class="opportunity-card__visual opportunity-card__visual--energy"><span class="status-pill">Active</span><span class="sector-icon" aria-hidden="true">En</span><div class="sun-disc"></div><div class="solar-lines"></div></div>
                            <div class="opportunity-card__body">
                                <p class="card-meta">Renewable energy</p>
                                <h3><a href="#">Utility-scale solar energy park</a></h3>
                                <p class="location"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg> Savelugu Municipal, Northern Region</p>
                                <div class="card-investment"><span>Investment required</span><strong>USD 8.2M</strong></div>
                            </div>
                        </article>

                        <article class="opportunity-card">
                            <div class="opportunity-card__visual opportunity-card__visual--logistics"><span class="status-pill">Active</span><span class="sector-icon" aria-hidden="true">Lo</span><div class="route-line"><i></i><i></i><i></i></div></div>
                            <div class="opportunity-card__body">
                                <p class="card-meta">Transport &amp; logistics</p>
                                <h3><a href="#">Western corridor logistics hub</a></h3>
                                <p class="location"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg> Takoradi, Western Region</p>
                                <div class="card-investment"><span>Investment required</span><strong>USD 12M</strong></div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="map-showcase" id="map" aria-labelledby="map-title">
                <div class="map-showcase__canvas" aria-hidden="true"><div class="map-showcase__grid"></div><span class="district-shape district-shape--a"></span><span class="district-shape district-shape--b"></span><span class="district-shape district-shape--c"></span><span class="showcase-pin showcase-pin--a"></span><span class="showcase-pin showcase-pin--b"></span><span class="showcase-pin showcase-pin--c"></span><span class="showcase-pin showcase-pin--d"></span></div>
                <div class="shell map-showcase__inner">
                    <div class="map-showcase__content">
                        <p class="eyebrow"><span></span> Geospatial intelligence</p>
                        <h2 id="map-title">See where opportunity lives.</h2>
                        <p>Explore projects by region, district, sector, status and investment size through a single national view.</p>
                        <a class="button button--gold" href="#">Open investment map</a>
                    </div>
                    <div class="map-showcase__facts" id="districts"><div><strong>261</strong><span>district profiles</span></div><div><strong>16</strong><span>regional economies</span></div><div><strong>1</strong><span>connected investment view</span></div></div>
                </div>
            </section>

            <section class="section sectors" id="insights" aria-labelledby="sectors-title">
                <div class="shell">
                    <div class="section-heading section-heading--center"><div><p class="eyebrow eyebrow--dark">Priority sectors</p><h2 id="sectors-title">Invest in Ghana's growth story</h2><p>Move from national potential to district-level opportunity.</p></div></div>
                    <div class="sector-list">
                        <a href="#"><span class="sector-list__number">01</span><strong>Agriculture</strong><small>32 opportunities</small><i aria-hidden="true">→</i></a>
                        <a href="#"><span class="sector-list__number">02</span><strong>Renewable energy</strong><small>18 opportunities</small><i aria-hidden="true">→</i></a>
                        <a href="#"><span class="sector-list__number">03</span><strong>Infrastructure</strong><small>24 opportunities</small><i aria-hidden="true">→</i></a>
                        <a href="#"><span class="sector-list__number">04</span><strong>Tourism &amp; culture</strong><small>16 opportunities</small><i aria-hidden="true">→</i></a>
                    </div>
                </div>
            </section>

            <section class="investor-cta" aria-labelledby="cta-title">
                <div class="shell investor-cta__inner">
                    <div><p class="eyebrow"><span></span> Investor support</p><h2 id="cta-title">Found an opportunity worth exploring?</h2><p>Connect with the right team and take the next informed step.</p></div>
                    <a class="button button--light" href="{{ route('opportunities.index') }}">Find an opportunity</a>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <div class="shell site-footer__main">
                <div class="footer-brand">
                    <a class="brand brand--footer" href="{{ url('/') }}"><span class="brand__seal" aria-hidden="true">IO</span><span class="brand__name"><strong>IOMP</strong><small>Investment Opportunities Mapping Project</small></span></a>
                    <p>A trusted national platform for discovering investment opportunities across Ghana.</p>
                    <button class="install-button" type="button" data-install-app hidden>Install IOMP app</button>
                </div>
                <div class="footer-links"><strong>Explore</strong><a href="#opportunities">Opportunities</a><a href="#map">Investment map</a><a href="#districts">Districts</a></div>
                <div class="footer-links"><strong>Resources</strong><a href="#insights">Insights</a><a href="#">API documentation</a><a href="#">Accessibility</a></div>
                <div class="footer-links"><strong>Contact</strong><a href="mailto:info@example.gov.gh">info@example.gov.gh</a><a href="tel:+233000000000">+233 (0) 00 000 0000</a><span>Accra, Ghana</span></div>
            </div>
            <div class="shell site-footer__bottom"><span>&copy; {{ date('Y') }} IOMP. Republic of Ghana.</span><div><a href="#">Privacy</a><a href="#">Terms</a><a href="#">Data policy</a></div></div>
        </footer>

        <div class="connection-status" data-connection-status role="status" aria-live="polite" hidden></div>
    </body>
</html>