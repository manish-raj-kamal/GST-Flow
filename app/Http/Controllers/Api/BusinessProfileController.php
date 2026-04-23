<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessProfileRequest;
use App\Models\BusinessProfile;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StateCode;
use App\Services\ActivityLogService;
use App\Services\GstinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessProfileController extends Controller
{
    public function __construct(
        private readonly GstinService $gstinService,
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $profiles = BusinessProfile::query()
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->get()
            ->filter(fn (BusinessProfile $profile) => blank($request->query('search'))
                || str_contains(strtolower($profile->business_name), strtolower((string) $request->query('search')))
                || str_contains(strtolower($profile->gstin), strtolower((string) $request->query('search'))))
            ->values();

        return response()->json(['data' => $profiles]);
    }

    public function store(StoreBusinessProfileRequest $request): JsonResponse
    {
        $validation = $this->gstinService->inspect($request->validated('gstin'));
        if (! $validation['is_valid']) {
            return response()->json(['message' => 'Invalid GSTIN provided.', 'validation' => $validation], 422);
        }

        $existing = BusinessProfile::query()->where('gstin', $validation['gstin'])->first();
        if ($existing) {
            return response()->json(['message' => 'This GSTIN is already registered.'], 422);
        }

        $profile = BusinessProfile::create([
            'user_id' => $request->user()->id,
            'business_name' => $request->validated('business_name'),
            'legal_name' => $request->validated('legal_name'),
            'gstin' => $validation['gstin'],
            'pan' => data_get($validation, 'parts.pan'),
            'address' => $request->validated('address'),
            'city' => $request->validated('city'),
            'state' => $validation['state_name'],
            'state_code' => $validation['state_code'],
            'pincode' => $request->validated('pincode'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'business_type' => $request->validated('business_type'),
            'registration_date' => $request->validated('registration_date'),
        ]);

        $this->activityLogService->log($request->user(), 'business_profile_created', [
            'business_profile_id' => $profile->id,
            'gstin' => $profile->gstin,
        ], $request);

        return response()->json(['message' => 'Business profile created successfully.', 'data' => $profile], 201);
    }

    public function show(Request $request, BusinessProfile $businessProfile): JsonResponse
    {
        $this->authorizeBusinessProfile($request, $businessProfile);

        return response()->json([
            'data' => $businessProfile,
            'stats' => [
                'customers' => Customer::query()->where('business_profile_id', $businessProfile->id)->count(),
                'products' => Product::query()->where('business_profile_id', $businessProfile->id)->count(),
                'invoices' => Invoice::query()->where('business_profile_id', $businessProfile->id)->count(),
            ],
        ]);
    }

    public function update(StoreBusinessProfileRequest $request, BusinessProfile $businessProfile): JsonResponse
    {
        $this->authorizeBusinessProfile($request, $businessProfile);

        $validation = $this->gstinService->inspect($request->validated('gstin'));
        if (! $validation['is_valid']) {
            return response()->json(['message' => 'Invalid GSTIN provided.', 'validation' => $validation], 422);
        }

        $duplicate = BusinessProfile::query()
            ->where('gstin', $validation['gstin'])
            ->where('id', '!=', $businessProfile->id)
            ->first();

        if ($duplicate) {
            return response()->json(['message' => 'This GSTIN is already assigned to another profile.'], 422);
        }

        $businessProfile->update([
            'business_name' => $request->validated('business_name'),
            'legal_name' => $request->validated('legal_name'),
            'gstin' => $validation['gstin'],
            'pan' => data_get($validation, 'parts.pan'),
            'address' => $request->validated('address'),
            'city' => $request->validated('city'),
            'state' => $validation['state_name'],
            'state_code' => $validation['state_code'],
            'pincode' => $request->validated('pincode'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'business_type' => $request->validated('business_type'),
            'registration_date' => $request->validated('registration_date'),
        ]);

        $this->activityLogService->log($request->user(), 'business_profile_updated', [
            'business_profile_id' => $businessProfile->id,
        ], $request);

        return response()->json(['message' => 'Business profile updated successfully.', 'data' => $businessProfile->fresh()]);
    }

    public function destroy(Request $request, BusinessProfile $businessProfile): JsonResponse
    {
        $this->authorizeBusinessProfile($request, $businessProfile);

        Customer::query()->where('business_profile_id', $businessProfile->id)->delete();
        Product::query()->where('business_profile_id', $businessProfile->id)->delete();
        Invoice::query()->where('business_profile_id', $businessProfile->id)->delete();
        $businessProfile->delete();

        $this->activityLogService->log($request->user(), 'business_profile_deleted', [
            'business_profile_id' => $businessProfile->id,
        ], $request);

        return response()->json(['message' => 'Business profile deleted successfully.']);
    }

    public function validateGstin(Request $request): JsonResponse
    {
        $request->validate([
            'gstin' => ['required', 'string', 'size:15'],
        ]);

        return response()->json(['data' => $this->gstinService->inspect($request->query('gstin', $request->input('gstin')))]);
    }

    public function stateCodes(): JsonResponse
    {
        return response()->json(['data' => StateCode::query()->orderBy('code')->get()]);
    }

    private function authorizeBusinessProfile(Request $request, BusinessProfile $businessProfile): void
    {
        abort_if(! $request->user()->isAdmin() && $businessProfile->user_id !== $request->user()->id, 403, 'Forbidden');
    }
}
