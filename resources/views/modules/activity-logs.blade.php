<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Audit</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Activity Logs</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="{ search: '', logs: @js($logs) }">
        <div class="mb-6">
            <div class="search-bar max-w-sm">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" placeholder="Filter logs..." class="flex-1">
            </div>
        </div>

        <div class="card-lg">
            <div class="space-y-3">
                <template x-for="log in logs.filter(l => !search || (l.action_type||'').toLowerCase().includes(search.toLowerCase()) || (l.ip_address||'').includes(search))" :key="log.id || log._id">
                    <div class="flex items-start gap-4 rounded-xl border p-4 transition-colors hover:bg-slate-50" style="border-color: hsl(var(--gst-border));">
                        <div class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-amber-100">
                            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-semibold text-slate-900 text-sm" x-text="(log.action_type||'').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())"></div>
                                <div class="text-xs text-slate-400 whitespace-nowrap" x-text="gst.formatDate(log.created_at)"></div>
                            </div>
                            <div class="mt-1 flex flex-wrap gap-2">
                                <span class="badge badge-info" x-show="log.user_id" x-text="'User: ' + (log.user_id||'').substring(0,8) + '…'"></span>
                                <span class="badge badge-inactive" x-show="log.ip_address" x-text="log.ip_address"></span>
                            </div>
                            <div class="mt-2 text-xs text-slate-500" x-show="log.affected_record" x-text="'Record: ' + JSON.stringify(log.affected_record)"></div>
                        </div>
                    </div>
                </template>
            </div>

            <template x-if="logs.length === 0">
                <div class="empty-state py-12">
                    <div class="empty-icon"><svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <h3>No activity yet</h3>
                    <p>System actions will be logged here as you use the platform.</p>
                </div>
            </template>
        </div>
    </div>
</x-app-layout>
