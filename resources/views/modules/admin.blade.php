<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Administration</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Admin Panel</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="adminPage()">
        {{-- Stats --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="metric-card"><div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Total Users</div><div class="mt-2 text-3xl font-bold text-slate-900">{{ $users->count() }}</div></div>
            <div class="metric-card"><div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Active Users</div><div class="mt-2 text-3xl font-bold text-emerald-600">{{ $users->where('is_active', true)->count() }}</div></div>
            <div class="metric-card"><div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Business Profiles</div><div class="mt-2 text-3xl font-bold text-amber-600">{{ $totalProfiles }}</div></div>
            <div class="metric-card"><div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Total Invoices</div><div class="mt-2 text-3xl font-bold text-sky-600">{{ $totalInvoices }}</div></div>
        </div>

        {{-- User Management --}}
        <div class="card-lg">
            <div class="flex items-center justify-between mb-4">
                <h3 class="panel-title">User Management</h3>
                <div class="search-bar max-w-xs">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" placeholder="Search users..." class="flex-1">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                        <template x-for="u in filtered" :key="u.id || u._id">
                            <tr>
                                <td class="font-medium text-slate-900" x-text="u.name"></td>
                                <td class="text-sm text-slate-500" x-text="u.email"></td>
                                <td>
                                    <select class="form-select text-xs py-1 px-2 max-w-[140px]" :value="u.role" @change="changeRole(u, $event.target.value)">
                                        <option value="business_user">Business User</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </td>
                                <td>
                                    <span class="badge cursor-pointer" :class="u.is_active ? 'badge-active' : 'badge-danger'" @click="toggleStatus(u)" x-text="u.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td class="text-sm text-slate-500" x-text="gst.formatDate(u.created_at)"></td>
                                <td class="text-right">
                                    <button @click="toggleStatus(u)" class="btn btn-ghost btn-xs" x-text="u.is_active ? 'Deactivate' : 'Activate'"></button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function adminPage() {
            return {
                users: @json($users),
                search: '',
                get filtered() {
                    const q = this.search.toLowerCase();
                    return this.users.filter(u => !q || (u.name||'').toLowerCase().includes(q) || (u.email||'').toLowerCase().includes(q));
                },
                async toggleStatus(u) {
                    const id = u.id || u._id;
                    try {
                        const res = await gst.api(`/admin/users/${id}/toggle-status`, { method: 'PUT' });
                        const idx = this.users.findIndex(x => (x.id||x._id) === id);
                        if (idx >= 0) this.users[idx] = res.data;
                        gst.toast(res.message);
                    } catch (e) { gst.toast(e.message, 'error'); }
                },
                async changeRole(u, role) {
                    const id = u.id || u._id;
                    try {
                        const res = await gst.api(`/admin/users/${id}/role`, { method: 'PUT', body: JSON.stringify({ role }) });
                        const idx = this.users.findIndex(x => (x.id||x._id) === id);
                        if (idx >= 0) this.users[idx] = res.data;
                        gst.toast(res.message);
                    } catch (e) { gst.toast(e.message, 'error'); }
                },
            };
        }
    </script>
</x-app-layout>
