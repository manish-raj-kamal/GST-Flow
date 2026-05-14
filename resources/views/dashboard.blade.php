<x-app-layout>
    <x-slot name="header">
        <div class="flex min-w-0 items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="module-kicker">Dashboard</p>
                <div class="page-title-row">
                    <h2 class="module-title">{{ $businessProfile?->business_name ?? 'GST Flow' }}</h2>
                    <x-info-tip placement="bottom" text="A focused view of GST activity, invoices, tax totals, and recent system movement." />
                </div>
                <p class="module-subtitle hidden md:block">
                    {{ $businessProfile?->gstin ? 'GSTIN: '.$businessProfile->gstin.' | '.$businessProfile->state : 'Set up a profile to start tracking GST operations.' }}
                </p>
            </div>
            <div class="status-pill">
                {{ auth()->user()->role === 'admin' ? 'Admin access' : 'Business user access' }}
            </div>
        </div>
    </x-slot>

    <div class="gst-shell py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($setupIssue)
                <section class="info-banner border-amber-200 bg-amber-50">
                    <div class="info-banner-icon">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-black text-[#332F3A]">MongoDB setup required</h3>
                        <p class="mt-1 text-[#635F69]">
                            The local PHP runtime needs the native <code>mongodb</code> extension enabled in XAMPP before live data can be read or written.
                        </p>
                        <p class="mt-2 rounded-[20px] bg-white/80 px-3 py-2 text-xs font-bold text-[#332F3A]">{{ $setupIssue }}</p>
                    </div>
                </section>
            @endif

            <section class="dashboard-hero">
                <div class="grid gap-6 lg:grid-cols-[1.25fr_0.75fr] lg:items-center">
                    <div>
                        <p class="module-kicker">Compliance command center</p>
                        <h1 class="mt-3 max-w-3xl text-3xl font-black leading-tight text-[#332F3A] sm:text-4xl lg:text-5xl">
                            Review GST health without digging through every module.
                        </h1>
                        <p class="mt-4 max-w-2xl text-base font-medium leading-7 text-[#635F69]">
                            Keep the first screen lean: invoice volume, tax liability, customer reach, and the actions that move work forward.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ route('invoices.create') }}{{ $businessProfile ? '?business_profile_id='.$businessProfile->id : '' }}" class="btn btn-primary">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                New Invoice
                            </a>
                            <a href="{{ route('reports') }}" class="btn btn-secondary">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3v18m7-14v14M5 11v10"/></svg>
                                Reports
                            </a>
                            <a href="{{ route('gstin-validator') }}" class="btn btn-secondary">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Validate GSTIN
                            </a>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                        <div class="hero-stat">
                            <p class="panel-label">This Month</p>
                            <p class="mt-2 text-3xl font-black text-[#332F3A]">{{ $overview['monthly_invoices'] ?? 0 }}</p>
                            <p class="mt-1 text-sm font-medium text-[#635F69]">Invoices created</p>
                        </div>
                        <div class="hero-stat">
                            <p class="panel-label">GST Collected</p>
                            <p class="mt-2 text-3xl font-black text-[#7C3AED]">₹ {{ number_format((float) ($overview['gst_collected'] ?? 0), 2) }}</p>
                            <p class="mt-1 text-sm font-medium text-[#635F69]">Calculated tax</p>
                        </div>
                        <div class="hero-stat">
                            <p class="panel-label">Active Customers</p>
                            <p class="mt-2 text-3xl font-black text-[#10B981]">{{ $overview['active_customers'] ?? 0 }}</p>
                            <p class="mt-1 text-sm font-medium text-[#635F69]">With activity</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @php
                    $cards = [
                        ['label' => 'Total invoices', 'value' => $overview['total_invoices'] ?? 0, 'hint' => 'Lifetime documents', 'info' => 'Count of all invoices recorded for the selected profile.', 'iconClass' => 'metric-icon-primary'],
                        ['label' => 'Monthly invoices', 'value' => $overview['monthly_invoices'] ?? 0, 'hint' => 'Current month', 'info' => 'Invoices dated in the current month.', 'iconClass' => 'metric-icon-accent'],
                        ['label' => 'GST collected', 'value' => '₹ '.number_format((float) ($overview['gst_collected'] ?? 0), 2), 'hint' => 'Tax liability generated', 'info' => 'Total GST amount calculated across invoices.', 'iconClass' => 'metric-icon-sky'],
                        ['label' => 'Revenue', 'value' => '₹ '.number_format((float) ($overview['total_revenue'] ?? 0), 2), 'hint' => 'Total bill value', 'info' => 'Gross invoice value including taxable value and tax.', 'iconClass' => 'metric-icon-rose'],
                    ];
                @endphp

                @foreach ($cards as $card)
                    <article class="metric-card">
                        <div class="flex items-start justify-between gap-4">
                            <div class="metric-icon {{ $card['iconClass'] }}">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 16V9m4 7V7m4 9v-4"/>
                                </svg>
                            </div>
                            <x-info-tip text="{{ $card['info'] }}" />
                        </div>
                        <p class="panel-label">{{ $card['label'] }}</p>
                        <p class="mt-3 text-2xl font-black text-[#332F3A] sm:text-3xl">{{ $card['value'] }}</p>
                        <p class="mt-2 text-sm font-medium text-[#635F69]">{{ $card['hint'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <article class="card-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="panel-label">Monthly GST summary</p>
                                <x-info-tip text="Month-level tax totals broken into taxable value, CGST, SGST, IGST, total tax, and total invoice value." />
                            </div>
                            <h3 class="panel-title">Tax movement</h3>
                        </div>
                        <span class="badge badge-info">{{ $monthlySummary['month'] ?? now()->format('Y-m') }}</span>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @forelse (($monthlySummary['totals'] ?? []) as $metric => $value)
                            <div class="rounded-[24px] bg-[#EFEBF5] p-4 shadow-[var(--shadow-pressed)]">
                                <p class="panel-label">{{ str_replace('_', ' ', $metric) }}</p>
                                <p class="mt-3 text-2xl font-black text-[#332F3A]">₹ {{ number_format((float) $value, 2) }}</p>
                            </div>
                        @empty
                            <p class="text-sm font-medium text-[#635F69]">No monthly GST summary is available yet.</p>
                        @endforelse
                    </div>
                </article>

                <article class="card-lg">
                    <div class="flex items-center gap-2">
                        <p class="panel-label">Top products</p>
                        <x-info-tip text="Products are ranked by invoice value so you can spot which catalog items drive revenue." />
                    </div>
                    <h3 class="panel-title">Revenue-leading items</h3>
                    <div class="mt-6 space-y-5">
                        @forelse ($topProducts as $item)
                            @php
                                $maxValue = collect($topProducts)->max('value') ?: 1;
                                $width = min(100, (($item['value'] ?? 0) / $maxValue) * 100);
                            @endphp
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                    <span class="font-bold text-[#332F3A]">{{ $item['label'] }}</span>
                                    <span class="font-medium text-[#635F69]">₹ {{ number_format((float) $item['value'], 2) }}</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: {{ $width }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm font-medium text-[#635F69]">Create invoices to populate product analytics.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <article class="card-lg overflow-hidden">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="panel-label">Recent invoices</p>
                                <x-info-tip text="Latest invoices help you review recent transaction status and totals without opening the invoice module." />
                            </div>
                            <h3 class="panel-title">Latest GST transactions</h3>
                        </div>
                        <a href="{{ route('invoices') }}" class="btn btn-secondary btn-sm">View all</a>
                    </div>
                    <div class="mt-6 overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentInvoices as $invoice)
                                    <tr>
                                        <td class="font-bold text-[#332F3A]">{{ $invoice['invoice_number'] }}</td>
                                        <td>{{ $invoice['invoice_date'] }}</td>
                                        <td>{{ $invoice['customer_name'] ?? 'N/A' }}</td>
                                        <td><span class="badge badge-active">{{ $invoice['status'] }}</span></td>
                                        <td class="text-right font-black text-[#332F3A]">₹ {{ number_format((float) $invoice['total_amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-10 text-center text-sm font-medium text-[#635F69]">No invoices available yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="card-lg">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="panel-label">Audit trail</p>
                            <x-info-tip text="Recent activity logs are useful for tracing who changed records and when." />
                        </div>
                        <h3 class="panel-title">Recent system activity</h3>
                    </div>
                    <div class="mt-6 space-y-4">
                        @forelse ($activityLogs as $log)
                            <div class="rounded-[24px] bg-[#EFEBF5] p-4 shadow-[var(--shadow-pressed)]">
                                <p class="text-sm font-black text-[#332F3A]">{{ $log['action_type'] }}</p>
                                <p class="mt-1 text-xs font-medium text-[#635F69]">{{ $log['created_at'] }}</p>
                                @if(!empty($log['ip_address']))
                                    <span class="badge badge-info mt-3">{{ $log['ip_address'] }}</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm font-medium text-[#635F69]">Activity logs will appear here as the system is used.</p>
                        @endforelse
                    </div>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
