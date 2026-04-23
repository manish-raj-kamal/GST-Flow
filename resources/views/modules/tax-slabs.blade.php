<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Configuration</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Tax Slabs</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="taxSlabsPage()">
        <div class="mb-6 flex items-center justify-between">
            <p class="text-sm text-slate-500">GST rate master table used across products and invoices.</p>
            @if(auth()->user()->isAdmin())
            <button @click="openModal()" class="btn btn-primary btn-sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add Slab
            </button>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            <template x-for="slab in slabs" :key="slab.id || slab._id">
                <div class="card text-center cursor-pointer" @click="openModal(slab)">
                    <div class="text-4xl font-extrabold" :class="{'text-emerald-500': slab.rate == 0, 'text-sky-500': slab.rate == 5, 'text-amber-500': slab.rate == 12, 'text-orange-500': slab.rate == 18, 'text-red-500': slab.rate == 28, 'text-slate-700': ![0,5,12,18,28].includes(Number(slab.rate))}" x-text="slab.rate + '%'"></div>
                    <div class="mt-2 text-sm font-semibold text-slate-700" x-text="slab.name"></div>
                    <div class="mt-1 text-xs text-slate-400" x-text="slab.effective_date ? 'Effective: ' + gst.formatDate(slab.effective_date) : ''"></div>
                    <span class="mt-2 badge" :class="slab.status === 'active' ? 'badge-active' : 'badge-inactive'" x-text="slab.status"></span>
                </div>
            </template>
        </div>

        <template x-if="slabs.length === 0">
            <div class="empty-state mt-8"><h3>No tax slabs defined</h3><p>Add GST rate slabs (0%, 5%, 12%, 18%, 28%) to use in products and invoices.</p></div>
        </template>

        @if(auth()->user()->isAdmin())
        <template x-if="showModal">
            <div class="modal-backdrop" @click.self="showModal = false">
                <div class="modal-panel">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="modal-title" x-text="editing ? 'Edit Slab' : 'New Tax Slab'"></h2>
                        <button @click="showModal = false" class="btn-ghost rounded-lg p-1"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form @submit.prevent="save()" class="space-y-4">
                        <div class="form-group"><label class="form-label">Name *</label><input x-model="form.name" class="form-input" required placeholder="e.g. GST 18%"></div>
                        <div class="form-group"><label class="form-label">Rate (%) *</label><input type="number" step="0.01" x-model="form.rate" class="form-input" required></div>
                        <div class="form-group"><label class="form-label">Effective Date</label><input type="date" x-model="form.effective_date" class="form-input"></div>
                        <div class="form-group"><label class="form-label">Status</label><select x-model="form.status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false" class="btn btn-secondary">Cancel</button>
                            <template x-if="editing"><button type="button" @click="remove()" class="btn btn-danger btn-sm">Delete</button></template>
                            <button type="submit" class="btn btn-primary" :disabled="saving"><span x-text="editing ? 'Update' : 'Create'"></span></button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
        @endif
    </div>

    <script>
        function taxSlabsPage() {
            return {
                slabs: @json($slabs),
                showModal: false, editing: null, saving: false, form: {},
                openModal(s = null) { this.editing = s; this.form = s ? { ...s } : { name: '', rate: '', effective_date: '', status: 'active' }; this.showModal = true; },
                async save() {
                    this.saving = true;
                    try {
                        const id = this.editing?.id || this.editing?._id;
                        const res = await gst.api(id ? `/tax-slabs/${id}` : '/tax-slabs', { method: id ? 'PUT' : 'POST', body: JSON.stringify(this.form) });
                        if (id) { const idx = this.slabs.findIndex(x => (x.id||x._id) === id); if (idx >= 0) this.slabs[idx] = res.data; } else { this.slabs.push(res.data); }
                        this.showModal = false; gst.toast(res.message);
                    } catch (e) { gst.toast(e.message, 'error'); }
                    this.saving = false;
                },
                async remove() {
                    if (!confirm('Delete this tax slab?')) return;
                    try { await gst.api(`/tax-slabs/${this.editing.id || this.editing._id}`, { method: 'DELETE' }); this.slabs = this.slabs.filter(x => (x.id||x._id) !== (this.editing.id||this.editing._id)); this.showModal = false; gst.toast('Deleted'); } catch (e) { gst.toast(e.message, 'error'); }
                },
            };
        }
    </script>
</x-app-layout>
