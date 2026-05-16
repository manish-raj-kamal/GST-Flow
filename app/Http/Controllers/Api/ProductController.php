<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Models\BusinessProfile;
use App\Models\HsnCode;
use App\Models\Product;
use App\Services\ActivityLogService;
use App\Services\HsnCatalogSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly HsnCatalogSyncService $hsnCatalogSyncService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $profile = $this->resolveBusinessProfile($request);

        $products = Product::query()
            ->where('business_profile_id', $profile->id)
            ->get()
            ->filter(fn (Product $product) => blank($request->query('search'))
                || str_contains(strtolower($product->product_name), strtolower((string) $request->query('search')))
                || str_contains(strtolower((string) $product->hsn_code), strtolower((string) $request->query('search'))))
            ->filter(fn (Product $product) => blank($request->query('category')) || $product->category === $request->query('category'))
            ->filter(fn (Product $product) => blank($request->query('status')) || $product->status === $request->query('status'))
            ->values();

        return response()->json(['data' => $products]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $hsn = HsnCode::query()->where('hsn_code', $request->validated('hsn_code'))->first();
        if (! $hsn) {
            return response()->json(['message' => 'HSN code not found.'], 422);
        }

        $targetProfileIds = collect($request->validated('business_profile_ids', []))
            ->filter()
            ->values();

        if ($targetProfileIds->isEmpty()) {
            $singleProfile = $request->validated('business_profile_id')
                ?: $request->user()->businessProfiles()->first()?->id;
            abort_if(! $singleProfile, 422, 'A business profile is required.');
            $targetProfileIds = collect([$singleProfile]);
        }

        $createdProducts = $targetProfileIds
            ->map(function (string $profileId) use ($request, $hsn): Product {
                $profile = $this->findAccessibleBusinessProfile($profileId, $request);

                $product = Product::create([
                    'business_profile_id' => $profile->id,
                    'product_name' => $request->validated('product_name'),
                    'description' => $request->validated('description') ?: $hsn->description,
                    'category' => $request->validated('category') ?: $hsn->category,
                    'hsn_code' => $hsn->hsn_code,
                    'unit' => $request->validated('unit'),
                    'price' => $request->validated('price'),
                    'gst_rate' => $request->validated('gst_rate') ?? $hsn->gst_rate,
                    'status' => $request->validated('status') ?? 'active',
                ]);

                $this->activityLogService->log($request->user(), 'product_created', [
                    'product_id' => $product->id,
                    'business_profile_id' => $profile->id,
                ], $request);

                return $product;
            })
            ->values();

        return response()->json([
            'message' => $createdProducts->count() > 1
                ? sprintf('Product created for %d business profiles.', $createdProducts->count())
                : 'Product created successfully.',
            'data' => $createdProducts->first(),
            'created_products' => $createdProducts,
        ], 201);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->findAccessibleBusinessProfile($product->business_profile_id, $request);

        return response()->json(['data' => $product]);
    }

    public function update(StoreProductRequest $request, Product $product): JsonResponse
    {
        $this->findAccessibleBusinessProfile($product->business_profile_id, $request);
        $hsn = HsnCode::query()->where('hsn_code', $request->validated('hsn_code'))->first();
        if (! $hsn) {
            return response()->json(['message' => 'HSN code not found.'], 422);
        }

        $product->update([
            'product_name' => $request->validated('product_name'),
            'description' => $request->validated('description') ?: $hsn->description,
            'category' => $request->validated('category') ?: $hsn->category,
            'hsn_code' => $hsn->hsn_code,
            'unit' => $request->validated('unit'),
            'price' => $request->validated('price'),
            'gst_rate' => $request->validated('gst_rate') ?? $hsn->gst_rate,
            'status' => $request->validated('status') ?? $product->status,
        ]);

        $this->activityLogService->log($request->user(), 'product_updated', [
            'product_id' => $product->id,
            'business_profile_id' => $product->business_profile_id,
        ], $request);

        return response()->json(['message' => 'Product updated successfully.', 'data' => $product->fresh()]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->findAccessibleBusinessProfile($product->business_profile_id, $request);
        $product->delete();

        $this->activityLogService->log($request->user(), 'product_deleted', [
            'product_id' => $product->id,
            'business_profile_id' => $product->business_profile_id,
        ], $request);

        return response()->json(['message' => 'Product deleted successfully.']);
    }

    public function auditTaxRates(Request $request): JsonResponse
    {
        $profile = $this->resolveBusinessProfile($request);

        $rows = Product::query()
            ->where('business_profile_id', $profile->id)
            ->get()
            ->map(function (Product $product): array {
                $currentRate = $this->hsnCatalogSyncService->resolveCurrentRateForHsn((string) $product->hsn_code, $product->gst_rate);
                $storedRate = (float) ($product->gst_rate ?? 0);
                $isOutdated = $currentRate !== null && (float) $currentRate !== $storedRate;

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->product_name,
                    'hsn_code' => $product->hsn_code,
                    'stored_rate' => $storedRate,
                    'current_rate' => $currentRate,
                    'is_outdated' => $isOutdated,
                ];
            })
            ->filter(fn (array $row): bool => $row['is_outdated'])
            ->values();

        return response()->json(['data' => $rows]);
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
