<?php

namespace App\Http\Controllers;

use App\Services\GstReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Throwable;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GstReportService $reportService): View
    {
        $payload = [
            'setupIssue' => null,
            'businessProfile' => null,
            'overview' => [],
            'recentInvoices' => [],
            'topProducts' => [],
            'stateWiseSales' => [],
            'activityLogs' => [],
            'monthlySummary' => ['month' => now()->format('Y-m'), 'totals' => []],
        ];

        try {
            $profile = $reportService->resolveProfile($request->user(), $request->query('business_profile_id'));

            if ($profile) {
                $dashboard = $reportService->dashboard($profile);
                $payload = [
                    'setupIssue' => null,
                    'businessProfile' => $dashboard['business_profile'],
                    'overview' => $dashboard['overview'],
                    'recentInvoices' => $dashboard['recent_invoices'],
                    'topProducts' => $dashboard['top_products'],
                    'stateWiseSales' => $dashboard['state_wise_sales'],
                    'activityLogs' => $dashboard['activity_logs'],
                    'monthlySummary' => $dashboard['monthly_tax_summary'],
                ];
            }
        } catch (Throwable $throwable) {
            $payload['setupIssue'] = $throwable->getMessage();
        }

        return view('dashboard', $payload);
    }
}
