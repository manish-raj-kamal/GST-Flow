<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Identity</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Business Profiles</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="businessProfilesPage()">
        {{-- Action bar --}}
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="search-bar max-w-sm flex-1">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" placeholder="Search profiles..." class="flex-1">
            </div>
            <button @click="openModal()" class="btn btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Profile
            </button>
        </div>

        {{-- Profiles Grid --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <template x-for="profile in filtered" :key="profile.id || profile._id">
                <div class="card group cursor-pointer" @click="openModal(profile)">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-semibold text-slate-900" x-text="profile.business_name"></h3>
                            <p class="mt-0.5 text-xs text-slate-500" x-text="profile.legal_name"></p>
                        </div>
                        <span class="badge badge-active" x-show="profile.gstin">GST Registered</span>
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <div class="flex justify-between"><span class="text-slate-400">GSTIN</span><span class="font-mono font-medium text-slate-900" x-text="profile.gstin || '—'"></span></div>
                        <div class="flex justify-between"><span class="text-slate-400">PAN</span><span class="font-medium" x-text="profile.pan || '—'"></span></div>
                        <div class="flex justify-between"><span class="text-slate-400">State</span><span x-text="profile.state || '—'"></span></div>
                        <div class="flex justify-between"><span class="text-slate-400">Type</span><span class="capitalize" x-text="profile.business_type || '—'"></span></div>
                    </div>
                    <div class="mt-4 flex items-center gap-2 border-t pt-3" style="border-color: hsl(var(--gst-border))">
                        <span class="text-xs text-slate-400" x-text="profile.email"></span>
                        <span class="text-slate-300">·</span>
                        <span class="text-xs text-slate-400" x-text="profile.phone"></span>
                    </div>
                </div>
            </template>
        </div>

        <template x-if="filtered.length === 0">
            <div class="empty-state">
                <div class="empty-icon"><svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div>
                <h3>No business profiles</h3>
                <p>Create your first business profile to start managing GST compliance.</p>
                <button @click="openModal()" class="btn btn-primary mt-4">Create Profile</button>
            </div>
        </template>

        {{-- Modal --}}
        <template x-if="showModal">
            <div class="modal-backdrop" @click.self="showModal = false">
                <div class="modal-panel-lg max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="modal-title" x-text="editing ? 'Edit Profile' : 'New Business Profile'"></h2>
                        <button @click="showModal = false" class="btn-ghost rounded-lg p-1"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form @submit.prevent="save()" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="form-group"><label class="form-label">Business Name *</label><input x-model="form.business_name" class="form-input" required></div>
                            <div class="form-group"><label class="form-label">Legal Name</label><input x-model="form.legal_name" class="form-input"></div>
                            <div class="form-group"><label class="form-label">GSTIN *</label><input x-model="form.gstin" class="form-input font-mono" maxlength="15" required placeholder="e.g. 27ABCDE1234F1Z5"></div>
                            <div class="form-group"><label class="form-label">Business Type</label>
                                <select x-model="form.business_type" class="form-select">
                                    <option value="">Select type</option>
                                    <option value="proprietorship">Proprietorship</option>
                                    <option value="partnership">Partnership</option>
                                    <option value="company">Company</option>
                                    <option value="llp">LLP</option>
                                    <option value="trust">Trust</option>
                                </select>
                            </div>
                            <div class="form-group sm:col-span-2"><label class="form-label">Address</label><input x-model="form.address" class="form-input"></div>
                            <div class="form-group"><label class="form-label">City</label><input x-model="form.city" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Pincode</label><input x-model="form.pincode" class="form-input" maxlength="6"></div>
                            <div class="form-group"><label class="form-label">Email</label><input type="email" x-model="form.email" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Phone</label><input x-model="form.phone" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Registration Date</label><input type="date" x-model="form.registration_date" class="form-input"></div>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false" class="btn btn-secondary">Cancel</button>
                            <template x-if="editing">
                                <button type="button" @click="remove()" class="btn btn-danger btn-sm">Delete</button>
                            </template>
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
        function businessProfilesPage() {
            return {
                profiles: @json($profiles),
                search: '',
                showModal: false,
                editing: null,
                saving: false,
                form: {},
                get filtered() {
                    const q = this.search.toLowerCase();
                    return this.profiles.filter(p =>
                        !q || (p.business_name||'').toLowerCase().includes(q) || (p.gstin||'').toLowerCase().includes(q)
                    );
                },
                openModal(profile = null) {
                    this.editing = profile;
                    this.form = profile ? { ...profile } : { business_name: '', legal_name: '', gstin: '', address: '', city: '', pincode: '', email: '', phone: '', business_type: '', registration_date: '' };
                    this.showModal = true;
                },
                async save() {
                    this.saving = true;
                    try {
                        const id = this.editing?.id || this.editing?._id;
                        const url = id ? `/business-profiles/${id}` : '/business-profiles';
                        const method = id ? 'PUT' : 'POST';
                        const res = await gst.api(url, { method, body: JSON.stringify(this.form) });
                        if (id) {
                            const idx = this.profiles.findIndex(p => (p.id||p._id) === id);
                            if (idx >= 0) this.profiles[idx] = res.data;
                        } else {
                            this.profiles.push(res.data);
                        }
                        this.showModal = false;
                        gst.toast(res.message);
                    } catch (e) { gst.toast(e.message || 'Error', 'error'); }
                    this.saving = false;
                },
                async remove() {
                    if (!confirm('Delete this business profile and all associated data?')) return;
                    const id = this.editing.id || this.editing._id;
                    try {
                        const res = await gst.api(`/business-profiles/${id}`, { method: 'DELETE' });
                        this.profiles = this.profiles.filter(p => (p.id||p._id) !== id);
                        this.showModal = false;
                        gst.toast(res.message);
                    } catch (e) { gst.toast(e.message || 'Error', 'error'); }
                },
            };
        }
    </script>
</x-app-layout>
