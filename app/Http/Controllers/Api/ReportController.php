<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GstReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function sales(Request $request, GstReportService $reportService): JsonResponse
    {
        $profile = $reportService->resolveProfile($request->user(), $request->query('business_profile_id'));
        abort_if(! $profile, 422, 'No business profile is available for this user.');

        return response()->json([
            'data' => $reportService->salesReport($profile, $request->user(), $request->only([
                'from_date',
                'to_date',
                'customer_id',
                'status',
                'search',
                'min_amount',
                'max_amount',
                'gst_rate',
            ])),
        ]);
    }

    public function purchases(Request $request, GstReportService $reportService): JsonResponse
    {
        $profile = $reportService->resolveProfile($request->user(), $request->query('business_profile_id'));
        abort_if(! $profile, 422, 'No business profile is available for this user.');

        return response()->json([
            'data' => $reportService->purchaseReport($profile, $request->user(), $request->only([
                'from_date',
                'to_date',
                'customer_id',
                'status',
                'search',
                'min_amount',
                'max_amount',
                'gst_rate',
            ])),
        ]);
    }

    public function monthlyGstSummary(Request $request, GstReportService $reportService): JsonResponse
    {
        $profile = $reportService->resolveProfile($request->user(), $request->query('business_profile_id'));
        abort_if(! $profile, 422, 'No business profile is available for this user.');

        return response()->json([
            'data' => $reportService->monthlySummary($profile, $request->user(), $request->only(['month'])),
        ]);
    }
}
