<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">GST Control Center</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">
                    {{ $businessProfile?->business_name ?? 'GST Platform Dashboard' }}
                </h2>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $businessProfile?->gstin ? 'GSTIN: '.$businessProfile->gstin.' | '.$businessProfile->state : 'Connected metrics, invoice operations, reports, and compliance outputs.' }}
                </p>
            </div>
            <div class="rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800">
                {{ auth()->user()->role === 'admin' ? 'Admin access' : 'Business user access' }}
            </div>
        </div>
    </x-slot>

    <div class="gst-shell py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if ($setupIssue)
                <section class="rounded-3xl border border-amber-200 bg-amber-50/90 p-6 shadow-sm">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-amber-900">MongoDB setup attention needed</h3>
                            <p class="mt-2 text-sm text-amber-800">
                                The Laravel app is wired for MongoDB, but the local PHP runtime still needs the native <code>mongodb</code> extension enabled in XAMPP before the platform can read and write data successfully.
                            </p>
                        </div>
                        <div class="rounded-2xl bg-white px-4 py-3 text-xs text-amber-900 shadow-sm">
                            {{ $setupIssue }}
                        </div>
                    </div>
                </section>
            @endif

            <section id="overview" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @php
                    $cards = [
                        ['label' => 'Total Invoices', 'value' => $overview['total_invoices'] ?? 0, 'tone' => 'from-sky-500 to-cyan-400'],
                        ['label' => 'Monthly Invoices', 'value' => $overview['monthly_invoices'] ?? 0, 'tone' => 'from-amber-500 to-orange-400'],
                        ['label' => 'GST Collected', 'value' => number_format((float) ($overview['gst_collected'] ?? 0), 2), 'tone' => 'from-emerald-500 to-lime-400'],
                        ['label' => 'Revenue', 'value' => number_format((float) ($overview['total_revenue'] ?? 0), 2), 'tone' => 'from-fuchsia-500 to-pink-400'],
                    ];
                @endphp

                @foreach ($cards as $card)
                    <article class="metric-card">
                        <div class="inline-flex rounded-full bg-white/75 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">
                            {{ $card['label'] }}
                        </div>
                        <div class="mt-5 flex items-end justify-between gap-4">
                            <div class="text-3xl font-semibold text-slate-950">{{ $card['value'] }}</div>
                            <div class="h-12 w-12 rounded-2xl bg-gradient-to-br {{ $card['tone'] }} shadow-lg shadow-slate-300/30"></div>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
                <article id="analytics" class="panel-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="panel-label">Monthly GST Summary</p>
                            <h3 class="panel-title">Compliance at a glance</h3>
                        </div>
                        <div class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">
                            {{ $monthlySummary['month'] ?? now()->format('Y-m') }}
                        </div>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach (($monthlySummary['totals'] ?? []) as $metric => $value)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ str_replace('_', ' ', $metric) }}</div>
                                <div class="mt-3 text-2xl font-semibold text-slate-900">{{ number_format((float) $value, 2) }}</div>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="panel-card">
                    <p class="panel-label">Active Customers</p>
                    <h3 class="panel-title">Customer footprint</h3>
                    <div class="mt-8 rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-800 to-slate-700 p-6 text-white">
                        <div class="text-5xl font-semibold">{{ $overview['active_customers'] ?? 0 }}</div>
                        <p class="mt-3 max-w-xs text-sm text-slate-300">
                            Distinct customers with recent outward supply activity for the selected business profile.
                        </p>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article id="products" class="panel-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="panel-label">Top Products</p>
                            <h3 class="panel-title">Revenue-leading catalog items</h3>
                        </div>
                    </div>
                    <div class="mt-6 space-y-4">
                        @forelse ($topProducts as $item)
                            @php
                                $maxValue = collect($topProducts)->max('value') ?: 1;
                                $width = min(100, (($item['value'] ?? 0) / $maxValue) * 100);
                            @endphp
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                    <span class="font-medium text-slate-700">{{ $item['label'] }}</span>
                                    <span class="text-slate-500">{{ number_format((float) $item['value'], 2) }}</span>
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

                <article id="states" class="panel-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="panel-label">State-wise Sales</p>
                            <h3 class="panel-title">Place of supply spread</h3>
                        </div>
                    </div>
                    <div class="mt-6 space-y-4">
                        @forelse ($stateWiseSales as $item)
                            @php
                                $maxValue = collect($stateWiseSales)->max('value') ?: 1;
                                $width = min(100, (($item['value'] ?? 0) / $maxValue) * 100);
                            @endphp
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                    <span class="font-medium text-slate-700">{{ $item['label'] }}</span>
                                    <span class="text-slate-500">{{ number_format((float) $item['value'], 2) }}</span>
                                </div>
                                <div class="bar-track bg-amber-100/70">
                                    <div class="bar-fill bg-gradient-to-r from-amber-500 to-orange-400" style="width: {{ $width }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No interstate or intrastate sales data is available yet.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <article id="invoices" class="panel-card overflow-hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="panel-label">Recent Invoices</p>
                            <h3 class="panel-title">Latest GST transactions</h3>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Search and filter ready via API</span>
                    </div>
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="border-b border-slate-200 text-xs uppercase tracking-[0.18em] text-slate-500">
                                <tr>
                                    <th class="pb-3 pr-4">Invoice</th>
                                    <th class="pb-3 pr-4">Date</th>
                                    <th class="pb-3 pr-4">Type</th>
                                    <th class="pb-3 pr-4">Customer</th>
                                    <th class="pb-3 pr-4">Status</th>
                                    <th class="pb-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentInvoices as $invoice)
                                    <tr class="border-b border-slate-100">
                                        <td class="py-4 pr-4 font-medium text-slate-900">{{ $invoice['invoice_number'] }}</td>
                                        <td class="py-4 pr-4 text-slate-600">{{ $invoice['invoice_date'] }}</td>
                                        <td class="py-4 pr-4">
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-600">
                                                {{ $invoice['transaction_type'] }}
                                            </span>
                                        </td>
                                        <td class="py-4 pr-4 text-slate-700">{{ $invoice['customer_name'] ?? 'N/A' }}</td>
                                        <td class="py-4 pr-4">
                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase text-emerald-700">
                                                {{ $invoice['status'] }}
                                            </span>
                                        </td>
                                        <td class="py-4 text-right font-medium text-slate-900">{{ number_format((float) $invoice['total_amount'], 2) }}</td>
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

                <article id="audit" class="panel-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="panel-label">Audit Trail</p>
                            <h3 class="panel-title">Recent system activity</h3>
                        </div>
                    </div>
                    <div class="mt-6 space-y-4">
                        @forelse ($activityLogs as $log)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="text-sm font-semibold text-slate-900">{{ $log['action_type'] }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $log['created_at'] }}</div>
                                @if(!empty($log['ip_address']))
                                    <div class="mt-3 inline-flex rounded-full bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                        {{ $log['ip_address'] }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Activity logs will appear here as the system is used.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section id="modules" class="panel-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="panel-label">Module Coverage</p>
                        <h3 class="panel-title">Implemented platform areas</h3>
                    </div>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        ['title' => 'Business Profiles', 'desc' => 'GSTIN validation, PAN extraction, uniqueness checks, and reusable seller identity.'],
                        ['title' => 'Customers & Catalog', 'desc' => 'Customer GST state detection plus reusable products, HSN codes, and tax slabs.'],
                        ['title' => 'Invoices & Tax Engine', 'desc' => 'Auto numbering, per-item breakdowns, intrastate vs interstate GST logic, and version history.'],
                        ['title' => 'Reports & Exports', 'desc' => 'Dashboard analytics, monthly GST summaries, GSTR-style output, PDF invoices, CSV, and XLS exports.'],
                    ] as $module)
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <h4 class="text-base font-semibold text-slate-900">{{ $module['title'] }}</h4>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $module['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
