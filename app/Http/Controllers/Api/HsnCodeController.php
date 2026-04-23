<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HsnCode;
use App\Services\ActivityLogService;
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
        $codes = HsnCode::query()
            ->get()
            ->filter(fn (HsnCode $code) => blank($request->query('search'))
                || str_contains(strtolower($code->hsn_code), strtolower((string) $request->query('search')))
                || str_contains(strtolower($code->description), strtolower((string) $request->query('search'))))
            ->filter(fn (HsnCode $code) => blank($request->query('category')) || $code->category === $request->query('category'))
            ->filter(fn (HsnCode $code) => blank($request->query('status')) || $code->status === $request->query('status'))
            ->values();

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
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $existing = HsnCode::query()->where('hsn_code', $validated['hsn_code'])->first();
        if ($existing) {
            return response()->json(['message' => 'HSN code already exists.'], 422);
        }

        $hsnCode = HsnCode::create([
            ...$validated,
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->activityLogService->log($request->user(), 'hsn_code_created', ['hsn_code_id' => $hsnCode->id], $request);

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
            'status' => $validated['status'] ?? $hsnCode->status,
        ]);

        $this->activityLogService->log($request->user(), 'hsn_code_updated', ['hsn_code_id' => $hsnCode->id], $request);

        return response()->json(['message' => 'HSN code updated successfully.', 'data' => $hsnCode->fresh()]);
    }

    public function destroy(Request $request, HsnCode $hsnCode): JsonResponse
    {
        $this->ensureAdmin($request);
        $hsnCode->delete();
        $this->activityLogService->log($request->user(), 'hsn_code_deleted', ['hsn_code_id' => $hsnCode->id], $request);

        return response()->json(['message' => 'HSN code deleted successfully.']);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_if(! $request->user()->isAdmin(), 403, 'Forbidden');
    }
}
