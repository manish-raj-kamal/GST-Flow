<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Models\BusinessProfile;
use App\Models\HsnCode;
use App\Models\Product;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
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
        $profile = $this->findAccessibleBusinessProfile($request->validated('business_profile_id'), $request);
        $hsn = HsnCode::query()->where('hsn_code', $request->validated('hsn_code'))->first();
        if (! $hsn) {
            return response()->json(['message' => 'HSN code not found.'], 422);
        }

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

        return response()->json(['message' => 'Product created successfully.', 'data' => $product], 201);
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
