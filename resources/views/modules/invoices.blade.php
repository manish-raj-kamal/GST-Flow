<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Transactions</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Invoices</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="invoicesPage()">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                @if($profiles->count() > 1)
                <select class="form-select max-w-xs text-sm" @change="window.location = '/invoices?business_profile_id=' + $event.target.value">
                    @foreach($profiles as $p)
                        <option value="{{ $p->id }}" {{ $activeProfile && $activeProfile->id === $p->id ? 'selected' : '' }}>{{ $p->business_name }}</option>
                    @endforeach
                </select>
                @endif
                <div class="search-bar max-w-xs">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" placeholder="Search invoices..." class="flex-1">
                </div>
                <select x-model="statusFilter" class="form-select text-sm max-w-[140px]">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="issued">Issued</option>
                    <option value="paid">Paid</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <a href="{{ route('invoices.create') }}{{ $activeProfile ? '?business_profile_id='.$activeProfile->id : '' }}" class="btn btn-primary {{ !$activeProfile ? 'pointer-events-none opacity-50' : '' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Invoice
            </a>
        </div>

        {{-- Stats row --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="metric-card">
                <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Total</div>
                <div class="mt-2 text-2xl font-bold text-slate-900" x-text="invoices.length"></div>
            </div>
            <div class="metric-card">
                <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Total Value</div>
                <div class="mt-2 text-2xl font-bold text-slate-900" x-text="'₹ ' + gst.formatNumber(invoices.reduce((s,i)=>s + parseFloat(i.total_amount||0), 0))"></div>
            </div>
            <div class="metric-card">
                <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Total Tax</div>
                <div class="mt-2 text-2xl font-bold text-emerald-600" x-text="'₹ ' + gst.formatNumber(invoices.reduce((s,i)=>s + parseFloat(i.total_tax||0), 0))"></div>
            </div>
            <div class="metric-card">
                <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Issued</div>
                <div class="mt-2 text-2xl font-bold text-amber-600" x-text="invoices.filter(i=>i.status==='issued').length"></div>
            </div>
        </div>

        <div class="card-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr>
                        <th>Invoice #</th><th>Date</th><th>Customer</th><th>Type</th><th>Taxable</th><th>Tax</th><th>Total</th><th>Status</th><th class="text-right">Actions</th>
                    </tr></thead>
                    <tbody>
                        <template x-for="inv in filtered" :key="inv.id || inv._id">
                            <tr>
                                <td class="font-mono font-medium text-slate-900" x-text="inv.invoice_number"></td>
                                <td class="text-sm" x-text="gst.formatDate(inv.invoice_date)"></td>
                                <td x-text="customerName(inv.customer_id)"></td>
                                <td><span class="badge badge-info uppercase" x-text="inv.transaction_type"></span></td>
                                <td class="text-right font-medium" x-text="'₹ ' + gst.formatNumber(inv.taxable_value)"></td>
                                <td class="text-right text-emerald-600" x-text="'₹ ' + gst.formatNumber(inv.total_tax)"></td>
                                <td class="text-right font-bold text-slate-900" x-text="'₹ ' + gst.formatNumber(inv.total_amount)"></td>
                                <td>
                                    <span class="badge" :class="{'badge-active': inv.status==='issued' || inv.status==='paid', 'badge-warning': inv.status==='draft', 'badge-danger': inv.status==='cancelled', 'badge-inactive': !['issued','paid','draft','cancelled'].includes(inv.status)}" x-text="inv.status"></span>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <button @click="viewInvoice(inv)" class="btn btn-ghost btn-xs">View</button>
                                    <button @click="duplicateInvoice(inv)" class="btn btn-ghost btn-xs">Dup</button>
                                    <button @click="deleteInvoice(inv)" class="btn btn-ghost btn-xs text-red-500">Del</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <template x-if="filtered.length === 0">
                <div class="empty-state py-12">
                    <div class="empty-icon"><svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
                    <h3>No invoices yet</h3>
                    <p>Create your first GST-compliant invoice.</p>
                </div>
            </template>
        </div>

        {{-- Invoice Detail Modal --}}
        <template x-if="viewingInvoice">
            <div class="modal-backdrop" @click.self="viewingInvoice = null">
                <div class="modal-panel-lg max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="modal-title">Invoice <span x-text="viewingInvoice.invoice_number" class="text-amber-600"></span></h2>
                        <div class="flex items-center gap-2">
                            <a :href="'/api/export/invoices/' + (viewingInvoice.id || viewingInvoice._id) + '/pdf'" target="_blank" class="btn btn-secondary btn-sm">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> PDF
                            </a>
                            <button @click="viewingInvoice = null" class="btn-ghost rounded-lg p-1"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 text-sm mb-4">
                        <div><span class="text-slate-400">Date:</span> <span x-text="gst.formatDate(viewingInvoice.invoice_date)" class="font-medium"></span></div>
                        <div><span class="text-slate-400">Status:</span> <span class="badge badge-active uppercase" x-text="viewingInvoice.status"></span></div>
                        <div><span class="text-slate-400">Seller GSTIN:</span> <span class="font-mono" x-text="viewingInvoice.seller_gstin || '—'"></span></div>
                        <div><span class="text-slate-400">Buyer GSTIN:</span> <span class="font-mono" x-text="viewingInvoice.buyer_gstin || '—'"></span></div>
                        <div><span class="text-slate-400">Supply:</span> <span x-text="viewingInvoice.place_of_supply || '—'"></span></div>
                        <div><span class="text-slate-400">Type:</span> <span class="uppercase" x-text="viewingInvoice.transaction_type"></span></div>
                    </div>
                    <h4 class="text-sm font-semibold text-slate-700 mb-2">Line Items</h4>
                    <div class="overflow-x-auto rounded-xl border" style="border-color: hsl(var(--gst-border));">
                        <table class="data-table">
                            <thead><tr><th>Product</th><th>HSN</th><th>Qty</th><th>Rate</th><th>Tax%</th><th>Tax Amt</th><th>Total</th></tr></thead>
                            <tbody>
                                <template x-for="item in (viewingInvoice.items || [])" :key="item.product_name">
                                    <tr>
                                        <td x-text="item.product_name"></td>
                                        <td class="font-mono text-xs" x-text="item.hsn_code"></td>
                                        <td x-text="item.quantity"></td>
                                        <td x-text="'₹ ' + gst.formatNumber(item.rate)"></td>
                                        <td x-text="item.tax_rate + '%'"></td>
                                        <td x-text="'₹ ' + gst.formatNumber(item.tax_amount)"></td>
                                        <td class="font-medium" x-text="'₹ ' + gst.formatNumber(item.line_total)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-3 text-sm">
                        <div class="rounded-xl bg-slate-50 p-3"><div class="text-slate-400">Taxable</div><div class="text-lg font-bold" x-text="'₹ ' + gst.formatNumber(viewingInvoice.taxable_value)"></div></div>
                        <div class="rounded-xl bg-emerald-50 p-3"><div class="text-slate-400">Total Tax</div><div class="text-lg font-bold text-emerald-700" x-text="'₹ ' + gst.formatNumber(viewingInvoice.total_tax)"></div><div class="text-xs text-slate-400 mt-1"><span x-text="'CGST: ₹' + gst.formatNumber(viewingInvoice.cgst)"></span> · <span x-text="'SGST: ₹' + gst.formatNumber(viewingInvoice.sgst)"></span> · <span x-text="'IGST: ₹' + gst.formatNumber(viewingInvoice.igst)"></span></div></div>
                        <div class="rounded-xl bg-amber-50 p-3"><div class="text-slate-400">Grand Total</div><div class="text-lg font-bold text-amber-700" x-text="'₹ ' + gst.formatNumber(viewingInvoice.total_amount)"></div></div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
        function invoicesPage() {
            const customersMap = Object.fromEntries(@json($customers).map(c => [c.id || c._id, c.customer_name]));
            return {
                invoices: @json($invoices),
                search: '',
                statusFilter: '',
                viewingInvoice: null,
                get filtered() {
                    const q = this.search.toLowerCase();
                    return this.invoices.filter(inv => {
                        if (this.statusFilter && inv.status !== this.statusFilter) return false;
                        if (!q) return true;
                        return (inv.invoice_number||'').toLowerCase().includes(q) || (inv.place_of_supply||'').toLowerCase().includes(q) || this.customerName(inv.customer_id).toLowerCase().includes(q);
                    });
                },
                customerName(id) { return customersMap[id] || '—'; },
                viewInvoice(inv) { this.viewingInvoice = inv; },
                async duplicateInvoice(inv) {
                    if (!confirm('Duplicate this invoice?')) return;
                    try {
                        const res = await gst.api(`/invoices/${inv.id || inv._id}/duplicate`, { method: 'POST' });
                        this.invoices.unshift(res.data);
                        gst.toast(res.message);
                    } catch (e) { gst.toast(e.message || 'Error', 'error'); }
                },
                async deleteInvoice(inv) {
                    if (!confirm('Delete this invoice?')) return;
                    const id = inv.id || inv._id;
                    try {
                        await gst.api(`/invoices/${id}`, { method: 'DELETE' });
                        this.invoices = this.invoices.filter(i => (i.id||i._id) !== id);
                        gst.toast('Invoice deleted');
                    } catch (e) { gst.toast(e.message || 'Error', 'error'); }
                },
            };
        }
    </script>
</x-app-layout>
