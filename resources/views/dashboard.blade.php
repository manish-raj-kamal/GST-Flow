<x-app-layout>
    <x-slot name="header">
        <div class="flex min-w-0 items-center justify-between gap-2 sm:gap-3">
            <div class="min-w-0">
                <p class="hidden text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-700 sm:block">Dashboard</p>
                <h2 class="truncate text-lg font-semibold text-slate-900 sm:text-2xl">{{ $businessProfile?->business_name ?? 'GST Flow' }}</h2>
                <p class="mt-0.5 hidden text-sm text-slate-600 md:block">
                    {{ $businessProfile?->gstin ? 'GSTIN: '.$businessProfile->gstin.' · '.$businessProfile->state : 'Track invoices, tax collection, compliance and operating activity.' }}
                </p>
            </div>
            <div class="hidden items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-800 sm:inline-flex">
                {{ auth()->user()->role === 'admin' ? 'Admin access' : 'Business user access' }}
            </div>
        </div>
    </x-slot>

    <div class="gst-shell py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if ($setupIssue)
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-amber-900">MongoDB setup required</h3>
                            <p class="mt-2 text-sm leading-6 text-amber-800">
                                The Laravel app is wired for MongoDB, but the local PHP runtime still needs the native <code>mongodb</code> extension enabled in XAMPP before the platform can read and write data successfully.
                            </p>
                        </div>
                        <div class="rounded-xl bg-white px-4 py-3 text-xs text-amber-900">
                            {{ $setupIssue }}
                        </div>
                    </div>
                </section>
            @endif

            <section id="overview" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @php
                    $cards = [
                        ['label' => 'Total invoices', 'value' => $overview['total_invoices'] ?? 0, 'hint' => 'Lifetime documents'],
                        ['label' => 'Monthly invoices', 'value' => $overview['monthly_invoices'] ?? 0, 'hint' => 'Current month'],
                        ['label' => 'GST collected', 'value' => number_format((float) ($overview['gst_collected'] ?? 0), 2), 'hint' => 'Tax liability generated'],
                        ['label' => 'Revenue', 'value' => number_format((float) ($overview['total_revenue'] ?? 0), 2), 'hint' => 'Total bill value'],
                    ];
                @endphp

                @foreach ($cards as $card)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-4 text-2xl font-semibold text-slate-900 sm:text-3xl">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ $card['hint'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
                <article id="analytics" class="card-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="panel-label">Monthly GST summary</p>
                            <h3 class="panel-title">Compliance overview</h3>
                        </div>
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">{{ $monthlySummary['month'] ?? now()->format('Y-m') }}</span>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach (($monthlySummary['totals'] ?? []) as $metric => $value)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ str_replace('_', ' ', $metric) }}</p>
                                <p class="mt-3 text-2xl font-semibold text-slate-900">{{ number_format((float) $value, 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="card-lg">
                    <p class="panel-label">Active customers</p>
                    <h3 class="panel-title">Customer footprint</h3>
                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-6">
                        <p class="text-3xl font-semibold text-slate-900 sm:text-4xl">{{ $overview['active_customers'] ?? 0 }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Distinct customers with recent invoice activity for the selected business profile.</p>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article id="products" class="card-lg">
                    <div>
                        <p class="panel-label">Top products</p>
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

                <article id="states" class="card-lg">
                    <div>
                        <p class="panel-label">State-wise sales</p>
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
                                    <span class="font-medium text-slate-700">{{ $item['label'] }}</span>
                                    <span class="text-slate-500">{{ number_format((float) $item['value'], 2) }}</span>
                                </div>
                                <div class="bar-track">
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
                <article id="invoices" class="card-lg overflow-hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="panel-label">Recent invoices</p>
                            <h3 class="panel-title">Latest GST transactions</h3>
                        </div>
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">Search and filter ready via API</span>
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
                                        <td class="font-medium text-slate-900">{{ $invoice['invoice_number'] }}</td>
                                        <td>{{ $invoice['invoice_date'] }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $invoice['transaction_type'] }}</span>
                                        </td>
                                        <td>{{ $invoice['customer_name'] ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-active">{{ $invoice['status'] }}</span>
                                        </td>
                                        <td class="text-right font-medium text-slate-900">{{ number_format((float) $invoice['total_amount'], 2) }}</td>
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
                        <p class="panel-label">Audit trail</p>
                        <h3 class="panel-title">Recent system activity</h3>
                    </div>
                    <div class="mt-6 space-y-4">
                        @forelse ($activityLogs as $log)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-900">{{ $log['action_type'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $log['created_at'] }}</p>
                                @if(!empty($log['ip_address']))
                                    <span class="mt-3 inline-flex rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium uppercase tracking-[0.08em] text-slate-500">
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
                    <p class="panel-label">Module coverage</p>
                    <h3 class="panel-title">Implemented platform areas</h3>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        ['title' => 'Business Profiles', 'desc' => 'GSTIN validation, PAN extraction, uniqueness checks, and reusable seller identity.'],
                        ['title' => 'Customers & Catalog', 'desc' => 'Customer GST state detection plus reusable products, HSN codes, and tax slabs.'],
                        ['title' => 'Invoices & Tax Engine', 'desc' => 'Auto numbering, per-item breakdowns, intrastate vs interstate GST logic, and version history.'],
                        ['title' => 'Reports & Exports', 'desc' => 'Dashboard analytics, monthly GST summaries, GSTR-style output, PDF invoices, CSV, and XLS exports.'],
                    ] as $module)
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <h4 class="text-base font-semibold text-slate-900">{{ $module['title'] }}</h4>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $module['desc'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
