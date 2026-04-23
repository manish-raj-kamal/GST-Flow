<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Validation</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">GSTIN Validator</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8" x-data="gstinValidator()">
        <div class="mx-auto max-w-2xl">
            <div class="card-lg">
                <h3 class="panel-title mb-2">Validate a GSTIN</h3>
                <p class="text-sm text-slate-500 mb-6">Enter a 15-character GSTIN to validate its format, extract state code, PAN, and verify checksum.</p>

                <div class="flex gap-3">
                    <input type="text" x-model="gstin" class="form-input flex-1 font-mono text-lg tracking-wider uppercase" maxlength="15" placeholder="e.g. 27ABCDE1234F1Z5" @keydown.enter="validate()">
                    <button @click="validate()" class="btn btn-primary" :disabled="loading || gstin.length !== 15">
                        <span x-show="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        Validate
                    </button>
                </div>

                {{-- Example --}}
                <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm">
                    <div class="font-semibold text-slate-700 mb-2">GSTIN Structure</div>
                    <div class="font-mono text-lg tracking-wider text-slate-600">
                        <span class="text-sky-600 font-bold">27</span><span class="text-purple-600 font-bold">ABCDE1234F</span><span class="text-amber-600 font-bold">1</span><span class="text-slate-400 font-bold">Z</span><span class="text-emerald-600 font-bold">5</span>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-1 text-xs text-slate-500">
                        <div><span class="text-sky-600 font-semibold">27</span> — State Code (Maharashtra)</div>
                        <div><span class="text-purple-600 font-semibold">ABCDE1234F</span> — PAN</div>
                        <div><span class="text-amber-600 font-semibold">1</span> — Entity Number</div>
                        <div><span class="text-slate-400 font-semibold">Z</span> — Default</div>
                        <div><span class="text-emerald-600 font-semibold">5</span> — Checksum</div>
                    </div>
                </div>
            </div>

            {{-- Result --}}
            <template x-if="result">
                <div class="mt-6 card-lg" :class="result.is_valid ? 'border-emerald-200 bg-emerald-50/30' : 'border-red-200 bg-red-50/30'">
                    <div class="flex items-center gap-3 mb-4">
                        <template x-if="result.is_valid">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100">
                                <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </template>
                        <template x-if="!result.is_valid">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                                <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                        </template>
                        <div>
                            <div class="text-lg font-bold" :class="result.is_valid ? 'text-emerald-800' : 'text-red-800'" x-text="result.is_valid ? 'Valid GSTIN' : 'Invalid GSTIN'"></div>
                            <div class="text-sm" :class="result.is_valid ? 'text-emerald-600' : 'text-red-600'" x-text="result.gstin"></div>
                        </div>
                    </div>

                    <template x-if="result.is_valid">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl bg-white p-4 border" style="border-color: hsl(var(--gst-border));"><div class="text-xs text-slate-400 mb-1">State</div><div class="font-semibold" x-text="result.state_name + ' (' + result.state_code + ')'"></div></div>
                            <div class="rounded-xl bg-white p-4 border" style="border-color: hsl(var(--gst-border));"><div class="text-xs text-slate-400 mb-1">PAN</div><div class="font-mono font-semibold" x-text="result.parts?.pan || '—'"></div></div>
                            <div class="rounded-xl bg-white p-4 border" style="border-color: hsl(var(--gst-border));"><div class="text-xs text-slate-400 mb-1">Entity Number</div><div class="font-semibold" x-text="result.parts?.entity_number || '—'"></div></div>
                            <div class="rounded-xl bg-white p-4 border" style="border-color: hsl(var(--gst-border));"><div class="text-xs text-slate-400 mb-1">Checksum</div><div class="font-semibold" x-text="result.parts?.checksum || '—'"></div></div>
                            <div class="rounded-xl bg-white p-4 border" style="border-color: hsl(var(--gst-border));"><div class="text-xs text-slate-400 mb-1">Format Valid</div><div class="font-semibold text-emerald-600" x-text="result.format_valid ? 'Yes' : 'No'"></div></div>
                            <div class="rounded-xl bg-white p-4 border" style="border-color: hsl(var(--gst-border));"><div class="text-xs text-slate-400 mb-1">Checksum Valid</div><div class="font-semibold text-emerald-600" x-text="result.checksum_valid ? 'Yes' : 'No'"></div></div>
                        </div>
                    </template>

                    <template x-if="!result.is_valid && result.errors">
                        <div class="mt-2 space-y-1">
                            <template x-for="err in result.errors"><div class="text-sm text-red-600" x-text="'• ' + err"></div></template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <script>
        function gstinValidator() {
            return {
                gstin: '',
                loading: false,
                result: null,
                async validate() {
                    if (this.gstin.length !== 15) { gst.toast('GSTIN must be 15 characters', 'error'); return; }
                    this.loading = true;
                    this.result = null;
                    try {
                        const res = await gst.api(`/gstin/validate?gstin=${encodeURIComponent(this.gstin)}`);
                        this.result = res.data;
                    } catch (e) { gst.toast(e.message, 'error'); }
                    this.loading = false;
                },
            };
        }
    </script>
</x-app-layout>
