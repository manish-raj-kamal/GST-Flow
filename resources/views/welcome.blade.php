<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — GST Compliance & Transaction Management</title>
    <meta name="description" content="Streamline your GST compliance with automated tax calculation, invoice generation, GSTIN validation, and real-time analytics.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #f59e0b;
            --primary-dark: #d97706;
            --bg: #0c0e14;
            --bg-card: rgba(255,255,255,0.04);
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255,255,255,0.08);
            --glow: rgba(245, 158, 11, 0.15);
        }
        html { font-family: 'Inter', system-ui, sans-serif; scroll-behavior: smooth; }
        body { background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; overflow-x: hidden; }

        /* Hero BG */
        .hero-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0;
            background:
                radial-gradient(ellipse 80% 60% at 50% -10%, rgba(245,158,11,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 80% 50%, rgba(99,102,241,0.08) 0%, transparent 50%),
                radial-gradient(ellipse 50% 40% at 20% 80%, rgba(16,185,129,0.06) 0%, transparent 50%);
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; position: relative; z-index: 1; }

        /* Nav */
        nav { padding: 20px 0; display: flex; align-items: center; justify-content: space-between; }
        .logo { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text); }
        .logo-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; color: #fff; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); box-shadow: 0 4px 16px rgba(245,158,11,0.3); }
        .logo span { font-weight: 700; font-size: 18px; letter-spacing: -0.02em; }
        .nav-links { display: flex; align-items: center; gap: 8px; }
        .nav-links a { padding: 8px 20px; border-radius: 10px; font-size: 14px; font-weight: 500; text-decoration: none; transition: all 150ms; }
        .nav-link-ghost { color: var(--text-muted); }
        .nav-link-ghost:hover { color: var(--text); background: var(--bg-card); }
        .nav-link-primary { color: #1c1917; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); box-shadow: 0 2px 12px rgba(245,158,11,0.3); }
        .nav-link-primary:hover { filter: brightness(1.1); box-shadow: 0 4px 20px rgba(245,158,11,0.4); }

        /* Hero */
        .hero { padding: 80px 0 60px; text-align: center; }
        .hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px; border-radius: 999px; font-size: 12px; font-weight: 600; color: var(--primary); background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); margin-bottom: 24px; text-transform: uppercase; letter-spacing: 0.1em; }
        .hero h1 { font-size: clamp(36px, 6vw, 72px); font-weight: 800; line-height: 1.05; letter-spacing: -0.03em; max-width: 800px; margin: 0 auto; }
        .hero h1 .highlight { background: linear-gradient(135deg, var(--primary), #fb923c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero p { margin-top: 24px; font-size: 18px; line-height: 1.7; color: var(--text-muted); max-width: 600px; margin-left: auto; margin-right: auto; }
        .hero-actions { margin-top: 40px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .hero-actions a { padding: 14px 32px; border-radius: 12px; font-size: 15px; font-weight: 600; text-decoration: none; transition: all 200ms; }
        .btn-hero-primary { color: #1c1917; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); box-shadow: 0 4px 24px rgba(245,158,11,0.3); }
        .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(245,158,11,0.4); }
        .btn-hero-secondary { color: var(--text-muted); border: 1px solid var(--border); background: var(--bg-card); }
        .btn-hero-secondary:hover { color: var(--text); border-color: rgba(255,255,255,0.2); }

        /* Features */
        .features { padding: 80px 0; }
        .section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.2em; color: var(--primary); margin-bottom: 12px; }
        .section-title { font-size: 32px; font-weight: 700; letter-spacing: -0.02em; max-width: 500px; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 48px; }
        .feature-card { padding: 28px; border-radius: 16px; background: var(--bg-card); border: 1px solid var(--border); transition: all 250ms; }
        .feature-card:hover { border-color: rgba(245,158,11,0.2); background: rgba(245,158,11,0.03); transform: translateY(-4px); }
        .feature-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; font-size: 20px; }
        .feature-card h3 { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
        .feature-card p { font-size: 14px; line-height: 1.6; color: var(--text-muted); }

        /* Stats */
        .stats { padding: 60px 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .stat-card { text-align: center; padding: 32px 20px; border-radius: 16px; background: var(--bg-card); border: 1px solid var(--border); }
        .stat-value { font-size: 36px; font-weight: 800; background: linear-gradient(135deg, var(--primary), #fb923c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { margin-top: 8px; font-size: 14px; color: var(--text-muted); }

        /* CTA */
        .cta { padding: 80px 0; text-align: center; }
        .cta-box { padding: 60px 40px; border-radius: 24px; background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(99,102,241,0.05)); border: 1px solid rgba(245,158,11,0.15); }
        .cta-box h2 { font-size: 32px; font-weight: 700; }
        .cta-box p { margin-top: 12px; color: var(--text-muted); font-size: 16px; }

        /* Footer */
        footer { padding: 40px 0; border-top: 1px solid var(--border); text-align: center; color: var(--text-muted); font-size: 13px; }

        @media (max-width: 640px) {
            .hero { padding: 48px 0 32px; }
            .nav-links a span { display: none; }
        }
    </style>
</head>
<body>
    <div class="hero-bg"></div>

    <div class="container">
        <nav>
            <a href="/" class="logo">
                <div class="logo-icon">GST</div>
                <span>{{ config('app.name') }}</span>
            </a>
            <div class="nav-links">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="nav-link-primary">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="nav-link-ghost"><span>Log in</span></a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="nav-link-primary">Get Started</a>
                        @endif
                    @endauth
                @endif
            </div>
        </nav>
    </div>

    <section class="hero">
        <div class="container">
            <div class="hero-badge">🇮🇳 GST Compliance Platform</div>
            <h1>Automate your <span class="highlight">GST compliance</span> end to end</h1>
            <p>Create GST-compliant invoices, auto-calculate taxes, validate GSTIN numbers, generate GSTR-ready reports, and monitor analytics — all from one dashboard.</p>
            <div class="hero-actions">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn-hero-primary">Open Dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="btn-hero-primary">Start Free</a>
                    <a href="{{ route('login') }}" class="btn-hero-secondary">Sign In</a>
                @endauth
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <div class="section-label">Capabilities</div>
            <div class="section-title">Everything you need for GST management</div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">🧾</div>
                    <h3>Invoice Generation</h3>
                    <p>Create GST-compliant invoices with automatic tax calculation, HSN mapping, and dynamic line items.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">🧮</div>
                    <h3>Tax Calculation Engine</h3>
                    <p>Automatic CGST/SGST/IGST computation based on interstate or intrastate supply detection.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;">🔍</div>
                    <h3>GSTIN Validation</h3>
                    <p>Regex + checksum validation, state code extraction, PAN detection, and format verification.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(236,72,153,0.1); color: #ec4899;">📊</div>
                    <h3>Dashboard Analytics</h3>
                    <p>Real-time metrics: revenue, GST collected, monthly trends, top products, and state-wise sales.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(14,165,233,0.1); color: #0ea5e9;">📑</div>
                    <h3>GSTR-Ready Reports</h3>
                    <p>Generate GSTR-1 and GSTR-3B style summaries with outward supplies, HSN-wise, and state-wise breakdowns.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(168,85,247,0.1); color: #a855f7;">📤</div>
                    <h3>Export & Download</h3>
                    <p>PDF invoices, CSV sales reports, Excel summaries — compliance-ready exports in one click.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(239,68,68,0.1); color: #ef4444;">🔐</div>
                    <h3>Role-Based Access</h3>
                    <p>Admin and business user roles with middleware-protected routes and permission controls.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: rgba(34,197,94,0.1); color: #22c55e;">📱</div>
                    <h3>Responsive Design</h3>
                    <p>Fully responsive interface with sidebar navigation, collapsible menus, and mobile-optimized views.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-value">20+</div><div class="stat-label">System Modules</div></div>
                <div class="stat-card"><div class="stat-value">30+</div><div class="stat-label">API Endpoints</div></div>
                <div class="stat-card"><div class="stat-value">36</div><div class="stat-label">State Codes</div></div>
                <div class="stat-card"><div class="stat-value">5</div><div class="stat-label">GST Slabs</div></div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <div class="cta-box">
                <h2>Ready to streamline your GST workflow?</h2>
                <p>Sign up now and start managing your GST compliance from a single powerful platform.</p>
                <div class="hero-actions" style="margin-top: 28px;">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn-hero-primary">Go to Dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="btn-hero-primary">Create Free Account</a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>© {{ date('Y') }} {{ config('app.name') }}. Built with Laravel, MongoDB, Tailwind CSS, and Alpine.js.</p>
        </div>
    </footer>
</body>
</html>
