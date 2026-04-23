<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GstReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GstrSummaryController extends Controller
{
    public function index(Request $request, GstReportService $reportService): JsonResponse
    {
        $profile = $reportService->resolveProfile($request->user(), $request->query('business_profile_id'));
        abort_if(! $profile, 422, 'No business profile is available for this user.');

        return response()->json([
            'data' => $reportService->gstrSummary($profile, $request->user(), $request->only([
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
}
