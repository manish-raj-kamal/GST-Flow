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
            // Login = indigo/blue tone, Register = emerald/green tone
            $scheme = $colorScheme ?? 'indigo';
            $schemes = [
                'indigo' => [
                    'bg'      => 'linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%)',
                    'accent'  => '#818cf8',
                    'glow'    => 'rgba(129, 140, 248, 0.15)',
                    'btn'     => 'linear-gradient(135deg, #6366f1, #4f46e5)',
                    'btnHover'=> '0 8px 32px rgba(99, 102, 241, 0.45)',
                    'ring'    => 'rgba(99, 102, 241, 0.4)',
                    'text'    => '#c7d2fe',
                ],
                'emerald' => [
                    'bg'      => 'linear-gradient(135deg, #022c22 0%, #064e3b 40%, #047857 100%)',
                    'accent'  => '#6ee7b7',
                    'glow'    => 'rgba(110, 231, 183, 0.12)',
                    'btn'     => 'linear-gradient(135deg, #10b981, #059669)',
                    'btnHover'=> '0 8px 32px rgba(16, 185, 129, 0.45)',
                    'ring'    => 'rgba(16, 185, 129, 0.4)',
                    'text'    => '#a7f3d0',
                ],
            ];
            $s = $schemes[$scheme] ?? $schemes['indigo'];
        @endphp

        <style>
            .auth-shell {
                background: {{ $s['bg'] }};
                min-height: 100vh;
                position: relative;
                overflow: hidden;
            }
            .auth-shell::before {
                content: '';
                position: absolute;
                top: -40%; right: -20%;
                width: 700px; height: 700px;
                border-radius: 50%;
                background: {{ $s['glow'] }};
                filter: blur(100px);
                pointer-events: none;
            }
            .auth-shell::after {
                content: '';
                position: absolute;
                bottom: -30%; left: -15%;
                width: 500px; height: 500px;
                border-radius: 50%;
                background: {{ $s['glow'] }};
                filter: blur(120px);
                pointer-events: none;
            }
            .auth-card {
                background: rgba(255,255,255,0.06);
                backdrop-filter: blur(24px);
                -webkit-backdrop-filter: blur(24px);
                border: 1px solid rgba(255,255,255,0.1);
                border-radius: 24px;
                padding: 40px;
                width: 100%;
                max-width: 440px;
                position: relative;
                z-index: 1;
            }
            .auth-card input[type="text"],
            .auth-card input[type="email"],
            .auth-card input[type="password"] {
                width: 100%;
                padding: 12px 16px;
                border-radius: 12px;
                border: 1px solid rgba(255,255,255,0.12);
                background: rgba(255,255,255,0.06);
                color: #fff;
                font-size: 14px;
                outline: none;
                transition: all 200ms;
            }
            .auth-card input:focus {
                border-color: {{ $s['accent'] }};
                box-shadow: 0 0 0 3px {{ $s['ring'] }};
            }
            .auth-card input::placeholder { color: rgba(255,255,255,0.35); }
            .auth-label { display: block; font-size: 13px; font-weight: 500; color: {{ $s['text'] }}; margin-bottom: 6px; }
            .auth-btn {
                width: 100%;
                padding: 13px;
                border-radius: 12px;
                border: none;
                background: {{ $s['btn'] }};
                color: #fff;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                transition: all 200ms;
                box-shadow: 0 4px 16px {{ $s['ring'] }};
            }
            .auth-btn:hover { transform: translateY(-1px); box-shadow: {{ $s['btnHover'] }}; }
            .auth-divider {
                display: flex; align-items: center; gap: 12px;
                margin: 20px 0; color: rgba(255,255,255,0.3); font-size: 12px;
            }
            .auth-divider::before, .auth-divider::after {
                content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.1);
            }
            .social-btn {
                width: 100%;
                padding: 12px;
                border-radius: 12px;
                border: 1px solid rgba(255,255,255,0.12);
                background: rgba(255,255,255,0.06);
                color: #fff;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 200ms;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                text-decoration: none;
            }
            .social-btn:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); }
            .auth-error { color: #fca5a5; font-size: 12px; margin-top: 4px; }
            .auth-link { color: {{ $s['accent'] }}; text-decoration: none; font-size: 13px; font-weight: 500; transition: opacity 150ms; }
            .auth-link:hover { opacity: 0.8; }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="auth-shell flex items-center justify-center px-4 py-8">
            <div class="flex flex-col items-center w-full" style="position: relative; z-index: 1;">
                {{-- Logo --}}
                <a href="/" class="flex items-center gap-3 mb-8">
                    <div style="width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; color: #fff; background: {{ $s['btn'] }}; box-shadow: 0 4px 20px {{ $s['ring'] }};">GST</div>
                    <span style="font-size: 20px; font-weight: 700; color: #fff; letter-spacing: -0.02em;">{{ config('app.name') }}</span>
                </a>

                <div class="auth-card">
                    {{ $slot }}
                </div>

                <p style="margin-top: 24px; font-size: 12px; color: rgba(255,255,255,0.25);">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </body>
</html>
