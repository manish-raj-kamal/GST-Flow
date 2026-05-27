<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="module-kicker">Audit</p>
            <div class="page-title-row">
                <h1 class="module-title">Activity Logs</h1>
                <x-info-tip placement="bottom" text="Activity logs show recent system actions, related users, IP address, and affected records for audit review." />
            </div>
            <p class="module-subtitle hidden md:block">Filter recorded actions to understand recent changes and operational history.</p>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="activityLogsPage()">
        <div class="mb-6">
            <div class="search-bar max-w-sm">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" placeholder="Filter logs..." class="flex-1">
            </div>
        </div>

        <div x-show="loading" class="mb-6 text-center py-6 text-slate-400">
            <div class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-amber-500 border-t-transparent"></div>
            <p class="mt-2 text-sm">Loading activity logs...</p>
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

            <template x-if="!loading && logs.length === 0">
                <div class="empty-state py-12">
                    <div class="empty-icon"><svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <h3>No activity yet</h3>
                    <p>System actions will be logged here as you use the platform.</p>
                </div>
            </template>
        </div>
    </div>

    <script>
        function activityLogsPage() {
            const cacheKey = 'activity-logs:index';
            return {
                search: '',
                logs: @js($logs),
                loading: false,
                async loadLogs() {
                    if (this.logs.length > 0) {
                        gst.writeCache(cacheKey, this.logs);
                        return;
                    }
                    const cached = gst.readCache(cacheKey, 120000);
                    if (Array.isArray(cached) && cached.length > 0) {
                        this.logs = cached;
                    }
                    this.loading = true;
                    try {
                        const res = await gst.api('/activity-logs');
                        this.logs = res?.data || [];
                        gst.writeCache(cacheKey, this.logs);
                    } catch (e) {
                        if (!this.logs.length) gst.toast(e.message || 'Unable to load activity logs', 'error');
                    }
                    this.loading = false;
                },
                init() {
                    this.loadLogs();
                },
            };
        }
    </script>
</x-app-layout>
