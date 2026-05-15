<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="module-kicker">Classification</p>
            <div class="page-title-row">
                <h1 class="module-title">HSN Codes</h1>
                <x-info-tip placement="bottom" text="HSN codes classify goods and services and connect each item to the correct GST rate for invoice calculation." />
            </div>
            <p class="module-subtitle hidden md:block">Search or maintain HSN mappings used by the product catalog and tax engine.</p>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="hsnPage()">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="search-bar max-w-sm flex-1">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" @input="fetchCatalog()" placeholder="Search item, category, or HSN..." class="flex-1">
            </div>
            @if(auth()->user()->isAdmin())
            <div class="flex gap-2">
                <button @click="syncCatalog()" class="btn btn-secondary">
                    Sync HSN Catalog
                </button>
                <button @click="openModal()" class="btn btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Add HSN Code
                </button>
            </div>
            @endif
        </div>

        <div class="card-lg mb-6" x-show="groupedResults.length > 0 || loadingCatalog">
            <div class="flex items-center justify-between mb-3">
                <h3 class="panel-title !mt-0">HSN Categories</h3>
                <span class="text-xs text-slate-500" x-show="loadingCatalog">Loading...</span>
            </div>
            <div class="space-y-2" x-show="!loadingCatalog">
                <template x-for="group in groupedResults" :key="group.category">
                    <details class="rounded-xl border p-3" style="border-color: hsl(var(--gst-border));">
                        <summary class="cursor-pointer font-semibold text-slate-900">
                            <span x-text="group.category"></span>
                            <span class="text-xs text-slate-500 ml-2" x-text="'(' + group.count + ' items)'"></span>
                        </summary>
                        <div class="mt-3 overflow-x-auto">
                            <table class="data-table">
                                <thead><tr><th>HSN</th><th>Item</th><th>GST</th></tr></thead>
                                <tbody>
                                    <template x-for="item in group.items" :key="item.id">
                                        <tr>
                                            <td class="font-mono" x-text="item.hsn_code"></td>
                                            <td x-text="item.description"></td>
                                            <td><span class="badge badge-info" x-text="item.gst_rate + '%'"></span></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </details>
                </template>
            </div>
        </div>

        <div class="card-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>HSN Code</th><th>Description</th><th>Category</th><th>GST Rate</th><th>Effective Date</th><th>Status</th>
                        @if(auth()->user()->isAdmin())<th class="text-right">Actions</th>@endif
                    </tr></thead>
                    <tbody>
                        <template x-for="c in filtered" :key="c.id || c._id">
                            <tr>
                                <td class="font-mono font-medium text-slate-900" x-text="c.hsn_code"></td>
                                <td class="max-w-xs truncate" x-text="c.description"></td>
                                <td x-text="c.category || '—'"></td>
                                <td><span class="badge badge-info" x-text="c.gst_rate + '%'"></span></td>
                                <td class="text-sm text-slate-500" x-text="gst.formatDate(c.effective_date)"></td>
                                <td><span class="badge" :class="c.status==='active' ? 'badge-active' : 'badge-inactive'" x-text="c.status"></span></td>
                                @if(auth()->user()->isAdmin())
                                <td class="text-right">
                                    <button @click="openModal(c)" class="btn btn-ghost btn-xs">Edit</button>
                                    <button @click="remove(c)" class="btn btn-ghost btn-xs text-red-500">Delete</button>
                                </td>
                                @endif
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <template x-if="filtered.length === 0">
                <div class="empty-state py-12"><h3>No HSN codes found</h3><p>HSN codes define the GST classification for goods and services.</p></div>
            </template>
        </div>

        @if(auth()->user()->isAdmin())
        <template x-if="showModal">
            <div class="modal-backdrop" @click.self="showModal = false">
                <div class="modal-panel">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="modal-title" x-text="editing ? 'Edit HSN Code' : 'New HSN Code'"></h2>
                        <button @click="showModal = false" class="btn-ghost rounded-lg p-1"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form @submit.prevent="save()" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="form-group"><label class="form-label">HSN Code * <x-info-tip text="HSN or SAC classification code used to identify the tax category." /></label><input x-model="form.hsn_code" class="form-input font-mono" maxlength="8" required></div>
                            <div class="form-group"><label class="form-label">GST Rate (%) * <x-info-tip text="Default GST rate applied when this HSN is selected for a product." /></label><input type="number" step="0.01" x-model="form.gst_rate" class="form-input" required></div>
                            <div class="form-group sm:col-span-2"><label class="form-label">Description *</label><input x-model="form.description" class="form-input" required></div>
                            <div class="form-group"><label class="form-label">Category</label><input x-model="form.category" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Effective Date</label><input type="date" x-model="form.effective_date" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Status</label><select x-model="form.status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false" class="btn btn-secondary">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving"><span x-text="editing ? 'Update' : 'Create'"></span></button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
        @endif
    </div>

    <script>
        function hsnPage() {
            return {
                codes: @json($codes),
                search: '',
                showModal: false,
                editing: null,
                saving: false,
                form: {},
                groupedResults: [],
                loadingCatalog: false,
                syncRunning: false,
                get filtered() {
                    const q = this.search.toLowerCase();
                    return this.codes.filter(c => !q || (c.hsn_code||'').toLowerCase().includes(q) || (c.description||'').toLowerCase().includes(q));
                },
                async fetchCatalog() {
                    this.loadingCatalog = true;
                    try {
                        const query = this.search ? `?search=${encodeURIComponent(this.search)}` : '';
                        const res = await gst.api(`/hsn-codes/catalog${query}`);
                        this.groupedResults = res.data || [];
                    } catch (e) {
                        this.groupedResults = [];
                    }
                    this.loadingCatalog = false;
                },
                async syncCatalog() {
                    if (this.syncRunning) return;
                    this.syncRunning = true;
                    try {
                        const res = await gst.api('/hsn-codes/sync', { method: 'POST', body: JSON.stringify({}) });
                        gst.toast(`${res.message} Created: ${res.data.created}, Updated: ${res.data.updated}`, 'info');
                        await this.reloadCodes();
                    } catch (e) {
                        gst.toast(e.message || 'Unable to sync catalog', 'error');
                    }
                    this.syncRunning = false;
                },
                async reloadCodes() {
                    try {
                        const res = await gst.api('/hsn-codes');
                        this.codes = res.data || [];
                        await this.fetchCatalog();
                    } catch (e) {}
                },
                openModal(c = null) {
                    this.editing = c;
                    this.form = c ? { ...c } : { hsn_code: '', description: '', category: '', gst_rate: '', effective_date: '', status: 'active' };
                    this.showModal = true;
                },
                async save() {
                    this.saving = true;
                    try {
                        const id = this.editing?.id || this.editing?._id;
                        const res = await gst.api(id ? `/hsn-codes/${id}` : '/hsn-codes', { method: id ? 'PUT' : 'POST', body: JSON.stringify(this.form) });
                        if (id) { const idx = this.codes.findIndex(x => (x.id||x._id) === id); if (idx >= 0) this.codes[idx] = res.data; } else { this.codes.push(res.data); }
                        this.showModal = false;
                        gst.toast(res.message);
                    } catch (e) { gst.toast(e.message || 'Error', 'error'); }
                    this.saving = false;
                },
                async remove(c) {
                    if (!confirm('Delete this HSN code?')) return;
                    try { await gst.api(`/hsn-codes/${c.id || c._id}`, { method: 'DELETE' }); this.codes = this.codes.filter(x => (x.id||x._id) !== (c.id||c._id)); gst.toast('Deleted'); } catch (e) { gst.toast(e.message, 'error'); }
                },
                init() {
                    this.fetchCatalog();
                },
            };
        }
    </script>
</x-app-layout>
