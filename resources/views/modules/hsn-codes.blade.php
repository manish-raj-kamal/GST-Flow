<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Classification</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">HSN Codes</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="hsnPage()">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="search-bar max-w-sm flex-1">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" placeholder="Search HSN codes..." class="flex-1">
            </div>
            @if(auth()->user()->isAdmin())
            <button @click="openModal()" class="btn btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add HSN Code
            </button>
            @endif
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
                            <div class="form-group"><label class="form-label">HSN Code *</label><input x-model="form.hsn_code" class="form-input font-mono" maxlength="8" required></div>
                            <div class="form-group"><label class="form-label">GST Rate (%) *</label><input type="number" step="0.01" x-model="form.gst_rate" class="form-input" required></div>
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
                get filtered() {
                    const q = this.search.toLowerCase();
                    return this.codes.filter(c => !q || (c.hsn_code||'').toLowerCase().includes(q) || (c.description||'').toLowerCase().includes(q));
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
            };
        }
    </script>
</x-app-layout>
