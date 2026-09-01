@props([
    'title',
    'eyebrow',
    'heading',
    'description',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} | IOMP</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|manrope:600,700,800" rel="stylesheet">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="login-body">
        <main class="login-layout">
            <section class="login-context">
                <a class="admin-brand" href="{{ route('home') }}">
                    <img src="{{ asset('images/gipa-logo.png') }}" alt="">
                    <span><strong>IOMP</strong><small>Investment Opportunities Mapping Project</small></span>
                </a>
                <div>
                    <p class="eyebrow"><span></span> Secure access</p>
                    <h1>{{ $heading }}</h1>
                    <p>{{ $description }}</p>
                </div>
                <small>Official platform of the Republic of Ghana</small>
            </section>
            <section class="login-panel">
                <div class="login-panel__inner">
                    <p class="eyebrow eyebrow--dark">{{ $eyebrow }}</p>
                    {{ $slot }}
                </div>
            </section>
        </main>
    </body>
</html>