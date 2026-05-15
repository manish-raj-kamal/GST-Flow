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
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            (function () {
                try {
                    if (localStorage.getItem('gst-sidebar-collapsed') === 'true') {
                        document.documentElement.classList.add('sidebar-collapsed-init');
                    }
                } catch (e) {}
            })();
        </script>
    </head>
    <body class="font-sans antialiased"
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: (() => {
                try {
                    return localStorage.getItem('gst-sidebar-collapsed') === 'true';
                } catch (e) {
                    return false;
                }
            })(),
            init() {
                document.documentElement.classList.toggle('sidebar-collapsed-init', this.sidebarCollapsed);
            },
            persistSidebarState() {
                try {
                    localStorage.setItem('gst-sidebar-collapsed', this.sidebarCollapsed);
                } catch (e) {}
            },
            toggleSidebarCollapsed() {
                this.sidebarCollapsed = !this.sidebarCollapsed;

                try {
                    localStorage.setItem('gst-sidebar-collapsed', this.sidebarCollapsed);
                } catch (e) {}

                document.documentElement.classList.toggle('sidebar-collapsed-init', this.sidebarCollapsed);
            },
        }"
        :class="{ 'is-sidebar-collapsed': sidebarCollapsed }">

        {{-- Mobile sidebar overlay --}}
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="sidebar-overlay"></div>

        {{-- Sidebar --}}
        @include('layouts.navigation')

        {{-- Main content --}}
        <div class="gst-main">
            {{-- Topbar --}}
            <header class="gst-topbar">
                <div class="flex min-w-0 items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="btn-ghost rounded-[20px] p-2 lg:hidden">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="min-w-0">
                        @isset($header)
                            {{ $header }}
                        @endisset
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-info-tip placement="bottom" class="hidden sm:inline-flex" text="Follow the left navigation as a GST workflow: create a business profile, add customers and products, create invoices, then review reports and GSTR summaries." />

                    {{-- User Menu --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex min-h-[44px] items-center gap-2 rounded-[20px] border border-transparent px-3 py-2 text-sm font-bold transition-all hover:bg-white/70 hover:shadow-[var(--shadow-card)]">
                            <div class="avatar-mark h-8 w-8">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 text-[#635F69]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 20 20"><path stroke-linecap="round" stroke-linejoin="round" d="M6 8l4 4 4-4"/></svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition
                            class="absolute right-0 mt-2 w-52 rounded-[24px] border bg-white/85 p-2 shadow-[var(--shadow-card)] backdrop-blur-xl" style="border-color: rgba(255,255,255,0.72);">
                            <a href="{{ route('profile.edit') }}" class="block rounded-[16px] px-4 py-2 text-sm font-bold text-[#332F3A] hover:bg-[#EFEBF5]">Profile Settings</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full rounded-[16px] px-4 py-2 text-left text-sm font-bold text-[#332F3A] hover:bg-[#EFEBF5]">Log Out</button>
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
                    const palette = {
                        success: { bg: '#065f46', fg: '#ffffff', border: 'rgba(255,255,255,0.26)' },
                        error: { bg: '#991b1b', fg: '#ffffff', border: 'rgba(255,255,255,0.26)' },
                        info: { bg: '#1e3a8a', fg: '#ffffff', border: 'rgba(255,255,255,0.26)' },
                    };
                    const tone = palette[type] || palette.success;
                    el.style.background = tone.bg;
                    el.style.color = tone.fg;
                    el.style.border = `1px solid ${tone.border}`;
                    el.style.boxShadow = '0 14px 28px rgba(16, 24, 40, 0.42)';
                    el.style.textShadow = '0 1px 1px rgba(0,0,0,0.3)';
                    container.appendChild(el);
                    setTimeout(() => el.remove(), 3200);
                },
                formatNumber(n) { return new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0); },
                formatDate(d) { if (!d) return '—'; return new Date(d).toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: 'numeric' }); },
            };
        </script>
    </body>
</html>
