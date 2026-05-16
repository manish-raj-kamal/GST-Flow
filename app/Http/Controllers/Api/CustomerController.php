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
use Illuminate\Support\Collection;

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
        $profiles = $this->userProfiles($request);

        $customers = Customer::query()
            ->get()
            ->filter(fn (Customer $customer) => $customer->isRelatedToProfile((string) $profile->id))
            ->filter(fn (Customer $customer) => blank($request->query('search'))
                || str_contains(strtolower($customer->customer_name), strtolower((string) $request->query('search')))
                || str_contains(strtolower((string) $customer->gstin), strtolower((string) $request->query('search'))))
            ->filter(fn (Customer $customer) => blank($request->query('state')) || $customer->state === $request->query('state'))
            ->filter(fn (Customer $customer) => blank($request->query('customer_type')) || $customer->customer_type === $request->query('customer_type'))
            ->map(fn (Customer $customer): array => $this->presentCustomer($customer, $profiles, $profile))
            ->values();

        return response()->json(['data' => $customers]);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $profileIds = $this->validatedBusinessProfileIds($request);
        $profile = $this->findAccessibleBusinessProfile($request->validated('business_profile_id'), $request);
        $profiles = $this->findAccessibleBusinessProfiles($profileIds, $request);
        $gstin = $request->validated('gstin');
        $validation = $gstin ? $this->gstinService->inspect($gstin) : null;

        if ($gstin && ! $validation['is_valid']) {
            return response()->json(['message' => 'Invalid customer GSTIN provided.', 'validation' => $validation], 422);
        }

        $state = $validation['state_name'] ?? $request->validated('state');
        $stateCode = $validation['state_code'] ?? StateCode::query()->where('state_name', $state)->value('code');

        $customer = Customer::create([
            'business_profile_id' => $profile->id,
            'business_profile_ids' => $profiles->pluck('id')->map(fn ($id) => (string) $id)->values()->all(),
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
            'business_profile_ids' => $customer->business_profile_ids,
        ], $request);

        return response()->json([
            'message' => 'Customer created successfully.',
            'data' => $this->presentCustomer($customer, $this->userProfiles($request), $profile),
        ], 201);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        $profile = $this->findAccessibleProfileForCustomer($customer, $request);
        $profiles = $this->userProfiles($request);

        return response()->json([
            'data' => $this->presentCustomer($customer, $profiles, $profile),
            'recent_invoices' => Invoice::query()
                ->whereIn('business_profile_id', $customer->relatedBusinessProfileIds())
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
        $existingProfile = $this->findAccessibleProfileForCustomer($customer, $request);
        $profileIds = $this->validatedBusinessProfileIds($request);
        $profile = $this->findAccessibleBusinessProfile($request->validated('business_profile_id'), $request);
        $profiles = $this->findAccessibleBusinessProfiles($profileIds, $request);
        $gstin = $request->validated('gstin');
        $validation = $gstin ? $this->gstinService->inspect($gstin) : null;

        if ($gstin && ! $validation['is_valid']) {
            return response()->json(['message' => 'Invalid customer GSTIN provided.', 'validation' => $validation], 422);
        }

        $state = $validation['state_name'] ?? $request->validated('state');
        $stateCode = $validation['state_code'] ?? StateCode::query()->where('state_name', $state)->value('code');

        $customer->update([
            'business_profile_id' => $profile->id,
            'business_profile_ids' => $profiles->pluck('id')->map(fn ($id) => (string) $id)->values()->all(),
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
            'previous_business_profile_id' => $existingProfile->id,
            'business_profile_ids' => $customer->business_profile_ids,
        ], $request);

        $freshCustomer = $customer->fresh();

        return response()->json([
            'message' => 'Customer updated successfully.',
            'data' => $this->presentCustomer($freshCustomer, $this->userProfiles($request), $profile),
        ]);
    }

    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        $profile = $this->findAccessibleProfileForCustomer($customer, $request);
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

    private function findAccessibleProfileForCustomer(Customer $customer, Request $request): BusinessProfile
    {
        $accessibleProfileIds = $this->userProfiles($request)->pluck('id')->map(fn ($id) => (string) $id)->all();

        $profileId = collect($customer->relatedBusinessProfileIds())
            ->first(fn (string $id): bool => in_array($id, $accessibleProfileIds, true));

        abort_if(! $profileId, 403, 'Forbidden');

        return BusinessProfile::query()->findOrFail($profileId);
    }

    private function validatedBusinessProfileIds(StoreCustomerRequest $request): array
    {
        return collect($request->validated('business_profile_ids', []))
            ->push($request->validated('business_profile_id'))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function findAccessibleBusinessProfiles(array $businessProfileIds, Request $request): Collection
    {
        $profiles = $this->userProfiles($request)
            ->filter(fn (BusinessProfile $profile): bool => in_array((string) $profile->id, $businessProfileIds, true))
            ->values();

        abort_if($profiles->count() !== count($businessProfileIds), 403, 'Forbidden');

        return $profiles;
    }

    private function userProfiles(Request $request): Collection
    {
        return BusinessProfile::query()
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->get()
            ->values();
    }

    private function presentCustomer(Customer $customer, Collection $profiles, ?BusinessProfile $contextProfile = null): array
    {
        $relatedProfiles = $customer->relatedBusinessProfiles($profiles)
            ->map(fn (BusinessProfile $profile): array => [
                'id' => (string) $profile->id,
                'business_name' => $profile->business_name,
            ])
            ->values()
            ->all();

        $relatedProfileIds = collect($relatedProfiles)->pluck('id')->all();
        $contextStateCode = $contextProfile?->state_code;

        return [
            'id' => (string) $customer->id,
            'business_profile_id' => (string) ($customer->business_profile_id ?: ($relatedProfileIds[0] ?? '')),
            'business_profile_ids' => $relatedProfileIds,
            'business_profiles' => $relatedProfiles,
            'customer_name' => $customer->customer_name,
            'gstin' => $customer->gstin,
            'state' => $customer->state,
            'state_code' => $customer->state_code,
            'address' => $customer->address,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'customer_type' => $customer->customer_type,
            'is_interstate' => $contextStateCode && $customer->state_code
                ? $contextStateCode !== $customer->state_code
                : (bool) $customer->is_interstate,
        ];
    }
}
