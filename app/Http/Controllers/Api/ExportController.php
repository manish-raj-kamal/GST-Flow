<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use App\Models\Invoice;
use App\Services\GstReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function invoicePdf(Request $request, Invoice $invoice)
    {
        $businessProfile = BusinessProfile::query()->findOrFail($invoice->business_profile_id);
        abort_if(! $request->user()->isAdmin() && $businessProfile->user_id !== $request->user()->id, 403, 'Forbidden');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'businessProfile' => $businessProfile,
            'customer' => $invoice->customer,
        ]);

        return $pdf->download($invoice->invoice_number.'.pdf');
    }

    public function salesCsv(Request $request, GstReportService $reportService): StreamedResponse
    {
        $profile = $reportService->resolveProfile($request->user(), $request->query('business_profile_id'));
        abort_if(! $profile, 422, 'No business profile is available for this user.');
        $report = $reportService->salesReport($profile, $request->user(), $request->only(['from_date', 'to_date', 'customer_id', 'status']));

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Invoice Number', 'Invoice Date', 'Customer', 'Taxable Value', 'CGST', 'SGST', 'IGST', 'Total', 'Status']);
            foreach ($report['invoices'] as $invoice) {
                fputcsv($handle, [
                    $invoice['invoice_number'],
                    optional($invoice['invoice_date'])->toDateString(),
                    $invoice['customer_id'],
                    $invoice['taxable_value'],
                    $invoice['cgst'],
                    $invoice['sgst'],
                    $invoice['igst'],
                    $invoice['total_amount'],
                    $invoice['status'],
                ]);
            }
            fclose($handle);
        }, 'sales-report.csv', ['Content-Type' => 'text/csv']);
    }

    public function taxSummaryCsv(Request $request, GstReportService $reportService): StreamedResponse
    {
        $profile = $reportService->resolveProfile($request->user(), $request->query('business_profile_id'));
        abort_if(! $profile, 422, 'No business profile is available for this user.');
        $summary = $reportService->monthlySummary($profile, $request->user(), $request->only(['month']));

        return response()->streamDownload(function () use ($summary): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Metric', 'Value']);
            foreach ($summary['totals'] as $metric => $value) {
                fputcsv($handle, [$metric, $value]);
            }
            fclose($handle);
        }, 'tax-summary.csv', ['Content-Type' => 'text/csv']);
    }

    public function monthlySummaryXls(Request $request, GstReportService $reportService): StreamedResponse
    {
        $profile = $reportService->resolveProfile($request->user(), $request->query('business_profile_id'));
        abort_if(! $profile, 422, 'No business profile is available for this user.');
        $summary = $reportService->monthlySummary($profile, $request->user(), $request->only(['month']));

        return response()->streamDownload(function () use ($summary): void {
            echo "Metric\tValue\n";
            foreach ($summary['totals'] as $metric => $value) {
                echo $metric."\t".$value."\n";
            }
        }, 'monthly-gst-summary.xls', ['Content-Type' => 'application/vnd.ms-excel']);
    }
}
