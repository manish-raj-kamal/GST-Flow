<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HsnProduct;
use App\Models\HsnSearchLog;
use App\Services\HsnImportService;
use App\Services\HsnSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HsnSearchController extends Controller
{
    public function search(Request $request, HsnSearchService $searchService): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'category' => ['nullable', 'string', 'max:120'],
        ]);

        $payload = $searchService->search(
            $validated['q'],
            (int) ($validated['limit'] ?? 8),
            $validated['category'] ?? null
        );

        $results = $payload['results'];
        $meta = $payload['meta'];

        HsnSearchLog::create([
            'user_id' => $request->user()?->id,
            'query' => $validated['q'],
            'normalized_query' => strtolower(trim($validated['q'])),
            'results_count' => count($results),
            'top_confidence' => (int) (($results[0]['confidence'] ?? 0)),
            'latency_ms' => (int) ($meta['latency_ms'] ?? 0),
            'status' => count($results) > 0 ? 'matched' : 'no_result',
            'result_snapshot' => array_slice($results, 0, 5),
            'meta' => [
                'category' => $validated['category'] ?? null,
                'suggestions' => $meta['suggestions'] ?? [],
            ],
        ]);

        return response()->json([
            'success' => true,
            'results' => $results,
            'meta' => $meta,
        ]);
    }

    public function select(Request $request, HsnSearchService $searchService): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:120'],
            'hsn_code' => ['required', 'string', 'max:16'],
        ]);

        $searchService->recordSelection($validated['query'], $validated['hsn_code']);

        $latest = HsnSearchLog::query()
            ->where('query', $validated['query'])
            ->whereNull('selected_hsn_code')
            ->orderByDesc('created_at')
            ->first();

        if ($latest) {
            $latest->update(['selected_hsn_code' => $validated['hsn_code']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Selection recorded.',
        ]);
    }

    public function analytics(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $recentLogs = HsnSearchLog::query()->orderByDesc('created_at')->limit(2500)->get();

        $mostSearched = $recentLogs
            ->groupBy('normalized_query')
            ->map(fn ($rows, $query) => ['query' => $query, 'count' => count($rows)])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        $failedSearches = $recentLogs
            ->filter(fn ($row) => $row->status === 'no_result')
            ->groupBy('normalized_query')
            ->map(fn ($rows, $query) => ['query' => $query, 'count' => count($rows)])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        $lowConfidence = $recentLogs
            ->filter(fn ($row) => (int) ($row->top_confidence ?? 0) < 60)
            ->take(20)
            ->map(fn ($row) => [
                'query' => $row->query,
                'confidence' => (int) ($row->top_confidence ?? 0),
                'results_count' => (int) ($row->results_count ?? 0),
                'created_at' => optional($row->created_at)->toISOString(),
            ])
            ->values();

        $selectedCodes = $recentLogs
            ->filter(fn ($row) => filled($row->selected_hsn_code))
            ->groupBy('selected_hsn_code')
            ->map(fn ($rows, $hsnCode) => [
                'hsn_code' => $hsnCode,
                'count' => count($rows),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'most_searched' => $mostSearched,
                'failed_searches' => $failedSearches,
                'low_confidence' => $lowConfidence,
                'selected_codes' => $selectedCodes,
            ],
        ]);
    }

    public function import(Request $request, HsnImportService $importService): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xls,xlsx', 'max:20480'],
        ]);

        $result = $importService->importUploadedFile($validated['file'], 'admin-upload');

        return response()->json([
            'success' => true,
            'message' => 'Import completed.',
            'data' => $result,
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $search = strtolower(trim((string) $request->query('search', '')));
        $status = strtolower(trim((string) $request->query('status', '')));

        $rows = HsnProduct::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('search_weight')
            ->limit(300)
            ->get()
            ->filter(function (HsnProduct $product) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str_contains(strtolower((string) $product->hsn_code), $search)
                    || str_contains(strtolower((string) $product->primary_name), $search)
                    || str_contains(strtolower((string) $product->official_description), $search)
                    || str_contains(strtolower((string) $product->category), $search);
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $this->validateProduct($request);
        $product = HsnProduct::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'HSN product created.',
            'data' => $product,
        ], 201);
    }

    public function updateProduct(Request $request, HsnProduct $hsnProduct): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $this->validateProduct($request);
        $hsnProduct->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'HSN product updated.',
            'data' => $hsnProduct->fresh(),
        ]);
    }

    public function destroyProduct(Request $request, HsnProduct $hsnProduct): JsonResponse
    {
        $this->ensureAdmin($request);
        $hsnProduct->delete();

        return response()->json([
            'success' => true,
            'message' => 'HSN product deleted.',
        ]);
    }

    private function validateProduct(Request $request): array
    {
        $validated = $request->validate([
            'hsn_code' => ['required', 'string', 'max:8'],
            'primary_name' => ['required', 'string', 'max:255'],
            'aliases' => ['nullable', 'array'],
            'aliases.*' => ['string', 'max:120'],
            'category' => ['nullable', 'string', 'max:255'],
            'subcategory' => ['nullable', 'string', 'max:255'],
            'official_description' => ['nullable', 'string', 'max:800'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:120'],
            'gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'search_weight' => ['nullable', 'integer', 'min:1', 'max:120'],
            'status' => ['nullable', 'in:active,inactive'],
            'source' => ['nullable', 'string', 'max:120'],
        ]);

        $validated['hsn_code'] = substr(preg_replace('/\D+/', '', (string) $validated['hsn_code']) ?: '', 0, 8);
        $validated['search_weight'] = (int) ($validated['search_weight'] ?? 90);
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['source'] = $validated['source'] ?? 'manual';
        $validated['aliases'] = collect($validated['aliases'] ?? [])->filter()->values()->all();
        $validated['keywords'] = collect($validated['keywords'] ?? [])->filter()->values()->all();

        return $validated;
    }

    private function ensureAdmin(Request $request): void
    {
        abort_if(! $request->user()->isAdmin(), 403, 'Forbidden');
    }
}

