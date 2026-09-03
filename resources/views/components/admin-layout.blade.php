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
                <nav class="admin-navigation" aria-label="Staff navigation">
                    <details class="admin-nav-group" data-nav-group="overview" @if(request()->routeIs('staff.dashboard')) open @endif>
                        <summary><i data-lucide="layout-dashboard" aria-hidden="true"></i><span>Overview</span><i data-lucide="chevron-down" aria-hidden="true"></i></summary>
                        <div class="admin-nav-group__items">
                            <a href="{{ route('staff.dashboard') }}" @class(['is-current' => request()->routeIs('staff.dashboard')])>Dashboard</a>
                        </div>
                    </details>
                    <details class="admin-nav-group" data-nav-group="opportunities" @if(request()->routeIs('staff.opportunity-workspace', 'staff.regions.*', 'staff.opportunities.*', 'staff.districts.*', 'staff.reference-data.*')) open @endif>
                        <summary><i data-lucide="briefcase-business" aria-hidden="true"></i><span>Opportunities</span><i data-lucide="chevron-down" aria-hidden="true"></i></summary>
                        <div class="admin-nav-group__items">
                            <a href="{{ route('staff.opportunity-workspace') }}" @class(['is-current' => request()->routeIs('staff.opportunity-workspace')])>Overview</a>
                            <a href="{{ route('staff.regions.index') }}" @class(['is-current' => request()->routeIs('staff.regions.*')])>Region list</a>
                            <a href="{{ route('staff.districts.overview') }}" @class(['is-current' => request()->routeIs('staff.districts.overview')])>District overview</a>
                            <a href="{{ route('staff.districts.index') }}" @class(['is-current' => request()->routeIs('staff.districts.index', 'staff.districts.show', 'staff.districts.create', 'staff.districts.edit')])>District list</a>
                            <a href="{{ route('staff.opportunities.overview') }}" @class(['is-current' => request()->routeIs('staff.opportunities.overview')])>Opportunity overview</a>
                            <a href="{{ route('staff.opportunities.index') }}" @class(['is-current' => request()->routeIs('staff.opportunities.index', 'staff.opportunities.show', 'staff.opportunities.create', 'staff.opportunities.edit')])>Opportunity list</a>
                            @can('opportunities.submit')
                                <span class="admin-nav-label">Reference data</span>
                                <a href="{{ route('staff.reference-data.index') }}" @class(['is-current' => request()->routeIs('staff.reference-data.index')])>Reference overview</a>
                                <a href="{{ route('staff.reference-data.section', 'sectors') }}" @class(['is-current' => request()->routeIs('staff.reference-data.section') && request()->route('section') === 'sectors'])>Sector list</a>
                                <a href="{{ route('staff.reference-data.section', 'sub-sectors') }}" @class(['is-current' => request()->routeIs('staff.reference-data.section') && request()->route('section') === 'sub-sectors'])>Sub-sector list</a>
                                <a href="{{ route('staff.reference-data.section', 'enterprise-types') }}" @class(['is-current' => request()->routeIs('staff.reference-data.section') && request()->route('section') === 'enterprise-types'])>Enterprise type list</a>
                            @endcan
                        </div>
                    </details>
                    @can('investors.view')
                        <details class="admin-nav-group" data-nav-group="investments" @if(request()->routeIs('staff.investments.*', 'staff.investors.*', 'staff.inquiries.*')) open @endif>
                            <summary><i data-lucide="landmark" aria-hidden="true"></i><span>Investments</span><i data-lucide="chevron-down" aria-hidden="true"></i></summary>
                            <div class="admin-nav-group__items">
                                <a href="{{ route('staff.investments.overview') }}" @class(['is-current' => request()->routeIs('staff.investments.*')])>Overview</a>
                                <a href="{{ route('staff.investors.overview') }}" @class(['is-current' => request()->routeIs('staff.investors.overview')])>Investor overview</a>
                                <a href="{{ route('staff.investors.index') }}" @class(['is-current' => request()->routeIs('staff.investors.index', 'staff.investors.show')])>Investor list</a>
                                <a href="{{ route('staff.inquiries.index') }}" @class(['is-current' => request()->routeIs('staff.inquiries.*')])>Inquiry list</a>
                            </div>
                        </details>
                    @endcan
                    @can('certificates.view')
                        <details class="admin-nav-group" data-nav-group="certificates" @if(request()->routeIs('staff.certificates.*')) open @endif>
                            <summary><i data-lucide="badge-check" aria-hidden="true"></i><span>Certificates</span><i data-lucide="chevron-down" aria-hidden="true"></i></summary>
                            <div class="admin-nav-group__items">
                                <a href="{{ route('staff.certificates.overview') }}" @class(['is-current' => request()->routeIs('staff.certificates.overview')])>Overview</a>
                                <a href="{{ route('staff.certificates.index') }}" @class(['is-current' => request()->routeIs('staff.certificates.index', 'staff.certificates.show')])>Registry list</a>
                                @can('certificates.issue')<a href="{{ route('staff.certificates.create') }}" @class(['is-current' => request()->routeIs('staff.certificates.create')])>Prepare certificate</a>@endcan
                            </div>
                        </details>
                    @endcan
                    <details class="admin-nav-group" data-nav-group="notifications" @if(request()->routeIs('staff.notifications.*')) open @endif>
                        <summary><i data-lucide="bell" aria-hidden="true"></i><span>Notifications</span><i data-lucide="chevron-down" aria-hidden="true"></i></summary>
                        <div class="admin-nav-group__items">
                            <a href="{{ route('staff.notifications.overview') }}" @class(['is-current' => request()->routeIs('staff.notifications.overview')])>Overview</a>
                            <a href="{{ route('staff.notifications.index') }}" @class(['is-current' => request()->routeIs('staff.notifications.index')])>Notification list</a>
                        </div>
                    </details>
                    <details class="admin-nav-group" data-nav-group="guidance" @if(request()->routeIs('staff.guide')) open @endif>
                        <summary><i data-lucide="files" aria-hidden="true"></i><span>Guidance</span><i data-lucide="chevron-down" aria-hidden="true"></i></summary>
                        <div class="admin-nav-group__items">
                            <a href="{{ route('staff.guide') }}" @class(['is-current' => request()->routeIs('staff.guide')])>Staff user guide</a>
                        </div>
                    </details>
                    @role('Super Administrator')
                        <details class="admin-nav-group" data-nav-group="users" @if(request()->routeIs('staff.users.*')) open @endif>
                            <summary><i data-lucide="users-round" aria-hidden="true"></i><span>Users &amp; access</span><i data-lucide="chevron-down" aria-hidden="true"></i></summary>
                            <div class="admin-nav-group__items">
                                <a href="{{ route('staff.users.overview') }}" @class(['is-current' => request()->routeIs('staff.users.overview')])>Overview</a>
                                <a href="{{ route('staff.users.staff') }}" @class(['is-current' => request()->routeIs('staff.users.staff')])>Staff list</a>
                                <a href="{{ route('staff.certificate-assignments.index') }}" @class(['is-current' => request()->routeIs('staff.certificate-assignments.*')])>District assignments</a>
                                <a href="{{ route('staff.users.roles') }}" @class(['is-current' => request()->routeIs('staff.users.roles')])>Roles</a>
                                <a href="{{ route('staff.users.permissions') }}" @class(['is-current' => request()->routeIs('staff.users.permissions')])>Permissions</a>
                            </div>
                        </details>
                    @endrole
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