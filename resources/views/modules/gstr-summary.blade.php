<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Compliance</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">GSTR Summary</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="gstrPage()">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500 max-w-xl">Structured return-ready summaries organized similar to GSTR-1 and GSTR-3B datasets. These are generated from your invoice data.</p>
            <div class="flex items-center gap-2">
                @if($profiles->count() > 1)
                <select class="form-select text-sm max-w-xs" @change="window.location = '/gstr-summary?business_profile_id=' + $event.target.value">
                    @foreach($profiles as $p)<option value="{{ $p->id }}" {{ $activeProfile && $activeProfile->id === $p->id ? 'selected' : '' }}>{{ $p->business_name }}</option>@endforeach
                </select>
                @endif
                <button @click="load()" class="btn btn-primary btn-sm">Generate</button>
            </div>
        </div>

        <div x-show="loading" class="text-center py-12 text-slate-400"><div class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-amber-500 border-t-transparent"></div></div>

        <template x-if="data && !loading">
            <div class="space-y-6">
                {{-- GSTR-1 Style: Outward Supplies --}}
                <div class="card-lg">
                    <h3 class="panel-title mb-4">Outward Supplies Summary <span class="text-xs text-slate-400">(GSTR-1 style)</span></h3>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-xl bg-slate-50 p-4"><div class="text-[10px] uppercase tracking-[0.2em] text-slate-400 mb-1">Total Invoices</div><div class="text-2xl font-bold" x-text="data.outward_supplies?.invoice_count || 0"></div></div>
                        <div class="rounded-xl bg-slate-50 p-4"><div class="text-[10px] uppercase tracking-[0.2em] text-slate-400 mb-1">Taxable Turnover</div><div class="text-2xl font-bold" x-text="'₹ ' + gst.formatNumber(data.outward_supplies?.taxable_value)"></div></div>
                        <div class="rounded-xl bg-emerald-50 p-4"><div class="text-[10px] uppercase tracking-[0.2em] text-slate-400 mb-1">Total Tax Liability</div><div class="text-2xl font-bold text-emerald-700" x-text="'₹ ' + gst.formatNumber(data.outward_supplies?.total_tax)"></div></div>
                        <div class="rounded-xl bg-amber-50 p-4"><div class="text-[10px] uppercase tracking-[0.2em] text-slate-400 mb-1">Grand Total</div><div class="text-2xl font-bold text-amber-700" x-text="'₹ ' + gst.formatNumber(data.outward_supplies?.total_amount)"></div></div>
                    </div>
                </div>

                {{-- Tax Liability --}}
                <div class="card-lg">
                    <h3 class="panel-title mb-4">Tax Liability <span class="text-xs text-slate-400">(GSTR-3B style)</span></h3>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-xl bg-sky-50 p-4 text-center"><div class="text-xs text-slate-400 mb-1">CGST</div><div class="text-2xl font-bold text-sky-700" x-text="'₹ ' + gst.formatNumber(data.tax_liability?.cgst)"></div></div>
                        <div class="rounded-xl bg-indigo-50 p-4 text-center"><div class="text-xs text-slate-400 mb-1">SGST</div><div class="text-2xl font-bold text-indigo-700" x-text="'₹ ' + gst.formatNumber(data.tax_liability?.sgst)"></div></div>
                        <div class="rounded-xl bg-purple-50 p-4 text-center"><div class="text-xs text-slate-400 mb-1">IGST</div><div class="text-2xl font-bold text-purple-700" x-text="'₹ ' + gst.formatNumber(data.tax_liability?.igst)"></div></div>
                    </div>
                </div>

                {{-- HSN-wise Summary --}}
                <div class="card-lg" x-show="data.hsn_summary && data.hsn_summary.length">
                    <h3 class="panel-title mb-4">HSN-wise Summary</h3>
                    <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>HSN</th><th>Description</th><th>Quantity</th><th>Taxable</th><th>Tax</th><th>Total</th></tr></thead><tbody>
                        <template x-for="h in (data.hsn_summary || [])" :key="h.hsn_code"><tr>
                            <td class="font-mono" x-text="h.hsn_code"></td><td x-text="h.description || '—'"></td><td x-text="h.quantity || 0"></td>
                            <td x-text="'₹ ' + gst.formatNumber(h.taxable_value)"></td><td x-text="'₹ ' + gst.formatNumber(h.tax_amount)"></td><td class="font-medium" x-text="'₹ ' + gst.formatNumber(h.total)"></td>
                        </tr></template>
                    </tbody></table></div>
                </div>

                {{-- State-wise --}}
                <div class="card-lg" x-show="data.state_wise_supplies && data.state_wise_supplies.length">
                    <h3 class="panel-title mb-4">State-wise Supply Totals</h3>
                    <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>State</th><th>Invoices</th><th>Taxable</th><th>Tax</th><th>Total</th></tr></thead><tbody>
                        <template x-for="s in (data.state_wise_supplies || [])" :key="s.state"><tr>
                            <td class="font-medium" x-text="s.state"></td><td x-text="s.count || 0"></td>
                            <td x-text="'₹ ' + gst.formatNumber(s.taxable_value)"></td><td x-text="'₹ ' + gst.formatNumber(s.tax)"></td><td class="font-medium" x-text="'₹ ' + gst.formatNumber(s.total)"></td>
                        </tr></template>
                    </tbody></table></div>
                </div>
            </div>
        </template>
    </div>

    <script>
        function gstrPage() {
            const profileId = '{{ $activeProfile?->id ?? '' }}';
            return {
                loading: false,
                data: null,
                async load() {
                    if (!profileId) { gst.toast('Select a business profile', 'error'); return; }
                    this.loading = true;
                    try {
                        const res = await gst.api(`/gstr-summary?business_profile_id=${profileId}`);
                        this.data = res.data;
                    } catch (e) { gst.toast(e.message, 'error'); }
                    this.loading = false;
                },
                init() { if (profileId) this.load(); },
            };
        }
    </script>
</x-app-layout>
