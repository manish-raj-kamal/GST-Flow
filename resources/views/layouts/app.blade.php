<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="GST compliance and transaction management platform — invoices, tax calculation, GSTIN validation, reports, and analytics.">

        <title>{{ isset($pageTitle) ? $pageTitle . ' — ' : '' }}{{ config('app.name', 'GST Platform') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased" x-data="{ sidebarOpen: false }">

        {{-- Mobile sidebar overlay --}}
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="sidebar-overlay"></div>

        {{-- Sidebar --}}
        @include('layouts.navigation')

        {{-- Main content --}}
        <div class="gst-main">
            {{-- Topbar --}}
            <header class="gst-topbar">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="btn-ghost rounded-lg p-2 lg:hidden">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    @isset($header)
                        {{ $header }}
                    @endisset
                </div>

                <div class="flex items-center gap-3">
                    {{-- User Menu --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 rounded-xl border border-transparent px-3 py-2 text-sm font-medium text-slate-600 transition-all hover:bg-slate-100">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-500 text-xs font-bold text-white">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 20 20"><path stroke-linecap="round" stroke-linejoin="round" d="M6 8l4 4 4-4"/></svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition
                            class="absolute right-0 mt-2 w-48 rounded-xl border bg-white py-1 shadow-lg" style="border-color: hsl(var(--gst-border));">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profile Settings</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Log Out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main>
                {{ $slot }}
            </main>
        </div>

        {{-- Global Toast --}}
        <div id="toast-container"></div>

        <script>
            window.gst = {
                csrfToken: document.querySelector('meta[name="csrf-token"]').content,
                async api(url, options = {}) {
                    const res = await fetch('/api' + url, {
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            ...options.headers,
                        },
                        credentials: 'same-origin',
                        ...options,
                    });
                    if (!res.ok) {
                        const err = await res.json().catch(() => ({}));
                        throw { status: res.status, message: err.message || 'Request failed', errors: err.errors };
                    }
                    if (res.status === 204) return null;
                    const contentType = res.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) return res.json();
                    return res;
                },
                toast(message, type = 'success') {
                    const container = document.getElementById('toast-container');
                    const el = document.createElement('div');
                    el.className = `toast toast-${type}`;
                    el.textContent = message;
                    container.appendChild(el);
                    setTimeout(() => el.remove(), 3200);
                },
                formatNumber(n) { return new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0); },
                formatDate(d) { if (!d) return '—'; return new Date(d).toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: 'numeric' }); },
            };
        </script>
    </body>
</html>
