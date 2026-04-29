<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — GST Compliance Operations Platform</title>
    <meta name="description" content="Run GST invoicing, tax computation, GSTIN checks, and filing-ready reporting from one production-ready operations platform.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name') }} — GST Compliance Operations Platform">
    <meta property="og:description" content="Automate invoices, tax logic, and GST reporting with one unified workflow.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('favicon.ico') }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ config('app.name') }} — GST Compliance Operations Platform">
    <meta name="twitter:description" content="Automate invoices, tax logic, and GST reporting with one unified workflow.">
    <meta name="twitter:image" content="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --space-1: 8px;
            --space-2: 16px;
            --space-3: 24px;
            --space-4: 32px;
            --space-5: 40px;
            --space-6: 48px;
            --space-7: 56px;
            --space-8: 64px;
            --space-9: 72px;
            --space-10: 80px;
            --space-11: 88px;
            --space-12: 96px;

            --radius-sm: 12px;
            --radius-md: 16px;
            --radius-lg: 24px;

            --bg: #f8fafc;
            --surface: #ffffff;
            --surface-muted: #f1f5f9;
            --text: #0f172a;
            --text-muted: #475569;
            --border: #e2e8f0;
            --brand: #f59e0b;
            --brand-strong: #d97706;
            --accent: #0ea5e9;
            --shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 10px 32px rgba(15, 23, 42, 0.07);
            --shadow-strong: 0 1px 2px rgba(15, 23, 42, 0.05), 0 20px 48px rgba(15, 23, 42, 0.12);
            --duration-fast: 180ms;
            --duration-base: 320ms;
            --duration-slow: 520ms;

            --scroll-progress: 0;
            --shape-shift-y: 0px;
            --shape-tilt: 0deg;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Quicksand', system-ui, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: var(--text);
            background:
                radial-gradient(1200px 480px at -10% -20%, rgba(245, 158, 11, 0.14), transparent 72%),
                radial-gradient(1000px 500px at 115% 5%, rgba(14, 165, 233, 0.1), transparent 72%),
                var(--bg);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        .page-layer {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

        .shape {
            position: absolute;
            border: 1px solid rgba(148, 163, 184, 0.28);
            background: rgba(255, 255, 255, 0.55);
            box-shadow: var(--shadow);
            backdrop-filter: blur(6px);
            transform: translate3d(0, calc(var(--shape-shift-y) * var(--speed)), 0) rotate(calc(var(--shape-tilt) * var(--dir)));
            transition: border-radius var(--duration-slow) ease;
            will-change: transform, border-radius;
        }

        .shape-a {
            top: 120px;
            left: 6%;
            width: 176px;
            height: 176px;
            --speed: 0.48;
            --dir: 1;
            border-radius: calc(32px + (var(--scroll-progress) * 48px)) calc(96px - (var(--scroll-progress) * 40px)) 32px 64px;
        }

        .shape-b {
            top: 420px;
            right: 9%;
            width: 144px;
            height: 144px;
            --speed: -0.34;
            --dir: -1;
            border-radius: calc(80px - (var(--scroll-progress) * 32px)) 24px calc(88px - (var(--scroll-progress) * 28px)) 40px;
        }

        .shape-c {
            bottom: 160px;
            left: 28%;
            width: 120px;
            height: 120px;
            --speed: 0.26;
            --dir: 1;
            border-radius: calc(32px + (var(--scroll-progress) * 20px));
        }

        .container {
            width: min(1200px, 100% - (var(--space-6)));
            margin-inline: auto;
            position: relative;
            z-index: 1;
        }

        .section {
            padding-block: var(--space-11);
        }

        .section-head {
            margin-bottom: var(--space-6);
            max-width: 640px;
        }

        .kicker {
            font-size: 12px;
            line-height: 16px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            font-weight: 700;
            color: #b45309;
            margin-bottom: var(--space-2);
        }

        h1, h2, h3, h4 {
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text);
        }

        h1 {
            font-size: clamp(40px, 7vw, 64px);
            line-height: 1.05;
        }

        h2 {
            font-size: clamp(32px, 4.8vw, 44px);
            line-height: 1.1;
        }

        h3 {
            font-size: 24px;
            line-height: 1.2;
        }

        h4 {
            font-size: 18px;
            line-height: 1.3;
        }

        p {
            font-size: 16px;
            line-height: 24px;
            color: var(--text-muted);
        }

        .nav-wrap {
            position: sticky;
            top: 0;
            z-index: 20;
            backdrop-filter: blur(12px);
            background: rgba(248, 250, 252, 0.82);
            border-bottom: 1px solid rgba(226, 232, 240, 0.75);
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-block: var(--space-2);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            text-decoration: none;
            color: var(--text);
            font-weight: 700;
            font-size: 18px;
            line-height: 24px;
        }

        .brand-mark {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 12px;
            line-height: 16px;
            background: linear-gradient(135deg, var(--brand), var(--brand-strong));
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.34);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: var(--space-1);
        }

        .nav-link {
            text-decoration: none;
            color: #334155;
            font-size: 14px;
            line-height: 20px;
            font-weight: 600;
            border-radius: var(--radius-sm);
            padding: 10px var(--space-2);
            transition: background var(--duration-fast), color var(--duration-fast), border-color var(--duration-fast);
            border: 1px solid transparent;
        }

        .nav-link:hover {
            background: #ffffff;
            border-color: var(--border);
            color: #0f172a;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            font-size: 14px;
            line-height: 20px;
            font-weight: 700;
            text-decoration: none;
            padding: 10px var(--space-3);
            transition: transform var(--duration-fast), box-shadow var(--duration-fast), background var(--duration-fast), color var(--duration-fast), border-color var(--duration-fast);
            border: 1px solid transparent;
        }

        .btn:active {
            transform: translateY(1px);
        }

        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--brand), var(--brand-strong));
            box-shadow: 0 12px 28px rgba(245, 158, 11, 0.26);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 32px rgba(245, 158, 11, 0.34);
        }

        .btn-secondary {
            color: #0f172a;
            background: #ffffff;
            border-color: var(--border);
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }

        .hero {
            padding-top: var(--space-12);
            padding-bottom: var(--space-10);
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: var(--space-8);
            align-items: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid #fcd34d;
            color: #92400e;
            background: #fef3c7;
            padding: 6px var(--space-2);
            font-size: 12px;
            line-height: 16px;
            letter-spacing: 0.1em;
            font-weight: 700;
            text-transform: uppercase;
        }

        .hero-copy h1 {
            margin-top: var(--space-3);
        }

        .hero-copy p {
            margin-top: var(--space-3);
            max-width: 560px;
        }

        .hero-cta {
            margin-top: var(--space-4);
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-2);
        }

        .hero-panel {
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.9);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-strong);
            padding: var(--space-4);
            position: relative;
            overflow: hidden;
        }

        .hero-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(125deg, rgba(245, 158, 11, 0.12), transparent 48%);
            pointer-events: none;
        }

        .hero-panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-3);
        }

        .hero-panel-head span {
            font-size: 12px;
            line-height: 16px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
        }

        .hero-panel-head strong {
            font-size: 14px;
            line-height: 20px;
            color: #0f172a;
            font-weight: 700;
        }

        .panel-bars {
            display: grid;
            gap: var(--space-2);
        }

        .bar {
            height: 12px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .bar > i {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--brand), var(--accent));
            transform-origin: left center;
            transform: scaleX(calc(0.78 + (var(--scroll-progress) * 0.22)));
            transition: transform var(--duration-base) ease;
        }

        .hero-micro-grid {
            margin-top: var(--space-4);
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-2);
        }

        .micro-card {
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            padding: var(--space-2);
            background: #ffffff;
        }

        .micro-card strong {
            display: block;
            font-size: 20px;
            line-height: 24px;
            color: #0f172a;
        }

        .micro-card span {
            display: block;
            margin-top: var(--space-1);
            color: #64748b;
            font-size: 13px;
            line-height: 16px;
            font-weight: 600;
        }

        .trust-strip {
            border-block: 1px solid var(--border);
            padding-block: var(--space-3);
            background: rgba(255, 255, 255, 0.55);
        }

        .trust-list {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--space-2);
        }

        .trust-item {
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--surface);
            padding: var(--space-2);
            text-align: center;
            font-size: 13px;
            line-height: 16px;
            color: #475569;
            font-weight: 600;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .card {
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            background: var(--surface);
            padding: var(--space-4);
            box-shadow: var(--shadow);
        }

        .card p {
            margin-top: var(--space-2);
        }

        .step-index {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: #fffbeb;
            color: #b45309;
            font-size: 13px;
            line-height: 16px;
            font-weight: 700;
            border: 1px solid #fde68a;
            margin-bottom: var(--space-2);
        }

        .platform-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .platform-card {
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            background: #ffffff;
            padding: var(--space-4);
            box-shadow: var(--shadow);
        }

        .platform-card h4 {
            margin-bottom: var(--space-2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--space-3);
        }

        .stat {
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            background: #ffffff;
            padding: var(--space-3);
            text-align: center;
            box-shadow: var(--shadow);
        }

        .stat strong {
            display: block;
            font-size: 34px;
            line-height: 40px;
            color: #0f172a;
            font-weight: 700;
        }

        .stat span {
            display: block;
            margin-top: var(--space-1);
            font-size: 13px;
            line-height: 16px;
            color: #64748b;
            font-weight: 600;
        }

        .cta {
            padding-top: var(--space-10);
            padding-bottom: var(--space-12);
        }

        .cta-shell {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            background: linear-gradient(145deg, rgba(245, 158, 11, 0.12), rgba(255, 255, 255, 0.9));
            padding: var(--space-8);
            box-shadow: var(--shadow-strong);
            text-align: center;
        }

        .cta-shell p {
            margin-top: var(--space-2);
        }

        .cta-shell .hero-cta {
            justify-content: center;
        }

        footer {
            border-top: 1px solid var(--border);
            padding-block: var(--space-4);
        }

        .footer-row {
            display: flex;
            justify-content: space-between;
            gap: var(--space-3);
            align-items: center;
            flex-wrap: wrap;
        }

        .footer-row p {
            font-size: 13px;
            line-height: 16px;
            color: #64748b;
        }

        .footer-links {
            display: flex;
            gap: var(--space-2);
            flex-wrap: wrap;
        }

        .footer-links a {
            color: #334155;
            text-decoration: none;
            font-size: 13px;
            line-height: 16px;
            font-weight: 600;
        }

        .footer-links a:hover {
            color: #0f172a;
        }

        [data-reveal] {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity var(--duration-base) ease, transform var(--duration-base) ease;
        }

        [data-reveal].is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 1100px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: var(--space-5);
            }

            .grid-3 {
                grid-template-columns: 1fr;
            }

            .platform-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .trust-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .container {
                width: min(1200px, 100% - (var(--space-4)));
            }

            .section {
                padding-block: var(--space-8);
            }

            .hero {
                padding-top: var(--space-8);
                padding-bottom: var(--space-7);
            }

            .stats-grid,
            .trust-list {
                grid-template-columns: 1fr;
            }

            .cta-shell {
                padding: var(--space-5);
            }

            .shape {
                display: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            [data-reveal] {
                opacity: 1;
                transform: none;
                transition: none;
            }

            .shape {
                transform: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="page-layer" aria-hidden="true">
        <span class="shape shape-a"></span>
        <span class="shape shape-b"></span>
        <span class="shape shape-c"></span>
    </div>

    <header class="nav-wrap">
        <div class="container">
            <nav class="nav" aria-label="Main">
                <a class="brand" href="{{ url('/') }}">
                    <span class="brand-mark">GST</span>
                    <span>{{ config('app.name') }}</span>
                </a>
                <div class="nav-links">
                    <a href="#platform" class="nav-link">Platform</a>
                    <a href="#workflow" class="nav-link">Workflow</a>
                    <a href="#results" class="nav-link">Results</a>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary">Open Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-secondary">Log in</a>
                        <a href="{{ route('register') }}" class="btn btn-primary">Start Free</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-grid">
                <div class="hero-copy" data-reveal>
                    <span class="hero-badge">GST Operations Platform</span>
                    <h1>Run invoicing, tax logic and compliance reporting from one connected workspace.</h1>
                    <p>Move from transaction entry to filing-ready summaries without switching tools. {{ config('app.name') }} unifies invoice generation, GSTIN checks, tax computation, and exports for finance teams that need reliable day-to-day execution.</p>
                    <div class="hero-cta">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-primary">Continue to Dashboard</a>
                        @else
                            <a href="{{ route('register') }}" class="btn btn-primary">Create Account</a>
                            <a href="{{ route('login') }}" class="btn btn-secondary">Sign In</a>
                        @endauth
                    </div>
                </div>

                <aside class="hero-panel" data-reveal>
                    <div class="hero-panel-head">
                        <span>Live Operations Snapshot</span>
                        <strong>Current Month</strong>
                    </div>
                    <div class="panel-bars">
                        <div class="bar"><i style="width: 84%;"></i></div>
                        <div class="bar"><i style="width: 68%;"></i></div>
                        <div class="bar"><i style="width: 92%;"></i></div>
                        <div class="bar"><i style="width: 76%;"></i></div>
                    </div>
                    <div class="hero-micro-grid">
                        <div class="micro-card">
                            <strong>2.4 hrs</strong>
                            <span>Average reporting prep</span>
                        </div>
                        <div class="micro-card">
                            <strong>98.7%</strong>
                            <span>Validation pass rate</span>
                        </div>
                        <div class="micro-card">
                            <strong>18,420</strong>
                            <span>Invoices processed</span>
                        </div>
                        <div class="micro-card">
                            <strong>24 states</strong>
                            <span>Supply coverage</span>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="trust-strip">
            <div class="container">
                <div class="trust-list" data-reveal>
                    <div class="trust-item">CA & Accounting Teams</div>
                    <div class="trust-item">SMB Finance Operations</div>
                    <div class="trust-item">Multi-State Sellers</div>
                    <div class="trust-item">In-house GST Administrators</div>
                </div>
            </div>
        </section>

        <section id="workflow" class="section">
            <div class="container">
                <div class="section-head" data-reveal>
                    <p class="kicker">Workflow</p>
                    <h2>A structured process from invoice creation to compliance output.</h2>
                </div>
                <div class="grid-3">
                    <article class="card" data-reveal>
                        <span class="step-index">01</span>
                        <h4>Capture transaction data</h4>
                        <p>Create invoices with HSN mapping, customer GST details, and place-of-supply context built in from the first step.</p>
                    </article>
                    <article class="card" data-reveal>
                        <span class="step-index">02</span>
                        <h4>Apply GST logic automatically</h4>
                        <p>Calculate CGST, SGST, or IGST using deterministic rules so every invoice follows the same tax treatment model.</p>
                    </article>
                    <article class="card" data-reveal>
                        <span class="step-index">03</span>
                        <h4>Publish filing-ready reports</h4>
                        <p>Generate GSTR-aligned summaries and exports for internal review, audit checks, and monthly compliance handoff.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="platform" class="section">
            <div class="container">
                <div class="section-head" data-reveal>
                    <p class="kicker">Platform Surface</p>
                    <h2>Every core GST function available in one consistent system.</h2>
                </div>
                <div class="platform-grid">
                    <article class="platform-card" data-reveal>
                        <h4>Invoice Operations</h4>
                        <p>Create, version, and export invoices with line-level tax details and compliant formatting out of the box.</p>
                    </article>
                    <article class="platform-card" data-reveal>
                        <h4>Validation Engine</h4>
                        <p>Run GSTIN structure checks, state code extraction, and PAN matching before invoice approval.</p>
                    </article>
                    <article class="platform-card" data-reveal>
                        <h4>Analytics & Monitoring</h4>
                        <p>Track revenue, tax collection, customer spread, and monthly filing indicators from one dashboard.</p>
                    </article>
                    <article class="platform-card" data-reveal>
                        <h4>Controlled Access</h4>
                        <p>Manage role-based permissions for admins and business users while keeping audit activity visible.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="results" class="section">
            <div class="container">
                <div class="section-head" data-reveal>
                    <p class="kicker">Operational Outcomes</p>
                    <h2>Designed to make compliance work more predictable and less manual.</h2>
                </div>
                <div class="stats-grid">
                    <article class="stat" data-reveal>
                        <strong>20+</strong>
                        <span>Integrated GST modules</span>
                    </article>
                    <article class="stat" data-reveal>
                        <strong>30+</strong>
                        <span>API endpoints in production</span>
                    </article>
                    <article class="stat" data-reveal>
                        <strong>36</strong>
                        <span>State-code validation matrix</span>
                    </article>
                    <article class="stat" data-reveal>
                        <strong>5</strong>
                        <span>Tax slab presets maintained</span>
                    </article>
                </div>
            </div>
        </section>

        <section class="cta">
            <div class="container">
                <div class="cta-shell" data-reveal>
                    <h2>Adopt a production-grade GST operations stack.</h2>
                    <p>Use one platform for invoice throughput, tax consistency, and reporting readiness across your full finance cycle.</p>
                    <div class="hero-cta">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-primary">Open Dashboard</a>
                        @else
                            <a href="{{ route('register') }}" class="btn btn-primary">Create Free Account</a>
                            <a href="{{ route('login') }}" class="btn btn-secondary">Sign In</a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-row">
                <p>© {{ date('Y') }} {{ config('app.name') }}. GST compliance platform built for daily finance operations.</p>
                <div class="footer-links">
                    <a href="{{ url('/') }}">Home</a>
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}">Register</a>
                    <a href="https://github.com/manish-raj-kamal/GST-Flow" target="_blank" rel="noopener noreferrer">GitHub</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        (() => {
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const revealNodes = [...document.querySelectorAll('[data-reveal]')];

            if (!prefersReducedMotion) {
                const observer = new IntersectionObserver((entries) => {
                    for (const entry of entries) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                        }
                    }
                }, { threshold: 0.18, rootMargin: '0px 0px -48px 0px' });

                revealNodes.forEach((node) => observer.observe(node));
            } else {
                revealNodes.forEach((node) => node.classList.add('is-visible'));
            }

            if (prefersReducedMotion) return;

            const rootStyle = document.documentElement.style;
            let ticking = false;

            const updateScrollVisuals = () => {
                const scrollY = window.scrollY || window.pageYOffset;
                const limit = Math.max(document.body.scrollHeight - window.innerHeight, 1);
                const progress = Math.min(scrollY / limit, 1);
                const shiftY = (progress - 0.5) * 80;
                const tilt = (progress - 0.5) * 28;

                rootStyle.setProperty('--scroll-progress', progress.toFixed(4));
                rootStyle.setProperty('--shape-shift-y', `${shiftY.toFixed(2)}px`);
                rootStyle.setProperty('--shape-tilt', `${tilt.toFixed(2)}deg`);
                ticking = false;
            };

            const requestTick = () => {
                if (!ticking) {
                    window.requestAnimationFrame(updateScrollVisuals);
                    ticking = true;
                }
            };

            updateScrollVisuals();
            window.addEventListener('scroll', requestTick, { passive: true });
            window.addEventListener('resize', requestTick, { passive: true });
        })();
    </script>
</body>
</html>
