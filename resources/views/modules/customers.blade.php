<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Relationships</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Customers</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="customersPage()">
        {{-- Profile selector & actions --}}
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                @if($profiles->count() > 1)
                <select class="form-select max-w-xs text-sm" @change="window.location = '/customers?business_profile_id=' + $event.target.value">
                    @foreach($profiles as $p)
                        <option value="{{ $p->id }}" {{ $activeProfile && $activeProfile->id === $p->id ? 'selected' : '' }}>{{ $p->business_name }}</option>
                    @endforeach
                </select>
                @endif
                <div class="search-bar max-w-xs">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" placeholder="Search customers..." class="flex-1">
                </div>
            </div>
            <button @click="openModal()" class="btn btn-primary" {{ !$activeProfile ? 'disabled' : '' }}>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add Customer
            </button>
        </div>

        {{-- Table --}}
        <div class="card-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr>
                        <th>Customer</th><th>GSTIN</th><th>State</th><th>Type</th><th>Supply</th><th>Contact</th><th class="text-right">Actions</th>
                    </tr></thead>
                    <tbody>
                        <template x-for="c in filtered" :key="c.id || c._id">
                            <tr>
                                <td class="font-medium text-slate-900" x-text="c.customer_name"></td>
                                <td class="font-mono text-xs" x-text="c.gstin || '—'"></td>
                                <td x-text="c.state || '—'"></td>
                                <td><span class="badge badge-info capitalize" x-text="c.customer_type || 'N/A'"></span></td>
                                <td><span class="badge" :class="c.is_interstate ? 'badge-warning' : 'badge-active'" x-text="c.is_interstate ? 'Interstate' : 'Intrastate'"></span></td>
                                <td class="text-xs text-slate-500" x-text="c.email || c.phone || '—'"></td>
                                <td class="text-right">
                                    <button @click="openModal(c)" class="btn btn-ghost btn-xs">Edit</button>
                                    <button @click="remove(c)" class="btn btn-ghost btn-xs text-red-500">Delete</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <template x-if="filtered.length === 0">
                <div class="empty-state py-12">
                    <div class="empty-icon"><svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
                    <h3>No customers yet</h3>
                    <p>Add customers to start generating invoices.</p>
                </div>
            </template>
        </div>

        {{-- Modal --}}
        <template x-if="showModal">
            <div class="modal-backdrop" @click.self="showModal = false">
                <div class="modal-panel">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="modal-title" x-text="editing ? 'Edit Customer' : 'New Customer'"></h2>
                        <button @click="showModal = false" class="btn-ghost rounded-lg p-1"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form @submit.prevent="save()" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="form-group sm:col-span-2"><label class="form-label">Customer Name *</label><input x-model="form.customer_name" class="form-input" required></div>
                            <div class="form-group"><label class="form-label">GSTIN</label><input x-model="form.gstin" class="form-input font-mono" maxlength="15" placeholder="Optional"></div>
                            <div class="form-group"><label class="form-label">State</label><input x-model="form.state" class="form-input"></div>
                            <div class="form-group sm:col-span-2"><label class="form-label">Address</label><input x-model="form.address" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Phone</label><input x-model="form.phone" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Email</label><input type="email" x-model="form.email" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Customer Type</label>
                                <select x-model="form.customer_type" class="form-select">
                                    <option value="business">Business (B2B)</option>
                                    <option value="consumer">Consumer (B2C)</option>
                                    <option value="government">Government</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false" class="btn btn-secondary">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span x-show="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                <span x-text="editing ? 'Update' : 'Create'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

    <script>
        function customersPage() {
            const profileId = '{{ $activeProfile?->id ?? '' }}';
            return {
                customers: @json($customers),
                search: '',
                showModal: false,
                editing: null,
                saving: false,
                form: {},
                get filtered() {
                    const q = this.search.toLowerCase();
                    return this.customers.filter(c =>
                        !q || (c.customer_name||'').toLowerCase().includes(q) || (c.gstin||'').toLowerCase().includes(q)
                    );
                },
                openModal(c = null) {
                    this.editing = c;
                    this.form = c ? { ...c } : { customer_name: '', gstin: '', state: '', address: '', phone: '', email: '', customer_type: 'business', business_profile_id: profileId };
                    if (!c) this.form.business_profile_id = profileId;
                    this.showModal = true;
                },
                async save() {
                    this.saving = true;
                    try {
                        const id = this.editing?.id || this.editing?._id;
                        this.form.business_profile_id = this.form.business_profile_id || profileId;
                        const url = id ? `/customers/${id}` : '/customers';
                        const method = id ? 'PUT' : 'POST';
                        const res = await gst.api(url, { method, body: JSON.stringify(this.form) });
                        if (id) {
                            const idx = this.customers.findIndex(p => (p.id||p._id) === id);
                            if (idx >= 0) this.customers[idx] = res.data;
                        } else {
                            this.customers.push(res.data);
                        }
                        this.showModal = false;
                        gst.toast(res.message);
                    } catch (e) { gst.toast(e.message || 'Error', 'error'); }
                    this.saving = false;
                },
                async remove(c) {
                    if (!confirm('Delete this customer?')) return;
                    const id = c.id || c._id;
                    try {
                        const res = await gst.api(`/customers/${id}`, { method: 'DELETE' });
                        this.customers = this.customers.filter(p => (p.id||p._id) !== id);
                        gst.toast(res.message);
                    } catch (e) { gst.toast(e.message || 'Error', 'error'); }
                },
            };
        }
    </script>
</x-app-layout>
