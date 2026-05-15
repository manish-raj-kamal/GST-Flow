<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HsnCode;
use App\Services\ActivityLogService;
use App\Services\HsnCatalogSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HsnCodeController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'hsn:index:'.md5(json_encode([
            'search' => $request->query('search'),
            'category' => $request->query('category'),
            'status' => $request->query('status'),
        ]));

        $codes = Cache::remember($cacheKey, now()->addMinutes(30), fn () => HsnCode::query()
            ->get()
            ->filter(fn (HsnCode $code) => blank($request->query('search'))
                || str_contains(strtolower($code->hsn_code), strtolower((string) $request->query('search')))
                || str_contains(strtolower($code->description), strtolower((string) $request->query('search'))))
            ->filter(fn (HsnCode $code) => blank($request->query('category')) || $code->category === $request->query('category'))
            ->filter(fn (HsnCode $code) => blank($request->query('status')) || $code->status === $request->query('status'))
            ->values());

        return response()->json(['data' => $codes]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'hsn_code' => ['required', 'string', 'max:8'],
            'description' => ['required', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:255'],
            'gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_date' => ['nullable', 'date'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $existing = HsnCode::query()->where('hsn_code', $validated['hsn_code'])->first();
        if ($existing) {
            return response()->json(['message' => 'HSN code already exists.'], 422);
        }

        $hsnCode = HsnCode::create([
            ...$validated,
            'effective_from' => $validated['effective_from'] ?? $validated['effective_date'] ?? now()->toDateString(),
            'effective_to' => $validated['effective_to'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->activityLogService->log($request->user(), 'hsn_code_created', ['hsn_code_id' => $hsnCode->id], $request);
        Cache::flush();

        return response()->json(['message' => 'HSN code created successfully.', 'data' => $hsnCode], 201);
    }

    public function show(HsnCode $hsnCode): JsonResponse
    {
        return response()->json(['data' => $hsnCode]);
    }

    public function update(Request $request, HsnCode $hsnCode): JsonResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'hsn_code' => ['required', 'string', 'max:8'],
            'description' => ['required', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:255'],
            'gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_date' => ['nullable', 'date'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $duplicate = HsnCode::query()
            ->where('hsn_code', $validated['hsn_code'])
            ->where('id', '!=', $hsnCode->id)
            ->first();
        if ($duplicate) {
            return response()->json(['message' => 'HSN code already exists.'], 422);
        }

        $hsnCode->update([
            ...$validated,
            'effective_from' => $validated['effective_from'] ?? $validated['effective_date'] ?? $hsnCode->effective_from,
            'effective_to' => $validated['effective_to'] ?? $hsnCode->effective_to,
            'status' => $validated['status'] ?? $hsnCode->status,
        ]);

        $this->activityLogService->log($request->user(), 'hsn_code_updated', ['hsn_code_id' => $hsnCode->id], $request);
        Cache::flush();

        return response()->json(['message' => 'HSN code updated successfully.', 'data' => $hsnCode->fresh()]);
    }

    public function destroy(Request $request, HsnCode $hsnCode): JsonResponse
    {
        $this->ensureAdmin($request);
        $hsnCode->delete();
        $this->activityLogService->log($request->user(), 'hsn_code_deleted', ['hsn_code_id' => $hsnCode->id], $request);
        Cache::flush();

        return response()->json(['message' => 'HSN code deleted successfully.']);
    }

    public function catalog(Request $request): JsonResponse
    {
        $search = strtolower(trim((string) $request->query('search', '')));

        $codes = HsnCode::query()
            ->where('status', 'active')
            ->get()
            ->filter(function (HsnCode $code) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str_contains(strtolower((string) $code->hsn_code), $search)
                    || str_contains(strtolower((string) $code->description), $search)
                    || str_contains(strtolower((string) $code->category), $search);
            })
            ->values();

        $grouped = $codes
            ->groupBy(fn (HsnCode $code) => $code->category ?: 'General')
            ->map(function ($items, $category) {
                $rows = collect($items)->map(fn (HsnCode $code) => [
                    'id' => $code->id,
                    'hsn_code' => $code->hsn_code,
                    'description' => $code->description,
                    'gst_rate' => $code->gst_rate,
                    'effective_date' => optional($code->effective_date)->toDateString(),
                    'effective_from' => optional($code->effective_from)->toDateString(),
                    'effective_to' => optional($code->effective_to)->toDateString(),
                    'status' => $code->status,
                ])->values();

                return [
                    'category' => $category,
                    'count' => $rows->count(),
                    'items' => $rows,
                ];
            })
            ->sortBy('category')
            ->values();

        return response()->json(['data' => $grouped]);
    }

    public function sync(Request $request, HsnCatalogSyncService $syncService): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'sources' => ['nullable', 'array'],
            'sources.*' => ['required', 'url'],
        ]);

        $sources = $validated['sources'] ?? config('services.hsn_catalog.sources', []);
        $result = $syncService->sync($sources);
        Cache::flush();

        $this->activityLogService->log($request->user(), 'hsn_catalog_synced', [
            'created' => $result['created'],
            'updated' => $result['updated'],
            'failed_sources' => $result['failed_sources'],
        ], $request);

        return response()->json([
            'message' => 'HSN catalog sync completed.',
            'data' => $result,
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_if(! $request->user()->isAdmin(), 403, 'Forbidden');
    }
}
