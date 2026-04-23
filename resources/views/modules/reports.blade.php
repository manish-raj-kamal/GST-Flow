<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Analytics</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Reports</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="reportsPage()">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="tab-bar">
                <button class="tab-item" :class="tab === 'sales' && 'active'" @click="tab = 'sales'; loadReport()">Sales Report</button>
                <button class="tab-item" :class="tab === 'purchases' && 'active'" @click="tab = 'purchases'; loadReport()">Purchase Report</button>
                <button class="tab-item" :class="tab === 'monthly' && 'active'" @click="tab = 'monthly'; loadMonthly()">Monthly Summary</button>
            </div>
            <div class="flex items-center gap-2">
                @if($profiles->count() > 1)
                <select class="form-select text-sm max-w-xs" @change="window.location = '/reports?business_profile_id=' + $event.target.value">
                    @foreach($profiles as $p)<option value="{{ $p->id }}" {{ $activeProfile && $activeProfile->id === $p->id ? 'selected' : '' }}>{{ $p->business_name }}</option>@endforeach
                </select>
                @endif
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-6" x-show="tab !== 'monthly'">
            <div class="flex flex-wrap gap-3 items-end">
                <div class="form-group"><label class="form-label">From</label><input type="date" x-model="filters.from_date" class="form-input text-sm"></div>
                <div class="form-group"><label class="form-label">To</label><input type="date" x-model="filters.to_date" class="form-input text-sm"></div>
                <div class="form-group"><label class="form-label">Status</label>
                    <select x-model="filters.status" class="form-select text-sm"><option value="">All</option><option value="draft">Draft</option><option value="issued">Issued</option><option value="paid">Paid</option></select>
                </div>
                <button @click="loadReport()" class="btn btn-primary btn-sm">Apply Filters</button>
                <a :href="downloadUrl" target="_blank" class="btn btn-secondary btn-sm" x-show="reportData">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export CSV
                </a>
            </div>
        </div>

        <div class="card mb-6" x-show="tab === 'monthly'">
            <div class="flex items-end gap-3">
                <div class="form-group"><label class="form-label">Month</label><input type="month" x-model="monthFilter" class="form-input text-sm"></div>
                <button @click="loadMonthly()" class="btn btn-primary btn-sm">Load Summary</button>
                <a :href="'/api/export/tax-summary.csv?business_profile_id={{ $activeProfile?->id }}'" target="_blank" class="btn btn-secondary btn-sm" x-show="monthlySummary">Export CSV</a>
                <a :href="'/api/export/monthly-summary.xls?business_profile_id={{ $activeProfile?->id }}'" target="_blank" class="btn btn-secondary btn-sm" x-show="monthlySummary">Export XLS</a>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="text-center py-12 text-slate-400"><div class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-amber-500 border-t-transparent"></div><p class="mt-2 text-sm">Loading report...</p></div>

        {{-- Sales/Purchase Report --}}
        <template x-if="tab !== 'monthly' && reportData && !loading">
            <div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                    <div class="metric-card"><div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Invoices</div><div class="mt-2 text-2xl font-bold" x-text="reportData.totals?.invoice_count || 0"></div></div>
                    <div class="metric-card"><div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Taxable Value</div><div class="mt-2 text-2xl font-bold" x-text="'₹ ' + gst.formatNumber(reportData.totals?.taxable_value)"></div></div>
                    <div class="metric-card"><div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Total Tax</div><div class="mt-2 text-2xl font-bold text-emerald-600" x-text="'₹ ' + gst.formatNumber(reportData.totals?.total_tax)"></div></div>
                    <div class="metric-card"><div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Grand Total</div><div class="mt-2 text-2xl font-bold text-amber-600" x-text="'₹ ' + gst.formatNumber(reportData.totals?.total_amount)"></div></div>
                </div>
                <div class="card-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead><tr><th>Invoice</th><th>Date</th><th>Taxable</th><th>CGST</th><th>SGST</th><th>IGST</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                                <template x-for="inv in (reportData.invoices || [])" :key="inv.id || inv._id || inv.invoice_number">
                                    <tr>
                                        <td class="font-mono font-medium" x-text="inv.invoice_number"></td>
                                        <td class="text-sm" x-text="gst.formatDate(inv.invoice_date)"></td>
                                        <td x-text="'₹ ' + gst.formatNumber(inv.taxable_value)"></td>
                                        <td x-text="'₹ ' + gst.formatNumber(inv.cgst)"></td>
                                        <td x-text="'₹ ' + gst.formatNumber(inv.sgst)"></td>
                                        <td x-text="'₹ ' + gst.formatNumber(inv.igst)"></td>
                                        <td class="font-medium" x-text="'₹ ' + gst.formatNumber(inv.total_amount)"></td>
                                        <td><span class="badge badge-active uppercase" x-text="inv.status"></span></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>

        {{-- Monthly Summary --}}
        <template x-if="tab === 'monthly' && monthlySummary && !loading">
            <div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    <template x-for="(val, key) in monthlySummary.totals" :key="key">
                        <div class="metric-card">
                            <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400" x-text="key.replace(/_/g, ' ')"></div>
                            <div class="mt-2 text-2xl font-bold text-slate-900" x-text="'₹ ' + gst.formatNumber(val)"></div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <script>
        function reportsPage() {
            const profileId = '{{ $activeProfile?->id ?? '' }}';
            return {
                tab: 'sales',
                loading: false,
                reportData: null,
                monthlySummary: null,
                filters: { from_date: '', to_date: '', status: '' },
                monthFilter: new Date().toISOString().slice(0,7),
                get downloadUrl() {
                    return `/api/export/sales.csv?business_profile_id=${profileId}&from_date=${this.filters.from_date}&to_date=${this.filters.to_date}&status=${this.filters.status}`;
                },
                async loadReport() {
                    if (!profileId) return;
                    this.loading = true;
                    try {
                        const q = new URLSearchParams({ business_profile_id: profileId, ...this.filters }).toString();
                        const res = await gst.api(`/reports/${this.tab}?${q}`);
                        this.reportData = res.data;
                    } catch (e) { gst.toast(e.message, 'error'); }
                    this.loading = false;
                },
                async loadMonthly() {
                    if (!profileId) return;
                    this.loading = true;
                    try {
                        const res = await gst.api(`/reports/monthly-gst-summary?business_profile_id=${profileId}&month=${this.monthFilter}`);
                        this.monthlySummary = res.data;
                    } catch (e) { gst.toast(e.message, 'error'); }
                    this.loading = false;
                },
                init() { this.loadReport(); },
            };
        }
    </script>
</x-app-layout>
