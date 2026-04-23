<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\BusinessProfile;
use App\Models\Invoice;
use App\Models\InvoiceVersion;
use App\Services\GstReportService;
use App\Services\InvoiceWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request, GstReportService $reportService): JsonResponse
    {
        $profile = $reportService->resolveProfile($request->user(), $request->query('business_profile_id'));
        abort_if(! $profile, 422, 'No business profile is available for this user.');

        $invoices = Invoice::query()
            ->where('business_profile_id', $profile->id)
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->status !== 'deleted')
            ->filter(fn (Invoice $invoice) => blank($request->query('search'))
                || str_contains(strtolower($invoice->invoice_number), strtolower((string) $request->query('search')))
                || str_contains(strtolower($invoice->place_of_supply), strtolower((string) $request->query('search'))))
            ->filter(fn (Invoice $invoice) => blank($request->query('status')) || $invoice->status === $request->query('status'))
            ->filter(fn (Invoice $invoice) => blank($request->query('customer_id')) || $invoice->customer_id === $request->query('customer_id'))
            ->filter(fn (Invoice $invoice) => blank($request->query('transaction_type')) || $invoice->transaction_type === $request->query('transaction_type'))
            ->filter(function (Invoice $invoice) use ($request): bool {
                $date = $invoice->invoice_date?->toDateString() ?? (string) $invoice->invoice_date;
                if ($request->query('from_date') && $date < $request->query('from_date')) {
                    return false;
                }
                if ($request->query('to_date') && $date > $request->query('to_date')) {
                    return false;
                }

                return true;
            })
            ->filter(function (Invoice $invoice) use ($request): bool {
                if (! $request->filled('gst_rate')) {
                    return true;
                }

                return collect($invoice->items ?? [])->contains(fn (array $item): bool => (float) ($item['tax_rate'] ?? 0) === (float) $request->query('gst_rate'));
            })
            ->filter(fn (Invoice $invoice) => ! $request->filled('min_amount') || (float) $invoice->total_amount >= (float) $request->query('min_amount'))
            ->filter(fn (Invoice $invoice) => ! $request->filled('max_amount') || (float) $invoice->total_amount <= (float) $request->query('max_amount'))
            ->sortByDesc('invoice_date')
            ->values();

        return response()->json(['data' => $invoices]);
    }

    public function store(StoreInvoiceRequest $request, InvoiceWorkflowService $invoiceWorkflowService): JsonResponse
    {
        $businessProfile = BusinessProfile::query()->findOrFail($request->validated('business_profile_id'));
        $this->authorizeBusinessProfile($request, $businessProfile);
        $invoice = $invoiceWorkflowService->create($request->validated(), $request->user(), $request);

        return response()->json(['message' => 'Invoice created successfully.', 'data' => $invoice], 201);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $businessProfile = BusinessProfile::query()->findOrFail($invoice->business_profile_id);
        $this->authorizeBusinessProfile($request, $businessProfile);

        return response()->json([
            'data' => $invoice,
            'versions' => InvoiceVersion::query()->where('invoice_id', $invoice->id)->orderByDesc('edited_at')->get()->values(),
        ]);
    }

    public function update(StoreInvoiceRequest $request, Invoice $invoice, InvoiceWorkflowService $invoiceWorkflowService): JsonResponse
    {
        $businessProfile = BusinessProfile::query()->findOrFail($invoice->business_profile_id);
        $this->authorizeBusinessProfile($request, $businessProfile);
        $updatedInvoice = $invoiceWorkflowService->update($invoice, $request->validated(), $request->user(), $request);

        return response()->json(['message' => 'Invoice updated successfully.', 'data' => $updatedInvoice]);
    }

    public function destroy(Request $request, Invoice $invoice, InvoiceWorkflowService $invoiceWorkflowService): JsonResponse
    {
        $businessProfile = BusinessProfile::query()->findOrFail($invoice->business_profile_id);
        $this->authorizeBusinessProfile($request, $businessProfile);
        $invoiceWorkflowService->delete($invoice, $request->user(), $request);

        return response()->json(['message' => 'Invoice deleted successfully.']);
    }

    public function duplicate(Request $request, Invoice $invoice, InvoiceWorkflowService $invoiceWorkflowService): JsonResponse
    {
        $businessProfile = BusinessProfile::query()->findOrFail($invoice->business_profile_id);
        $this->authorizeBusinessProfile($request, $businessProfile);
        $duplicate = $invoiceWorkflowService->duplicate($invoice, $request->user(), $request);

        return response()->json(['message' => 'Invoice duplicated successfully.', 'data' => $duplicate], 201);
    }

    public function versions(Request $request, Invoice $invoice): JsonResponse
    {
        $businessProfile = BusinessProfile::query()->findOrFail($invoice->business_profile_id);
        $this->authorizeBusinessProfile($request, $businessProfile);

        return response()->json([
            'data' => InvoiceVersion::query()->where('invoice_id', $invoice->id)->orderByDesc('edited_at')->get()->values(),
        ]);
    }

    private function authorizeBusinessProfile(Request $request, BusinessProfile $businessProfile): void
    {
        abort_if(! $request->user()->isAdmin() && $businessProfile->user_id !== $request->user()->id, 403, 'Forbidden');
    }
}
