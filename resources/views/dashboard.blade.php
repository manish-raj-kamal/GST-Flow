<x-app-layout>
    <x-slot name="header">
        <div class="flex min-w-0 items-center justify-between gap-2 sm:gap-3">
            <div class="min-w-0">
                <p class="module-kicker">Dashboard</p>
                <div class="page-title-row">
                    <h2 class="module-title">{{ $businessProfile?->business_name ?? 'GST Flow' }}</h2>
                    <x-info-tip placement="bottom" text="This dashboard pulls together invoice volume, tax collected, customer activity, product performance, state-wise sales, and audit activity for the selected business profile." />
                </div>
                <p class="module-subtitle hidden md:block">
                    {{ $businessProfile?->gstin ? 'GSTIN: '.$businessProfile->gstin.' | '.$businessProfile->state : 'Track invoices, tax collection, compliance and operating activity.' }}
                </p>
            </div>
            <div class="status-pill">
                {{ auth()->user()->role === 'admin' ? 'Admin access' : 'Business user access' }}
            </div>
        </div>
    </x-slot>

    <div class="gst-shell py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if ($setupIssue)
                <section class="info-banner border-amber-200 bg-amber-50">
                    <div class="info-banner-icon bg-amber-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold text-amber-950">MongoDB setup required</h3>
                        <p class="mt-1 text-amber-800">
                            The Laravel app is wired for MongoDB, but the local PHP runtime still needs the native <code>mongodb</code> extension enabled in XAMPP before the platform can read and write data successfully.
                        </p>
                        <p class="mt-2 rounded-lg bg-white/80 px-3 py-2 text-xs font-semibold text-amber-900">{{ $setupIssue }}</p>
                    </div>
                </section>
            @endif

            <section class="dashboard-hero">
                <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr] lg:items-end">
                    <div>
                        <p class="module-kicker">Compliance workspace</p>
                        <h1 class="mt-2 max-w-3xl text-3xl font-bold leading-tight text-slate-950 sm:text-4xl">
                            Know what changed, what is due, and where your GST work needs attention.
                        </h1>
                        <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                            Start with master data, create clean invoices, and use the report modules to review tax liability before filing.
                        </p>
                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('business-profiles') }}" class="btn btn-secondary">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V5a2 2 0 012-2h7l5 5v13M9 8h4M9 12h6M9 16h6"/></svg>
                                Business Profile
                            </a>
                            <a href="{{ route('invoices.create') }}{{ $businessProfile ? '?business_profile_id='.$businessProfile->id : '' }}" class="btn btn-primary">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                New Invoice
                            </a>
                            <a href="{{ route('reports') }}" class="btn btn-secondary">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3v18m7-14v14M5 11v10"/></svg>
                                Reports
                            </a>
                        </div>
                    </div>

                    <div class="grid gap-4 border-t border-slate-200 pt-5 sm:grid-cols-3 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                        <div>
                            <p class="panel-label">This Month</p>
                            <p class="mt-2 text-2xl font-bold text-slate-950">{{ $overview['monthly_invoices'] ?? 0 }}</p>
                            <p class="mt-1 text-xs text-slate-500">Invoices created</p>
                        </div>
                        <div>
                            <p class="panel-label">GST</p>
                            <p class="value-accent mt-2 text-2xl font-bold">₹ {{ number_format((float) ($overview['gst_collected'] ?? 0), 2) }}</p>
                            <p class="mt-1 text-xs text-slate-500">Collected so far</p>
                        </div>
                        <div>
                            <p class="panel-label">Customers</p>
                            <p class="value-primary mt-2 text-2xl font-bold">{{ $overview['active_customers'] ?? 0 }}</p>
                            <p class="mt-1 text-xs text-slate-500">With activity</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="workflow-steps">
                @foreach ([
                    ['title' => 'Profile', 'desc' => 'Confirm seller GSTIN and state before billing.', 'tip' => 'Business profiles hold seller GSTIN, PAN, address, and state details reused across invoices.'],
                    ['title' => 'Customers', 'desc' => 'Add GSTIN and state for accurate supply type.', 'tip' => 'Customer state helps identify intrastate vs interstate supply for CGST/SGST or IGST.'],
                    ['title' => 'Products', 'desc' => 'Map HSN, price, unit, and GST rate.', 'tip' => 'Products speed up invoice entry and reduce rate or HSN mistakes.'],
                    ['title' => 'Invoices', 'desc' => 'Create documents and let tax totals calculate.', 'tip' => 'Invoices combine seller, customer, product, tax, and status data into one auditable transaction.'],
                    ['title' => 'Reports', 'desc' => 'Review liability and export summaries.', 'tip' => 'Reports and GSTR summaries help review monthly totals before compliance filing.'],
                ] as $index => $step)
                    <article class="workflow-step">
                        <div class="flex items-start justify-between gap-3">
                            <div class="workflow-step-number">{{ $index + 1 }}</div>
                            <x-info-tip text="{{ $step['tip'] }}" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-950">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $step['desc'] }}</p>
                    </article>
                @endforeach
            </section>

            <section id="overview" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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
                        <p class="mt-3 text-2xl font-bold text-slate-950 sm:text-3xl">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs font-semibold text-slate-500">{{ $card['hint'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
                <article id="analytics" class="card-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="panel-label">Monthly GST summary</p>
                                <x-info-tip text="Month-level tax totals broken into taxable value, CGST, SGST, IGST, total tax, and total invoice value." />
                            </div>
                            <h3 class="panel-title">Compliance overview</h3>
                        </div>
                        <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600">{{ $monthlySummary['month'] ?? now()->format('Y-m') }}</span>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @forelse (($monthlySummary['totals'] ?? []) as $metric => $value)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">{{ str_replace('_', ' ', $metric) }}</p>
                                <p class="mt-3 text-2xl font-bold text-slate-950">₹ {{ number_format((float) $value, 2) }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No monthly GST summary is available yet.</p>
                        @endforelse
                    </div>
                </article>

                <article class="card-lg">
                    <div class="flex items-center gap-2">
                        <p class="panel-label">Active customers</p>
                        <x-info-tip text="Customers counted here have recent invoice activity for this profile." />
                    </div>
                    <h3 class="panel-title">Customer footprint</h3>
                    <div class="callout-accent mt-6 p-5">
                        <p class="text-3xl font-bold text-slate-950 sm:text-4xl">{{ $overview['active_customers'] ?? 0 }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Distinct customers with recent invoice activity for the selected business profile.</p>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article id="products" class="card-lg">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="panel-label">Top products</p>
                            <x-info-tip text="Products are ranked by invoice value so you can spot which catalog items drive revenue." />
                        </div>
                        <h3 class="panel-title">Revenue-leading items</h3>
                    </div>
                    <div class="mt-6 space-y-4">
                        @forelse ($topProducts as $item)
                            @php
                                $maxValue = collect($topProducts)->max('value') ?: 1;
                                $width = min(100, (($item['value'] ?? 0) / $maxValue) * 100);
                            @endphp
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                    <span class="font-bold text-slate-700">{{ $item['label'] }}</span>
                                    <span class="text-slate-500">₹ {{ number_format((float) $item['value'], 2) }}</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: {{ $width }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No product analytics yet. Create invoices to populate this view.</p>
                        @endforelse
                    </div>
                </article>

                <article id="states" class="card-lg">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="panel-label">State-wise sales</p>
                            <x-info-tip text="Place-of-supply totals show where billed value is distributed across states." />
                        </div>
                        <h3 class="panel-title">Place of supply spread</h3>
                    </div>
                    <div class="mt-6 space-y-4">
                        @forelse ($stateWiseSales as $item)
                            @php
                                $maxValue = collect($stateWiseSales)->max('value') ?: 1;
                                $width = min(100, (($item['value'] ?? 0) / $maxValue) * 100);
                            @endphp
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                    <span class="font-bold text-slate-700">{{ $item['label'] }}</span>
                                    <span class="text-slate-500">₹ {{ number_format((float) $item['value'], 2) }}</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: {{ $width }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No interstate or intrastate sales data is available yet.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <article id="invoices" class="card-lg">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="panel-label">Recent invoices</p>
                                <x-info-tip text="Latest invoices help you review recent transaction status and totals without opening the invoice module." />
                            </div>
                            <h3 class="panel-title">Latest GST transactions</h3>
                        </div>
                        <span class="hidden rounded-lg border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600 sm:inline-flex">API ready</span>
                    </div>
                    <div class="mt-6 overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentInvoices as $invoice)
                                    <tr>
                                        <td class="font-medium text-slate-950">{{ $invoice['invoice_number'] }}</td>
                                        <td>{{ $invoice['invoice_date'] }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $invoice['transaction_type'] }}</span>
                                        </td>
                                        <td>{{ $invoice['customer_name'] ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-active">{{ $invoice['status'] }}</span>
                                        </td>
                                        <td class="text-right font-bold text-slate-950">₹ {{ number_format((float) $invoice['total_amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-10 text-center text-sm text-slate-500">No invoices available yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article id="audit" class="card-lg">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="panel-label">Audit trail</p>
                            <x-info-tip text="Recent activity logs are useful for tracing who changed records and when." />
                        </div>
                        <h3 class="panel-title">Recent system activity</h3>
                    </div>
                    <div class="mt-6 space-y-4">
                        @forelse ($activityLogs as $log)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-bold text-slate-950">{{ $log['action_type'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $log['created_at'] }}</p>
                                @if(!empty($log['ip_address']))
                                    <span class="mt-3 inline-flex rounded-md border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.08em] text-slate-500">
                                        {{ $log['ip_address'] }}
                                    </span>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Activity logs will appear here as the system is used.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section id="modules" class="card-lg">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="panel-label">Module coverage</p>
                        <x-info-tip text="These are the main implemented areas of the platform and the purpose each one serves in the GST workflow." />
                    </div>
                    <h3 class="panel-title">Implemented platform areas</h3>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        ['title' => 'Business Profiles', 'desc' => 'GSTIN validation, PAN extraction, uniqueness checks, and reusable seller identity.'],
                        ['title' => 'Customers & Catalog', 'desc' => 'Customer GST state detection plus reusable products, HSN codes, and tax slabs.'],
                        ['title' => 'Invoices & Tax Engine', 'desc' => 'Auto numbering, per-item breakdowns, intrastate vs interstate GST logic, and version history.'],
                        ['title' => 'Reports & Exports', 'desc' => 'Dashboard analytics, monthly GST summaries, GSTR-style output, PDF invoices, CSV, and XLS exports.'],
                    ] as $module)
                        <article class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <h4 class="text-base font-bold text-slate-950">{{ $module['title'] }}</h4>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $module['desc'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
