<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GstRule;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GstRuleController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $rules = GstRule::query()
            ->get()
            ->filter(fn (GstRule $rule) => blank($request->query('status')) || $rule->status === $request->query('status'))
            ->values();

        return response()->json(['data' => $rules]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'rule_key' => ['required', 'string', 'max:100'],
            'rule_name' => ['required', 'string', 'max:255'],
            'config' => ['required', 'array'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $rule = GstRule::create([
            ...$validated,
            'effective_from' => $validated['effective_from'] ?? now()->toDateString(),
            'status' => $validated['status'] ?? 'active',
        ]);

        $this->activityLogService->log($request->user(), 'gst_rule_created', ['gst_rule_id' => $rule->id], $request);

        return response()->json(['message' => 'GST rule created successfully.', 'data' => $rule], 201);
    }

    public function update(Request $request, GstRule $gstRule): JsonResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'rule_name' => ['required', 'string', 'max:255'],
            'config' => ['required', 'array'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $gstRule->update([
            ...$validated,
            'status' => $validated['status'] ?? $gstRule->status,
        ]);

        $this->activityLogService->log($request->user(), 'gst_rule_updated', ['gst_rule_id' => $gstRule->id], $request);

        return response()->json(['message' => 'GST rule updated successfully.', 'data' => $gstRule->fresh()]);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_if(! $request->user()->isAdmin(), 403, 'Forbidden');
    }
}

