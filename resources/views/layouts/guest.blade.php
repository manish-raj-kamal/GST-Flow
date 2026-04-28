<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $pageTitle ?? config('app.name', 'GST Platform') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @php
            $scheme = $colorScheme ?? 'indigo';
            $schemes = [
                'indigo' => [
                    'primary' => '37, 99, 235',
                    'secondary' => '20, 184, 166',
                    'tertiary' => '245, 158, 11',
                    'headline' => 'Welcome back to your GST workspace',
                    'subline' => 'Pick up invoices, reports, and validation from the same desk.',
                ],
                'emerald' => [
                    'primary' => '5, 150, 105',
                    'secondary' => '14, 165, 233',
                    'tertiary' => '249, 115, 22',
                    'headline' => 'Start with a clean compliance desk',
                    'subline' => 'Create your account and keep GST operations moving without clutter.',
                ],
            ];
            $s = $schemes[$scheme] ?? $schemes['indigo'];
        @endphp
    </head>
    <body class="font-sans antialiased">
        <div class="auth-shell"
            style="--auth-primary: {{ $s['primary'] }}; --auth-secondary: {{ $s['secondary'] }}; --auth-tertiary: {{ $s['tertiary'] }};">
            <div class="auth-floating-layer" aria-hidden="true">
                <span class="auth-float auth-float-one"></span>
                <span class="auth-float auth-float-two"></span>
                <span class="auth-float auth-float-three"></span>
            </div>

            <div class="auth-stage">
                <section class="auth-story" aria-label="{{ config('app.name') }}">
                    <a href="/" class="auth-brand">
                        <span class="auth-mark">GST</span>
                        <span>{{ config('app.name') }}</span>
                    </a>

                    <div class="auth-story-copy">
                        <p class="auth-eyebrow">GST Platform</p>
                        <h1>{{ $s['headline'] }}</h1>
                        <p>{{ $s['subline'] }}</p>
                    </div>

                    <div class="auth-orbit" aria-hidden="true">
                        <span class="auth-orbit-ring auth-orbit-ring-lg"></span>
                        <span class="auth-orbit-ring auth-orbit-ring-md"></span>
                        <span class="auth-orbit-ring auth-orbit-ring-sm"></span>
                        <div class="auth-core">
                            <span class="auth-core-value">GST</span>
                            <span class="auth-core-caption">READY</span>
                        </div>
                    </div>
                </section>

                <main class="auth-panel">
                    <a href="/" class="auth-mobile-brand">
                        <span class="auth-mark">GST</span>
                        <span>{{ config('app.name') }}</span>
                    </a>

                    <div class="auth-card">
                        {{ $slot }}
                    </div>

                    <p class="auth-copyright">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                </main>
            </div>
        </div>
    </body>
</html>
