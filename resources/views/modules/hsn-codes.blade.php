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
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="relative max-w-xl flex-1">
                <div class="search-bar">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" @input="onSmartInput()" @keydown="onSmartKeydown($event)" @focus="smartOpen = smartResults.length > 0 || noResultSuggestions.length > 0" placeholder="Smart search: paneer, ghee, laptop, mobile charger..." class="flex-1">
                    <span class="text-[10px] text-slate-400" x-show="smartLoading">Searching...</span>
                </div>
                <div class="mt-2 flex flex-wrap gap-2" x-show="recentSearches.length > 0 && !smartOpen">
                    <template x-for="term in recentSearches" :key="term">
                        <button type="button" class="rounded-full border px-3 py-1 text-xs text-slate-600 hover:bg-slate-50" style="border-color: hsl(var(--gst-border));" @click="useRecent(term)" x-text="term"></button>
                    </template>
                </div>
                <div x-show="smartOpen" x-transition class="absolute z-30 mt-2 max-h-96 w-full overflow-auto rounded-2xl border bg-white shadow-xl" style="border-color: hsl(var(--gst-border));">
                    <template x-if="smartResults.length > 0">
                        <div class="py-2">
                            <template x-for="(item, index) in smartResults" :key="item.hsn_code + '-' + index">
                                <button type="button" class="flex w-full items-start justify-between gap-3 px-4 py-3 text-left hover:bg-slate-50" :class="index === activeResultIndex ? 'bg-violet-50' : ''" @mouseenter="activeResultIndex = index" @click="chooseResult(item)">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-xs text-slate-600" x-text="item.hsn_code"></span>
                                            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700" x-text="item.category || 'General'"></span>
                                        </div>
                                        <p class="truncate text-sm font-semibold text-slate-900" x-html="highlight(item.name)"></p>
                                        <p class="truncate text-xs text-slate-500" x-text="item.matched_alias || item.official_description"></p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <span class="badge badge-info" x-text="(item.gst_rate ?? 0) + '%'"></span>
                                        <p class="mt-1 text-[11px] font-semibold" :class="confidenceClass(item.confidence)" x-text="'Confidence ' + item.confidence + '%'"></p>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </template>
                    <template x-if="smartResults.length === 0 && !smartLoading">
                        <div class="p-4">
                            <p class="text-sm font-semibold text-slate-800">No exact results found.</p>
                            <p class="mt-1 text-xs text-slate-500">Try these suggestions:</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <template x-for="suggestion in noResultSuggestions" :key="suggestion">
                                    <button type="button" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" @click="useRecent(suggestion)" x-text="suggestion"></button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            @if(auth()->user()->isAdmin())
            <div class="flex gap-2">
                <input type="file" class="hidden" x-ref="importFile" accept=".csv,.xls,.xlsx" @change="importCatalog()">
                <button @click="$refs.importFile.click()" class="btn btn-secondary">Import CSV/XLS</button>
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

        @if(auth()->user()->isAdmin())
        <div class="mb-6 grid gap-4 lg:grid-cols-3" x-show="analyticsReady">
            <div class="card-lg">
                <h3 class="panel-title !mt-0">Most searched terms</h3>
                <div class="mt-3 space-y-2 text-sm">
                    <template x-for="row in analytics.most_searched" :key="row.query">
                        <div class="flex items-center justify-between"><span x-text="row.query"></span><span class="font-semibold text-slate-900" x-text="row.count"></span></div>
                    </template>
                </div>
            </div>
            <div class="card-lg">
                <h3 class="panel-title !mt-0">Failed searches</h3>
                <div class="mt-3 space-y-2 text-sm">
                    <template x-for="row in analytics.failed_searches" :key="row.query">
                        <div class="flex items-center justify-between"><span x-text="row.query"></span><span class="font-semibold text-red-600" x-text="row.count"></span></div>
                    </template>
                </div>
            </div>
            <div class="card-lg">
                <h3 class="panel-title !mt-0">Low confidence reviews</h3>
                <div class="mt-3 space-y-2 text-sm">
                    <template x-for="row in analytics.low_confidence" :key="row.query + row.created_at">
                        <div class="rounded-xl border px-3 py-2" style="border-color: hsl(var(--gst-border));">
                            <p class="font-semibold text-slate-900" x-text="row.query"></p>
                            <p class="text-xs text-slate-500" x-text="'Confidence ' + row.confidence + '% • Results ' + row.results_count"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        @endif

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
                smartResults: [],
                smartOpen: false,
                smartLoading: false,
                activeResultIndex: -1,
                noResultSuggestions: [],
                recentSearches: [],
                smartSearchTimer: null,
                analyticsReady: false,
                analytics: {
                    most_searched: [],
                    failed_searches: [],
                    low_confidence: [],
                },
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
                onSmartInput() {
                    clearTimeout(this.smartSearchTimer);
                    if ((this.search || '').trim().length < 2) {
                        this.smartResults = [];
                        this.noResultSuggestions = [];
                        this.smartOpen = false;
                        this.fetchCatalog();
                        return;
                    }
                    this.smartSearchTimer = setTimeout(() => this.performSmartSearch(), 300);
                    this.fetchCatalog();
                },
                async performSmartSearch() {
                    this.smartLoading = true;
                    this.smartOpen = true;
                    this.activeResultIndex = -1;
                    try {
                        const q = encodeURIComponent(this.search.trim());
                        const res = await gst.api(`/hsn/search?q=${q}&limit=8`);
                        this.smartResults = res.results || [];
                        this.noResultSuggestions = res?.meta?.suggestions || [];
                    } catch (e) {
                        this.smartResults = [];
                        this.noResultSuggestions = [];
                    }
                    this.smartLoading = false;
                },
                async chooseResult(item) {
                    this.search = item.name || this.search;
                    this.smartOpen = false;
                    this.rememberSearch(this.search);
                    try {
                        await gst.api('/hsn/search/select', {
                            method: 'POST',
                            body: JSON.stringify({ query: this.search, hsn_code: item.hsn_code }),
                        });
                    } catch (e) {}
                },
                onSmartKeydown(e) {
                    if (!this.smartOpen || this.smartResults.length === 0) return;
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        this.activeResultIndex = Math.min(this.smartResults.length - 1, this.activeResultIndex + 1);
                        return;
                    }
                    if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        this.activeResultIndex = Math.max(0, this.activeResultIndex - 1);
                        return;
                    }
                    if (e.key === 'Enter' && this.activeResultIndex >= 0) {
                        e.preventDefault();
                        this.chooseResult(this.smartResults[this.activeResultIndex]);
                        return;
                    }
                    if (e.key === 'Escape') {
                        this.smartOpen = false;
                    }
                },
                highlight(text) {
                    const value = String(text || '');
                    const q = (this.search || '').trim();
                    if (!q) return value;
                    const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    return value.replace(new RegExp(`(${escaped})`, 'ig'), '<mark class="rounded bg-yellow-100 px-0.5">$1</mark>');
                },
                confidenceClass(confidence) {
                    const score = Number(confidence || 0);
                    if (score >= 85) return 'text-emerald-600';
                    if (score >= 65) return 'text-amber-600';
                    return 'text-rose-600';
                },
                useRecent(term) {
                    this.search = term;
                    this.onSmartInput();
                },
                rememberSearch(term) {
                    const value = String(term || '').trim();
                    if (value.length < 2) return;
                    const current = this.recentSearches.filter(x => x !== value);
                    this.recentSearches = [value, ...current].slice(0, 6);
                    localStorage.setItem('gst:hsn:recent-searches', JSON.stringify(this.recentSearches));
                },
                loadRecentSearches() {
                    try {
                        const payload = JSON.parse(localStorage.getItem('gst:hsn:recent-searches') || '[]');
                        this.recentSearches = Array.isArray(payload) ? payload : [];
                    } catch (e) {
                        this.recentSearches = [];
                    }
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
                async importCatalog() {
                    const file = this.$refs.importFile?.files?.[0];
                    if (!file) return;
                    const body = new FormData();
                    body.append('file', file);
                    try {
                        const res = await fetch('/api/hsn/import', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': gst.csrfToken,
                            },
                            credentials: 'same-origin',
                            body,
                        });
                        const payload = await res.json();
                        if (!res.ok) throw new Error(payload?.message || 'Import failed');
                        const info = payload?.data || {};
                        gst.toast(`Import done. Created ${info.created || 0}, Updated ${info.updated || 0}`, 'info');
                    } catch (e) {
                        gst.toast(e.message || 'Import failed', 'error');
                    }
                    this.$refs.importFile.value = '';
                },
                async loadAnalytics() {
                    @if(auth()->user()->isAdmin())
                    try {
                        const res = await gst.api('/hsn/analytics');
                        this.analytics = res.data || this.analytics;
                    } catch (e) {
                        this.analytics = { most_searched: [], failed_searches: [], low_confidence: [] };
                    }
                    this.analyticsReady = true;
                    @endif
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
                    this.loadRecentSearches();
                    this.fetchCatalog();
                    this.loadAnalytics();
                },
            };
        }
    </script>
</x-app-layout>
