<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Models\BusinessProfile;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\StateCode;
use App\Services\ActivityLogService;
use App\Services\GstinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private readonly GstinService $gstinService,
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $profile = $this->resolveBusinessProfile($request);

        $customers = Customer::query()
            ->where('business_profile_id', $profile->id)
            ->get()
            ->filter(fn (Customer $customer) => blank($request->query('search'))
                || str_contains(strtolower($customer->customer_name), strtolower((string) $request->query('search')))
                || str_contains(strtolower((string) $customer->gstin), strtolower((string) $request->query('search'))))
            ->filter(fn (Customer $customer) => blank($request->query('state')) || $customer->state === $request->query('state'))
            ->filter(fn (Customer $customer) => blank($request->query('customer_type')) || $customer->customer_type === $request->query('customer_type'))
            ->values();

        return response()->json(['data' => $customers]);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $profile = $this->findAccessibleBusinessProfile($request->validated('business_profile_id'), $request);
        $gstin = $request->validated('gstin');
        $validation = $gstin ? $this->gstinService->inspect($gstin) : null;

        if ($gstin && ! $validation['is_valid']) {
            return response()->json(['message' => 'Invalid customer GSTIN provided.', 'validation' => $validation], 422);
        }

        $state = $validation['state_name'] ?? $request->validated('state');
        $stateCode = $validation['state_code'] ?? StateCode::query()->where('state_name', $state)->value('code');

        $customer = Customer::create([
            'business_profile_id' => $profile->id,
            'customer_name' => $request->validated('customer_name'),
            'gstin' => $validation['gstin'] ?? null,
            'state' => $state,
            'state_code' => $stateCode,
            'address' => $request->validated('address'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'customer_type' => $request->validated('customer_type'),
            'is_interstate' => $profile->state_code && $stateCode ? $profile->state_code !== $stateCode : false,
        ]);

        $this->activityLogService->log($request->user(), 'customer_created', [
            'customer_id' => $customer->id,
            'business_profile_id' => $profile->id,
        ], $request);

        return response()->json(['message' => 'Customer created successfully.', 'data' => $customer], 201);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        $profile = $this->findAccessibleBusinessProfile($customer->business_profile_id, $request);

        return response()->json([
            'data' => $customer,
            'recent_invoices' => Invoice::query()
                ->where('business_profile_id', $profile->id)
                ->where('customer_id', $customer->id)
                ->where('status', '!=', 'deleted')
                ->get()
                ->sortByDesc('invoice_date')
                ->take(5)
                ->values()
                ->all(),
        ]);
    }

    public function update(StoreCustomerRequest $request, Customer $customer): JsonResponse
    {
        $profile = $this->findAccessibleBusinessProfile($customer->business_profile_id, $request);
        $gstin = $request->validated('gstin');
        $validation = $gstin ? $this->gstinService->inspect($gstin) : null;

        if ($gstin && ! $validation['is_valid']) {
            return response()->json(['message' => 'Invalid customer GSTIN provided.', 'validation' => $validation], 422);
        }

        $state = $validation['state_name'] ?? $request->validated('state');
        $stateCode = $validation['state_code'] ?? StateCode::query()->where('state_name', $state)->value('code');

        $customer->update([
            'customer_name' => $request->validated('customer_name'),
            'gstin' => $validation['gstin'] ?? null,
            'state' => $state,
            'state_code' => $stateCode,
            'address' => $request->validated('address'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'customer_type' => $request->validated('customer_type'),
            'is_interstate' => $profile->state_code && $stateCode ? $profile->state_code !== $stateCode : false,
        ]);

        $this->activityLogService->log($request->user(), 'customer_updated', [
            'customer_id' => $customer->id,
            'business_profile_id' => $profile->id,
        ], $request);

        return response()->json(['message' => 'Customer updated successfully.', 'data' => $customer->fresh()]);
    }

    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        $profile = $this->findAccessibleBusinessProfile($customer->business_profile_id, $request);
        $customer->delete();

        $this->activityLogService->log($request->user(), 'customer_deleted', [
            'customer_id' => $customer->id,
            'business_profile_id' => $profile->id,
        ], $request);

        return response()->json(['message' => 'Customer deleted successfully.']);
    }

    private function resolveBusinessProfile(Request $request): BusinessProfile
    {
        $businessProfileId = $request->query('business_profile_id')
            ?: $request->user()->businessProfiles()->first()?->id;

        abort_if(! $businessProfileId, 422, 'A business_profile_id query parameter is required.');

        return $this->findAccessibleBusinessProfile($businessProfileId, $request);
    }

    private function findAccessibleBusinessProfile(string $businessProfileId, Request $request): BusinessProfile
    {
        $profile = BusinessProfile::query()->findOrFail($businessProfileId);
        abort_if(! $request->user()->isAdmin() && $profile->user_id !== $request->user()->id, 403, 'Forbidden');

        return $profile;
    }
}
