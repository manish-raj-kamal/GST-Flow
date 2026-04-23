<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxSlab;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxSlabController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $slabs = TaxSlab::query()
            ->get()
            ->filter(fn (TaxSlab $slab) => blank($request->query('status')) || $slab->status === $request->query('status'))
            ->sortBy('rate')
            ->values();

        return response()->json(['data' => $slabs]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $duplicate = TaxSlab::query()->where('name', $validated['name'])->first();
        if ($duplicate) {
            return response()->json(['message' => 'Tax slab name already exists.'], 422);
        }

        $slab = TaxSlab::create([
            ...$validated,
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->activityLogService->log($request->user(), 'tax_slab_created', ['tax_slab_id' => $slab->id], $request);

        return response()->json(['message' => 'Tax slab created successfully.', 'data' => $slab], 201);
    }

    public function show(TaxSlab $taxSlab): JsonResponse
    {
        return response()->json(['data' => $taxSlab]);
    }

    public function update(Request $request, TaxSlab $taxSlab): JsonResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $duplicate = TaxSlab::query()
            ->where('name', $validated['name'])
            ->where('id', '!=', $taxSlab->id)
            ->first();
        if ($duplicate) {
            return response()->json(['message' => 'Tax slab name already exists.'], 422);
        }

        $taxSlab->update([
            ...$validated,
            'status' => $validated['status'] ?? $taxSlab->status,
        ]);

        $this->activityLogService->log($request->user(), 'tax_slab_updated', ['tax_slab_id' => $taxSlab->id], $request);

        return response()->json(['message' => 'Tax slab updated successfully.', 'data' => $taxSlab->fresh()]);
    }

    public function destroy(Request $request, TaxSlab $taxSlab): JsonResponse
    {
        $this->ensureAdmin($request);
        $taxSlab->delete();
        $this->activityLogService->log($request->user(), 'tax_slab_deleted', ['tax_slab_id' => $taxSlab->id], $request);

        return response()->json(['message' => 'Tax slab deleted successfully.']);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_if(! $request->user()->isAdmin(), 403, 'Forbidden');
    }
}
