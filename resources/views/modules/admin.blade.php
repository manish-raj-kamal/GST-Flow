<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="module-kicker">Administration</p>
            <div class="page-title-row">
                <h1 class="module-title">Admin Panel</h1>
                <x-info-tip placement="bottom" text="Admins can review users, change roles, toggle account status, and monitor platform-level totals from this page." />
            </div>
            <p class="module-subtitle hidden md:block">Manage user access and monitor account activity across the platform.</p>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="adminPage()">
        @if (auth()->user()->isTestAdmin())
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">
                You are viewing test-admin mode. The {{ $users->count() }} account{{ $users->count() === 1 ? '' : 's' }} shown here are test users only.
            </div>
        @endif

        {{-- Stats --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="metric-card"><div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Total Users</div><div class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">{{ $users->count() }}</div></div>
            <div class="metric-card"><div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Active Users</div><div class="mt-2 text-2xl font-bold text-emerald-600 sm:text-3xl">{{ $users->where('is_active', true)->count() }}</div></div>
            <div class="metric-card"><div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Business Profiles</div><div class="mt-2 text-2xl font-bold text-amber-600 sm:text-3xl">{{ $totalProfiles }}</div></div>
            <div class="metric-card"><div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Total Invoices</div><div class="mt-2 text-2xl font-bold text-sky-600 sm:text-3xl">{{ $totalInvoices }}</div></div>
        </div>

        {{-- User Management --}}
        <div class="card-lg">
            <div class="mb-4 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                <div class="flex items-center gap-2">
                    <h3 class="panel-title !mt-0">User Management</h3>
                    <x-info-tip text="Change roles carefully. Admin users can access management controls and platform-wide data." />
                </div>
                <div class="search-bar w-full max-w-xs">
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
                                    <span class="badge"
                                        :class="[
                                            u.is_active ? 'badge-active' : 'badge-danger',
                                            canToggleStatus(u) ? 'cursor-pointer' : 'cursor-not-allowed opacity-70'
                                        ]"
                                        @click="canToggleStatus(u) && toggleStatus(u)"
                                        :title="canToggleStatus(u) ? '' : 'You cannot deactivate your own admin account.'"
                                        x-text="u.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td class="text-sm text-slate-500" x-text="gst.formatDate(u.created_at)"></td>
                                <td class="text-right">
                                    <button @click="toggleStatus(u)" class="btn btn-ghost btn-xs" :disabled="!canToggleStatus(u)" :title="canToggleStatus(u) ? '' : 'You cannot deactivate your own admin account.'" x-text="u.is_active ? 'Deactivate' : 'Activate'"></button>
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
                currentUserId: @js((string) auth()->id()),
                currentUserRole: @js((string) auth()->user()->role),
                search: '',
                canToggleStatus(u) {
                    const userId = String(u.id || u._id || '');

                    return !(
                        userId === this.currentUserId
                        && ['admin', 'superadmin'].includes(this.currentUserRole)
                    );
                },
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
