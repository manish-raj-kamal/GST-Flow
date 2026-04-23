<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Reference</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">System Documentation</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8">
        <div class="mx-auto max-w-4xl space-y-6">

            {{-- API Routes --}}
            <div class="card-lg" x-data="{ open: true }">
                <button @click="open = !open" class="flex w-full items-center justify-between text-left">
                    <h3 class="panel-title">API Routes Reference</h3>
                    <svg class="h-5 w-5 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition class="mt-4 overflow-x-auto">
                    <table class="data-table text-xs">
                        <thead><tr><th>Method</th><th>Endpoint</th><th>Description</th><th>Auth</th></tr></thead>
                        <tbody>
                            @php
                            $routes = [
                                ['POST', '/api/auth/register', 'Register new user', 'No'],
                                ['POST', '/api/auth/login', 'Login and get token', 'No'],
                                ['POST', '/api/auth/logout', 'Logout', 'Yes'],
                                ['GET', '/api/auth/me', 'Current user profile', 'Yes'],
                                ['PUT', '/api/auth/profile', 'Update profile', 'Yes'],
                                ['PUT', '/api/auth/password', 'Change password', 'Yes'],
                                ['GET', '/api/gstin/validate', 'Validate GSTIN', 'Yes'],
                                ['GET', '/api/state-codes', 'List state codes', 'Yes'],
                                ['CRUD', '/api/business-profiles', 'Business profile management', 'Yes'],
                                ['CRUD', '/api/customers', 'Customer management', 'Yes'],
                                ['CRUD', '/api/products', 'Product catalog', 'Yes'],
                                ['CRUD', '/api/hsn-codes', 'HSN code mapping', 'Yes (Admin)'],
                                ['CRUD', '/api/tax-slabs', 'Tax slab configuration', 'Yes (Admin)'],
                                ['CRUD', '/api/invoices', 'Invoice management', 'Yes'],
                                ['POST', '/api/invoices/{id}/duplicate', 'Duplicate invoice', 'Yes'],
                                ['GET', '/api/invoices/{id}/versions', 'Version history', 'Yes'],
                                ['GET', '/api/dashboard', 'Dashboard analytics', 'Yes'],
                                ['GET', '/api/reports/sales', 'Sales report', 'Yes'],
                                ['GET', '/api/reports/purchases', 'Purchase report', 'Yes'],
                                ['GET', '/api/reports/monthly-gst-summary', 'Monthly GST summary', 'Yes'],
                                ['GET', '/api/export/invoices/{id}/pdf', 'PDF invoice download', 'Yes'],
                                ['GET', '/api/export/sales.csv', 'Sales CSV export', 'Yes'],
                                ['GET', '/api/export/tax-summary.csv', 'Tax summary CSV', 'Yes'],
                                ['GET', '/api/export/monthly-summary.xls', 'Monthly summary Excel', 'Yes'],
                                ['GET', '/api/gstr-summary', 'GSTR-style summary', 'Yes'],
                                ['GET', '/api/activity-logs', 'System activity logs', 'Yes'],
                                ['GET', '/api/admin/users', 'List all users', 'Admin'],
                                ['PUT', '/api/admin/users/{id}/toggle-status', 'Toggle user status', 'Admin'],
                                ['PUT', '/api/admin/users/{id}/role', 'Assign role', 'Admin'],
                                ['GET', '/api/admin/analytics', 'System analytics', 'Admin'],
                            ];
                            @endphp
                            @foreach($routes as $route)
                            <tr>
                                <td><span class="badge {{ $route[0] === 'GET' ? 'badge-active' : ($route[0] === 'POST' ? 'badge-info' : ($route[0] === 'CRUD' ? 'badge-warning' : 'badge-inactive')) }} text-[9px]">{{ $route[0] }}</span></td>
                                <td class="font-mono text-[11px]">{{ $route[1] }}</td>
                                <td>{{ $route[2] }}</td>
                                <td><span class="text-[10px] {{ $route[3] === 'No' ? 'text-emerald-600' : 'text-amber-600' }}">{{ $route[3] }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Database Schema --}}
            <div class="card-lg" x-data="{ open: false }">
                <button @click="open = !open" class="flex w-full items-center justify-between text-left">
                    <h3 class="panel-title">Database Collections (MongoDB)</h3>
                    <svg class="h-5 w-5 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach(['users', 'roles', 'permissions', 'business_profiles', 'customers', 'products', 'hsn_codes', 'tax_slabs', 'invoices', 'reports', 'activity_logs', 'invoice_versions', 'state_codes'] as $coll)
                    <div class="rounded-xl border bg-slate-50 p-3" style="border-color: hsl(var(--gst-border));">
                        <div class="font-mono text-sm font-semibold text-slate-700">{{ $coll }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Tax Calculation Logic --}}
            <div class="card-lg" x-data="{ open: false }">
                <button @click="open = !open" class="flex w-full items-center justify-between text-left">
                    <h3 class="panel-title">Tax Calculation Logic</h3>
                    <svg class="h-5 w-5 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition class="mt-4 space-y-4 text-sm text-slate-600">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <h4 class="font-semibold text-slate-800 mb-2">Intrastate (Same State)</h4>
                        <code class="text-xs">CGST = taxable_value × (gst_rate / 2) / 100</code><br>
                        <code class="text-xs">SGST = taxable_value × (gst_rate / 2) / 100</code><br>
                        <code class="text-xs">IGST = 0</code>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <h4 class="font-semibold text-slate-800 mb-2">Interstate (Different State)</h4>
                        <code class="text-xs">CGST = 0</code><br>
                        <code class="text-xs">SGST = 0</code><br>
                        <code class="text-xs">IGST = taxable_value × gst_rate / 100</code>
                    </div>
                    <div class="rounded-xl bg-amber-50 p-4">
                        <h4 class="font-semibold text-amber-800 mb-2">Total Computation</h4>
                        <code class="text-xs">total_tax = CGST + SGST + IGST</code><br>
                        <code class="text-xs">invoice_total = taxable_value + total_tax</code>
                    </div>
                </div>
            </div>

            {{-- Module Overview --}}
            <div class="card-lg" x-data="{ open: false }">
                <button @click="open = !open" class="flex w-full items-center justify-between text-left">
                    <h3 class="panel-title">System Capabilities</h3>
                    <svg class="h-5 w-5 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach([
                        'GSTIN validation with checksum',
                        'Business profile management',
                        'Customer management with interstate detection',
                        'Product catalog with HSN mapping',
                        'Automatic GST calculation engine',
                        'Invoice creation and versioning',
                        'PDF invoice export',
                        'Dashboard analytics',
                        'Sales and purchase reports',
                        'Monthly GST summaries',
                        'GSTR-1 and GSTR-3B style datasets',
                        'Admin controls and user management',
                        'Tax slab management',
                        'State code auto-detection',
                        'Activity logging and audit trail',
                        'Invoice version history',
                        'Search and filters across all modules',
                        'CSV and Excel data exports',
                        'Responsive mobile-friendly UI',
                        'Role-based access control',
                    ] as $capability)
                    <div class="flex items-center gap-2 rounded-lg p-2">
                        <svg class="h-4 w-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-sm text-slate-700">{{ $capability }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
