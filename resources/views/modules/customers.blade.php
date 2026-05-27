<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="module-kicker">Relationships</p>
            <div class="page-title-row">
                <h1 class="module-title">Customers</h1>
                <x-info-tip placement="bottom" text="Customer GSTIN and state help determine whether invoices use CGST/SGST for intrastate supply or IGST for interstate supply." />
            </div>
            <p class="module-subtitle hidden md:block">Maintain buyer details so invoice creation stays fast and tax treatment is easier to review.</p>
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

        <div x-show="loading" class="mb-6 text-center py-6 text-slate-400">
            <div class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-amber-500 border-t-transparent"></div>
            <p class="mt-2 text-sm">Loading customers...</p>
        </div>

        {{-- Table --}}
        <div class="card-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr>
                        <th>Customer</th><th>GSTIN</th><th>State</th><th>Type</th><th>Business Profiles</th><th>Supply</th><th>Contact</th><th class="text-right">Actions</th>
                    </tr></thead>
                    <tbody>
                        <template x-for="c in filtered" :key="c.id || c._id">
                            <tr>
                                <td class="font-medium text-slate-900" x-text="c.customer_name"></td>
                                <td class="font-mono text-xs" x-text="c.gstin || '—'"></td>
                                <td x-text="c.state || '—'"></td>
                                <td><span class="badge badge-info capitalize" x-text="c.customer_type || 'N/A'"></span></td>
                                <td>
                                    <span class="badge badge-info cursor-help" :title="profileTooltip(c)" x-text="profileLabel(c)"></span>
                                </td>
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
            <template x-if="!loading && filtered.length === 0">
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
                            <div class="form-group"><label class="form-label">GSTIN <x-info-tip text="Optional for consumers, but useful for B2B customers because it helps validate buyer identity." /></label><input x-model="form.gstin" class="form-input font-mono" maxlength="15" placeholder="Optional"></div>
                            <div class="form-group"><label class="form-label">State <x-info-tip text="Customer state is used to understand place of supply and interstate or intrastate treatment." /></label><input x-model="form.state" class="form-input"></div>
                            <div class="form-group sm:col-span-2"><label class="form-label">Address</label><input x-model="form.address" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Phone</label><input x-model="form.phone" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Email</label><input type="email" x-model="form.email" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Customer Type <x-info-tip text="B2B, B2C, or government classification helps reports and invoice review." /></label>
                                <select x-model="form.customer_type" class="form-select">
                                    <option value="business">Business (B2B)</option>
                                    <option value="consumer">Consumer (B2C)</option>
                                    <option value="government">Government</option>
                                </select>
                            </div>
                            <div class="form-group sm:col-span-2">
                                <label class="form-label">Related Business Profiles *</label>
                                <div class="grid gap-2 rounded-xl border p-3" style="border-color: hsl(var(--gst-border));">
                                    <template x-for="profile in availableProfiles" :key="profile.id || profile._id">
                                        <label class="flex items-center gap-3 rounded-lg px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                            <input type="checkbox" :value="profile.id || profile._id" x-model="form.business_profile_ids" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                                            <span x-text="profile.business_name"></span>
                                        </label>
                                    </template>
                                </div>
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
            const profiles = @json($profiles);
            const cacheKey = profileId ? `customers:${profileId}` : 'customers:none';
            return {
                customers: @json($customers),
                availableProfiles: profiles,
                loading: false,
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
                    this.form = c
                        ? { ...c, business_profile_ids: [...(c.business_profile_ids || [])], business_profile_id: c.business_profile_id || c.business_profile_ids?.[0] || profileId }
                        : { customer_name: '', gstin: '', state: '', address: '', phone: '', email: '', customer_type: 'business', business_profile_id: profileId, business_profile_ids: profileId ? [profileId] : [] };
                    this.showModal = true;
                },
                profileLabel(c) {
                    const count = c.business_profiles?.length || c.business_profile_ids?.length || 0;
                    if (!count) return '—';
                    if (count === 1) return c.business_profiles?.[0]?.business_name || '1 profile';
                    return `${count} profiles`;
                },
                profileTooltip(c) {
                    const names = (c.business_profiles || []).map(profile => profile.business_name).filter(Boolean);
                    return names.length ? names.join(', ') : 'No related business profiles';
                },
                async loadCustomers() {
                    if (!profileId) {
                        this.customers = [];
                        this.loading = false;
                        return;
                    }

                    if (this.customers.length > 0) {
                        gst.writeCache(cacheKey, this.customers);
                        return;
                    }

                    const cached = gst.readCache(cacheKey, 300000);
                    if (Array.isArray(cached) && cached.length > 0) {
                        this.customers = cached;
                    }

                    this.loading = true;
                    try {
                        const res = await gst.api(`/customers?business_profile_id=${profileId}`);
                        this.customers = res?.data || [];
                        gst.writeCache(cacheKey, this.customers);
                    } catch (e) {
                        if (!this.customers.length) gst.toast(e.message || 'Unable to load customers', 'error');
                    }
                    this.loading = false;
                },
                async save() {
                    this.saving = true;
                    try {
                        const id = this.editing?.id || this.editing?._id;
                        this.form.business_profile_ids = [...new Set((this.form.business_profile_ids || []).filter(Boolean))];
                        if (!this.form.business_profile_ids.length) {
                            gst.toast('Select at least one related business profile', 'error');
                            this.saving = false;
                            return;
                        }
                        this.form.business_profile_id = this.form.business_profile_ids[0];
                        const url = id ? `/customers/${id}` : '/customers';
                        const method = id ? 'PUT' : 'POST';
                        const res = await gst.api(url, { method, body: JSON.stringify(this.form) });
                        if (id) {
                            const idx = this.customers.findIndex(p => (p.id||p._id) === id);
                            if (idx >= 0) this.customers[idx] = res.data;
                        } else {
                            this.customers.push(res.data);
                        }
                        gst.writeCache(cacheKey, this.customers);
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
                        gst.writeCache(cacheKey, this.customers);
                        gst.toast(res.message);
                    } catch (e) { gst.toast(e.message || 'Error', 'error'); }
                },
                init() {
                    this.loadCustomers();
                },
            };
        }
    </script>
</x-app-layout>
