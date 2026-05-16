<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="module-kicker">Catalog</p>
            <div class="page-title-row">
                <h1 class="module-title">Products</h1>
                <x-info-tip placement="bottom" text="Products store HSN code, GST rate, unit, and price so invoice line items can be filled accurately with fewer manual steps." />
            </div>
            <p class="module-subtitle hidden md:block">Build reusable catalog items with HSN and tax details for faster invoice entry.</p>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="productsPage()">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                @if($profiles->count() > 1)
                <select class="form-select max-w-xs text-sm" @change="window.location = '/products?business_profile_id=' + $event.target.value">
                    @foreach($profiles as $p)
                        <option value="{{ $p->id }}" {{ $activeProfile && $activeProfile->id === $p->id ? 'selected' : '' }}>{{ $p->business_name }}</option>
                    @endforeach
                </select>
                @endif
                <div class="search-bar max-w-xs">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" placeholder="Search products or HSN..." class="flex-1">
                </div>
            </div>
            <button @click="openModal()" class="btn btn-primary" {{ !$activeProfile ? 'disabled' : '' }}>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add Product
            </button>
        </div>

        <div class="card-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr>
                        <th>Product</th><th>HSN</th><th>Category</th><th>Unit</th><th>Price</th><th>GST Rate</th><th>Status</th><th class="text-right">Actions</th>
                    </tr></thead>
                    <tbody>
                        <template x-for="p in filtered" :key="p.id || p._id">
                            <tr>
                                <td><div class="font-medium text-slate-900" x-text="p.product_name"></div><div class="text-xs text-slate-400 truncate max-w-[200px]" x-text="p.description"></div></td>
                                <td class="font-mono text-xs" x-text="p.hsn_code"></td>
                                <td x-text="p.category || '—'"></td>
                                <td x-text="p.unit || '—'"></td>
                                <td class="font-medium" x-text="'₹ ' + gst.formatNumber(p.price)"></td>
                                <td><span class="badge badge-info" x-text="p.gst_rate + '%'"></span></td>
                                <td><span class="badge" :class="p.status === 'active' ? 'badge-active' : 'badge-inactive'" x-text="p.status"></span></td>
                                <td class="text-right">
                                    <button @click="openModal(p)" class="btn btn-ghost btn-xs">Edit</button>
                                    <button @click="remove(p)" class="btn btn-ghost btn-xs text-red-500">Delete</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <template x-if="filtered.length === 0">
                <div class="empty-state py-12">
                    <div class="empty-icon"><svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
                    <h3>No products yet</h3>
                    <p>Add products to your catalog to use them in invoices.</p>
                </div>
            </template>
        </div>

        <template x-if="showModal">
            <div class="modal-backdrop" @click.self="showModal = false">
                <div class="modal-panel">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="modal-title" x-text="editing ? 'Edit Product' : 'New Product'"></h2>
                        <button @click="showModal = false" class="btn-ghost rounded-lg p-1"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form @submit.prevent="save()" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <template x-if="!editing">
                                <div class="form-group sm:col-span-2">
                                    <label class="form-label">Add product to *</label>
                                    <div class="relative">
                                        <button type="button" class="form-select w-full text-left" @click="profileDropdownOpen = !profileDropdownOpen">
                                            <span class="block truncate" x-text="selectedProfilesLabel()"></span>
                                        </button>
                                        <div x-show="profileDropdownOpen" x-transition @click.outside="profileDropdownOpen = false" class="absolute z-30 mt-2 w-full rounded-xl border bg-white p-2 shadow-lg" style="border-color: hsl(var(--gst-border));">
                                            <button type="button" class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-sm font-semibold hover:bg-slate-50" @click="toggleAllProfiles()">
                                                <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500" :checked="isAllProfilesSelected()">
                                                <span>All Profiles</span>
                                            </button>
                                            <div class="my-1 h-px bg-slate-100"></div>
                                            <template x-for="profile in profileOptions" :key="profile.id">
                                                <button type="button" class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-sm hover:bg-slate-50" @click="toggleProfile(profile.id)">
                                                    <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500" :checked="isProfileSelected(profile.id)">
                                                    <span class="truncate" x-text="profile.name"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">Default is all business profiles.</p>
                                </div>
                            </template>
                            <div class="form-group sm:col-span-2"><label class="form-label">Product Name *</label><input x-model="form.product_name" class="form-input" required></div>
                            <div class="form-group sm:col-span-2"><label class="form-label">Description</label><input x-model="form.description" class="form-input"></div>
                            <div class="form-group"><label class="form-label">HSN Code * <x-info-tip text="Selecting an HSN can auto-fill category, description, and GST rate when available." /></label>
                                <select x-model="form.hsn_code" class="form-select" required @change="autoFillHsn()">
                                    <option value="">Select HSN</option>
                                    @foreach($hsnCodes as $h)
                                    <option value="{{ $h->hsn_code }}" data-rate="{{ $h->gst_rate }}" data-cat="{{ $h->category }}" data-desc="{{ $h->description }}">{{ $h->hsn_code }} — {{ Str::limit($h->description, 40) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group"><label class="form-label">Category</label><input x-model="form.category" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Unit</label>
                                <select x-model="form.unit" class="form-select"><option value="NOS">NOS</option><option value="KGS">KGS</option><option value="MTR">MTR</option><option value="LTR">LTR</option><option value="SQM">SQM</option><option value="PCS">PCS</option><option value="SET">SET</option><option value="HRS">HRS</option></select>
                            </div>
                            <div class="form-group"><label class="form-label">Price (₹) * <x-info-tip text="Base item price before GST. The invoice adds tax separately." /></label><input type="number" step="0.01" x-model="form.price" class="form-input" required></div>
                            <div class="form-group"><label class="form-label">GST Rate (%) * <x-info-tip text="Rate used when this product is added to invoice line items." /></label>
                                <select x-model="form.gst_rate" class="form-select" required>
                                    @foreach($taxSlabs as $s)<option value="{{ $s->rate }}">{{ $s->rate }}%</option>@endforeach
                                </select>
                            </div>
                            <div class="form-group"><label class="form-label">Status</label>
                                <select x-model="form.status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
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
        function productsPage() {
            const profileId = '{{ $activeProfile?->id ?? '' }}';
            const allProfileIds = @json($profiles->pluck('id')->values());
            const profileOptions = @json($profiles->map(fn($p) => ['id' => (string) $p->id, 'name' => $p->business_name])->values());
            return {
                products: @json($products),
                search: '',
                showModal: false,
                profileDropdownOpen: false,
                editing: null,
                saving: false,
                form: {},
                profileOptions,
                get filtered() {
                    const q = this.search.toLowerCase();
                    return this.products.filter(p =>
                        !q || (p.product_name||'').toLowerCase().includes(q) || (p.hsn_code||'').toLowerCase().includes(q) || (p.category||'').toLowerCase().includes(q)
                    );
                },
                selectedProfilesLabel() {
                    const selected = this.profileOptions.filter(p => this.isProfileSelected(p.id));
                    if (selected.length === 0) return 'Select profiles';
                    if (selected.length === this.profileOptions.length) return 'All Profiles';
                    if (selected.length <= 2) return selected.map(p => p.name).join(', ');
                    return `${selected.length} profiles selected`;
                },
                isProfileSelected(profileIdValue) {
                    return (this.form.business_profile_ids || []).includes(profileIdValue);
                },
                isAllProfilesSelected() {
                    return this.profileOptions.length > 0
                        && (this.form.business_profile_ids || []).length === this.profileOptions.length;
                },
                toggleProfile(profileIdValue) {
                    const current = [...(this.form.business_profile_ids || [])];
                    if (current.includes(profileIdValue)) {
                        this.form.business_profile_ids = current.filter(id => id !== profileIdValue);
                    } else {
                        this.form.business_profile_ids = [...current, profileIdValue];
                    }
                },
                toggleAllProfiles() {
                    this.form.business_profile_ids = this.isAllProfilesSelected() ? [] : [...allProfileIds];
                },
                autoFillHsn() {
                    const opt = document.querySelector(`select option[value="${this.form.hsn_code}"]`);
                    if (opt) {
                        this.form.gst_rate = opt.dataset.rate || this.form.gst_rate;
                        if (!this.form.category) this.form.category = opt.dataset.cat || '';
                        if (!this.form.description) this.form.description = opt.dataset.desc || '';
                    }
                },
                openModal(p = null) {
                    this.editing = p;
                    this.form = p
                        ? { ...p, business_profile_ids: [p.business_profile_id || profileId].filter(Boolean) }
                        : { product_name: '', description: '', hsn_code: '', category: '', unit: 'NOS', price: '', gst_rate: '18', status: 'active', business_profile_id: profileId, business_profile_ids: [...allProfileIds] };
                    this.profileDropdownOpen = false;
                    this.showModal = true;
                },
                async save() {
                    this.saving = true;
                    try {
                        const id = this.editing?.id || this.editing?._id;
                        if (!id) {
                            this.form.business_profile_ids = (this.form.business_profile_ids || []).filter(Boolean);
                            if (this.form.business_profile_ids.length === 0) this.form.business_profile_ids = [...allProfileIds];
                            this.form.business_profile_id = this.form.business_profile_id || this.form.business_profile_ids[0] || profileId;
                        } else {
                            this.form.business_profile_id = this.form.business_profile_id || profileId;
                        }
                        const url = id ? `/products/${id}` : '/products';
                        const method = id ? 'PUT' : 'POST';
                        const res = await gst.api(url, { method, body: JSON.stringify(this.form) });
                        if (id) {
                            const idx = this.products.findIndex(x => (x.id||x._id) === id);
                            if (idx >= 0) this.products[idx] = res.data;
                        } else {
                            const created = Array.isArray(res.created_products) ? res.created_products : (res.data ? [res.data] : []);
                            if (profileId) {
                                this.products.push(...created.filter(x => x.business_profile_id === profileId));
                            } else {
                                this.products.push(...created);
                            }
                        }
                        this.showModal = false;
                        gst.toast(res.message);
                    } catch (e) { gst.toast(e.message || 'Error', 'error'); }
                    this.saving = false;
                },
                async remove(p) {
                    if (!confirm('Delete this product?')) return;
                    const id = p.id || p._id;
                    try {
                        const res = await gst.api(`/products/${id}`, { method: 'DELETE' });
                        this.products = this.products.filter(x => (x.id||x._id) !== id);
                        gst.toast(res.message);
                    } catch (e) { gst.toast(e.message || 'Error', 'error'); }
                },
            };
        }
    </script>
</x-app-layout>
