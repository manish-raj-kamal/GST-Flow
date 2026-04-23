<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Create</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">New Invoice</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="invoiceFormPage()">
        <form @submit.prevent="submit()" class="mx-auto max-w-4xl space-y-6">
            {{-- Invoice Basics --}}
            <div class="card-lg">
                <h3 class="panel-title mb-4">Invoice Details</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="form-group">
                        <label class="form-label">Business Profile *</label>
                        <select x-model="form.business_profile_id" class="form-select" required @change="onProfileChange()">
                            <option value="">Select profile</option>
                            @foreach($profiles as $p)
                            <option value="{{ $p->id }}">{{ $p->business_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Customer *</label>
                        <select x-model="form.customer_id" class="form-select" required>
                            <option value="">Select customer</option>
                            <template x-for="c in customers" :key="c.id || c._id">
                                <option :value="c.id || c._id" x-text="c.customer_name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Invoice Date *</label>
                        <input type="date" x-model="form.invoice_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Transaction Type</label>
                        <select x-model="form.transaction_type" class="form-select">
                            <option value="sale">Sale</option>
                            <option value="purchase">Purchase</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Place of Supply</label>
                        <select x-model="form.place_of_supply" class="form-select">
                            <option value="">Auto-detect</option>
                            @foreach($stateCodes as $sc)
                            <option value="{{ $sc->state_name }}">{{ $sc->code }} — {{ $sc->state_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select x-model="form.status" class="form-select">
                            <option value="draft">Draft</option>
                            <option value="issued">Issued</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="card-lg">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="panel-title">Line Items</h3>
                    <button type="button" @click="addItem()" class="btn btn-secondary btn-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Add Item
                    </button>
                </div>

                <template x-for="(item, idx) in form.items" :key="idx">
                    <div class="mb-4 rounded-xl border p-4" style="border-color: hsl(var(--gst-border));">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400" x-text="'Item #' + (idx + 1)"></span>
                            <button type="button" @click="form.items.splice(idx, 1); recalc()" class="text-red-400 hover:text-red-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="form-group lg:col-span-2">
                                <label class="form-label text-[10px]">Product</label>
                                <select x-model="item.product_id" class="form-select text-sm" @change="fillProduct(idx)">
                                    <option value="">Select or type</option>
                                    <template x-for="p in products" :key="p.id || p._id">
                                        <option :value="p.id || p._id" x-text="p.product_name + ' (' + p.hsn_code + ')'"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="form-group"><label class="form-label text-[10px]">Product Name</label><input x-model="item.product_name" class="form-input text-sm"></div>
                            <div class="form-group"><label class="form-label text-[10px]">HSN Code</label><input x-model="item.hsn_code" class="form-input text-sm font-mono"></div>
                            <div class="form-group"><label class="form-label text-[10px]">Quantity</label><input type="number" min="1" x-model.number="item.quantity" class="form-input text-sm" @input="recalc()"></div>
                            <div class="form-group"><label class="form-label text-[10px]">Rate (₹)</label><input type="number" step="0.01" x-model.number="item.rate" class="form-input text-sm" @input="recalc()"></div>
                            <div class="form-group"><label class="form-label text-[10px]">Tax Rate (%)</label><input type="number" step="0.01" x-model.number="item.tax_rate" class="form-input text-sm" @input="recalc()"></div>
                            <div class="form-group">
                                <label class="form-label text-[10px]">Line Total</label>
                                <div class="form-input bg-slate-50 font-semibold text-slate-900" x-text="'₹ ' + gst.formatNumber(item.line_total)"></div>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="form.items.length === 0">
                    <div class="text-center py-8 text-sm text-slate-400">No items added. Click "Add Item" to begin.</div>
                </template>
            </div>

            {{-- Totals --}}
            <div class="card-lg">
                <h3 class="panel-title mb-4">Tax Summary</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-xl bg-slate-50 p-4 text-center"><div class="text-[10px] uppercase tracking-[0.2em] text-slate-400 mb-1">Taxable Value</div><div class="text-xl font-bold" x-text="'₹ ' + gst.formatNumber(totals.taxable)"></div></div>
                    <div class="rounded-xl bg-sky-50 p-4 text-center"><div class="text-[10px] uppercase tracking-[0.2em] text-slate-400 mb-1">CGST</div><div class="text-xl font-bold text-sky-700" x-text="'₹ ' + gst.formatNumber(totals.cgst)"></div></div>
                    <div class="rounded-xl bg-indigo-50 p-4 text-center"><div class="text-[10px] uppercase tracking-[0.2em] text-slate-400 mb-1">SGST</div><div class="text-xl font-bold text-indigo-700" x-text="'₹ ' + gst.formatNumber(totals.sgst)"></div></div>
                    <div class="rounded-xl bg-purple-50 p-4 text-center"><div class="text-[10px] uppercase tracking-[0.2em] text-slate-400 mb-1">IGST</div><div class="text-xl font-bold text-purple-700" x-text="'₹ ' + gst.formatNumber(totals.igst)"></div></div>
                    <div class="rounded-xl bg-amber-50 p-4 text-center"><div class="text-[10px] uppercase tracking-[0.2em] text-slate-400 mb-1">Grand Total</div><div class="text-xl font-bold text-amber-700" x-text="'₹ ' + gst.formatNumber(totals.total)"></div></div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('invoices') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                    Create Invoice
                </button>
            </div>
        </form>
    </div>

    <script>
        function invoiceFormPage() {
            const profileId = '{{ $activeProfile?->id ?? '' }}';
            return {
                customers: @json($customers),
                products: @json($products),
                saving: false,
                form: {
                    business_profile_id: profileId,
                    customer_id: '',
                    invoice_date: new Date().toISOString().split('T')[0],
                    transaction_type: 'sale',
                    place_of_supply: '',
                    status: 'draft',
                    items: [],
                },
                totals: { taxable: 0, cgst: 0, sgst: 0, igst: 0, tax: 0, total: 0 },
                addItem() {
                    this.form.items.push({ product_id: '', product_name: '', hsn_code: '', quantity: 1, rate: 0, tax_rate: 18, tax_amount: 0, line_total: 0 });
                },
                fillProduct(idx) {
                    const pid = this.form.items[idx].product_id;
                    const prod = this.products.find(p => (p.id||p._id) === pid);
                    if (prod) {
                        this.form.items[idx].product_name = prod.product_name;
                        this.form.items[idx].hsn_code = prod.hsn_code;
                        this.form.items[idx].rate = parseFloat(prod.price) || 0;
                        this.form.items[idx].tax_rate = parseFloat(prod.gst_rate) || 18;
                        this.recalc();
                    }
                },
                recalc() {
                    let taxable = 0;
                    this.form.items.forEach(item => {
                        const base = (item.quantity || 0) * (item.rate || 0);
                        item.tax_amount = +(base * (item.tax_rate || 0) / 100).toFixed(2);
                        item.line_total = +(base + item.tax_amount).toFixed(2);
                        taxable += base;
                    });
                    const totalTax = this.form.items.reduce((s, i) => s + (i.tax_amount || 0), 0);
                    // Simple split: if interstate use IGST, otherwise CGST/SGST
                    const isInterstate = false; // determined server-side
                    this.totals = {
                        taxable: +taxable.toFixed(2),
                        cgst: +(totalTax / 2).toFixed(2),
                        sgst: +(totalTax / 2).toFixed(2),
                        igst: 0,
                        tax: +totalTax.toFixed(2),
                        total: +(taxable + totalTax).toFixed(2),
                    };
                },
                onProfileChange() {
                    // Could reload customers for selected profile
                },
                async submit() {
                    if (!this.form.items.length) { gst.toast('Add at least one item', 'error'); return; }
                    this.saving = true;
                    try {
                        const res = await gst.api('/invoices', { method: 'POST', body: JSON.stringify(this.form) });
                        gst.toast(res.message);
                        window.location.href = '/invoices?business_profile_id=' + this.form.business_profile_id;
                    } catch (e) { gst.toast(e.message || 'Error creating invoice', 'error'); }
                    this.saving = false;
                },
            };
        }
    </script>
</x-app-layout>
