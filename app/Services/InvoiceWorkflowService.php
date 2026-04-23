<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceVersion;
use App\Models\Product;
use App\Models\StateCode;
use App\Models\User;
use Illuminate\Http\Request;

class InvoiceWorkflowService
{
    public function __construct(
        private readonly GstCalculationService $gstCalculationService,
        private readonly InvoiceNumberService $invoiceNumberService,
        private readonly GstinService $gstinService,
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function create(array $validated, User $user, ?Request $request = null): Invoice
    {
        [$businessProfile, $customer, $sellerStateCode, $buyerStateCode, $sellerGstin, $buyerGstin] = $this->resolveContext($validated);
        $lineItems = $this->buildLineItems($validated['items'], $businessProfile->id);
        $calculation = $this->gstCalculationService->calculate($lineItems, $sellerStateCode, $buyerStateCode);

        $invoice = Invoice::create([
            'business_profile_id' => $businessProfile->id,
            'customer_id' => $customer->id,
            'transaction_type' => $validated['transaction_type'],
            'invoice_number' => $this->invoiceNumberService->generate($validated['transaction_type']),
            'invoice_date' => $validated['invoice_date'],
            'seller_gstin' => $sellerGstin,
            'buyer_gstin' => $buyerGstin,
            'place_of_supply' => $validated['transaction_type'] === 'purchase' ? $businessProfile->state : $customer->state,
            'seller_state_code' => $sellerStateCode,
            'buyer_state_code' => $buyerStateCode,
            'items' => $calculation['items'],
            'taxable_value' => $calculation['totals']['taxable_value'],
            'cgst' => $calculation['totals']['cgst'],
            'sgst' => $calculation['totals']['sgst'],
            'igst' => $calculation['totals']['igst'],
            'total_tax' => $calculation['totals']['total_tax'],
            'total_amount' => $calculation['totals']['invoice_total'],
            'status' => $validated['status'] ?? 'draft',
            'tax_breakdowns' => [
                'supply_type' => $calculation['supply_type'],
                'same_state' => $calculation['same_state'],
                'summary' => $calculation['totals'],
            ],
        ]);

        $this->activityLogService->log($user, 'invoice_created', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'business_profile_id' => $businessProfile->id,
        ], $request);

        return $invoice;
    }

    public function update(Invoice $invoice, array $validated, User $user, ?Request $request = null): Invoice
    {
        $original = $this->snapshot($invoice);
        [$businessProfile, $customer, $sellerStateCode, $buyerStateCode, $sellerGstin, $buyerGstin] = $this->resolveContext($validated);
        $lineItems = $this->buildLineItems($validated['items'], $businessProfile->id);
        $calculation = $this->gstCalculationService->calculate($lineItems, $sellerStateCode, $buyerStateCode);

        $invoice->fill([
            'business_profile_id' => $businessProfile->id,
            'customer_id' => $customer->id,
            'transaction_type' => $validated['transaction_type'],
            'invoice_date' => $validated['invoice_date'],
            'seller_gstin' => $sellerGstin,
            'buyer_gstin' => $buyerGstin,
            'place_of_supply' => $validated['transaction_type'] === 'purchase' ? $businessProfile->state : $customer->state,
            'seller_state_code' => $sellerStateCode,
            'buyer_state_code' => $buyerStateCode,
            'items' => $calculation['items'],
            'taxable_value' => $calculation['totals']['taxable_value'],
            'cgst' => $calculation['totals']['cgst'],
            'sgst' => $calculation['totals']['sgst'],
            'igst' => $calculation['totals']['igst'],
            'total_tax' => $calculation['totals']['total_tax'],
            'total_amount' => $calculation['totals']['invoice_total'],
            'status' => $validated['status'] ?? $invoice->status,
            'tax_breakdowns' => [
                'supply_type' => $calculation['supply_type'],
                'same_state' => $calculation['same_state'],
                'summary' => $calculation['totals'],
            ],
        ])->save();

        InvoiceVersion::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'original_values' => $original,
            'updated_values' => $this->snapshot($invoice->fresh()),
            'change_summary' => 'Invoice updated',
            'edited_at' => now(),
        ]);

        $this->activityLogService->log($user, 'invoice_updated', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'business_profile_id' => $invoice->business_profile_id,
        ], $request);

        return $invoice->fresh();
    }

    public function duplicate(Invoice $invoice, User $user, ?Request $request = null): Invoice
    {
        $duplicate = Invoice::create([
            'business_profile_id' => $invoice->business_profile_id,
            'customer_id' => $invoice->customer_id,
            'transaction_type' => $invoice->transaction_type,
            'invoice_number' => $this->invoiceNumberService->generate($invoice->transaction_type),
            'invoice_date' => now()->toDateString(),
            'seller_gstin' => $invoice->seller_gstin,
            'buyer_gstin' => $invoice->buyer_gstin,
            'place_of_supply' => $invoice->place_of_supply,
            'seller_state_code' => $invoice->seller_state_code,
            'buyer_state_code' => $invoice->buyer_state_code,
            'items' => $invoice->items,
            'taxable_value' => $invoice->taxable_value,
            'cgst' => $invoice->cgst,
            'sgst' => $invoice->sgst,
            'igst' => $invoice->igst,
            'total_tax' => $invoice->total_tax,
            'total_amount' => $invoice->total_amount,
            'status' => 'draft',
            'tax_breakdowns' => $invoice->tax_breakdowns,
        ]);

        $this->activityLogService->log($user, 'invoice_duplicated', [
            'source_invoice_id' => $invoice->id,
            'invoice_id' => $duplicate->id,
            'business_profile_id' => $invoice->business_profile_id,
        ], $request);

        return $duplicate;
    }

    public function delete(Invoice $invoice, User $user, ?Request $request = null): Invoice
    {
        $original = $this->snapshot($invoice);

        $invoice->update([
            'status' => 'deleted',
        ]);

        InvoiceVersion::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'original_values' => $original,
            'updated_values' => $this->snapshot($invoice->fresh()),
            'change_summary' => 'Invoice marked as deleted',
            'edited_at' => now(),
        ]);

        $this->activityLogService->log($user, 'invoice_deleted', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'business_profile_id' => $invoice->business_profile_id,
        ], $request);

        return $invoice->fresh();
    }

    private function resolveContext(array $validated): array
    {
        $businessProfile = BusinessProfile::query()->findOrFail($validated['business_profile_id']);
        $customer = Customer::query()->findOrFail($validated['customer_id']);

        $transactionType = $validated['transaction_type'];
        $sellerStateCode = $transactionType === 'purchase'
            ? ($customer->state_code ?: $this->stateCodeFromState($customer->state))
            : ($businessProfile->state_code ?: $this->gstinService->detectStateCode($businessProfile->gstin) ?: $this->stateCodeFromState($businessProfile->state));
        $buyerStateCode = $transactionType === 'purchase'
            ? ($businessProfile->state_code ?: $this->gstinService->detectStateCode($businessProfile->gstin) ?: $this->stateCodeFromState($businessProfile->state))
            : ($customer->state_code ?: $this->stateCodeFromState($customer->state));
        $sellerGstin = $transactionType === 'purchase'
            ? ($customer->gstin ?: null)
            : $businessProfile->gstin;
        $buyerGstin = $transactionType === 'purchase'
            ? $businessProfile->gstin
            : ($customer->gstin ?: null);

        return [$businessProfile, $customer, $sellerStateCode ?? '', $buyerStateCode ?? '', $sellerGstin, $buyerGstin];
    }

    private function buildLineItems(array $items, string $businessProfileId): array
    {
        return collect($items)->map(function (array $item) use ($businessProfileId): array {
            $product = Product::query()->findOrFail($item['product_id']);
            abort_if($product->business_profile_id !== $businessProfileId, 422, 'Invoice item product does not belong to the selected business profile.');

            return [
                'product_name' => $product->product_name,
                'hsn_code' => $product->hsn_code,
                'quantity' => (float) $item['quantity'],
                'rate' => (float) ($item['rate'] ?? $product->price),
                'tax_rate' => (float) $product->gst_rate,
            ];
        })->all();
    }

    private function stateCodeFromState(?string $stateName): ?string
    {
        if (! $stateName) {
            return null;
        }

        return StateCode::query()->where('state_name', $stateName)->value('code');
    }

    private function snapshot(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'business_profile_id' => $invoice->business_profile_id,
            'customer_id' => $invoice->customer_id,
            'transaction_type' => $invoice->transaction_type,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => optional($invoice->invoice_date)->toDateString(),
            'status' => $invoice->status,
            'items' => $invoice->items,
            'taxable_value' => $invoice->taxable_value,
            'cgst' => $invoice->cgst,
            'sgst' => $invoice->sgst,
            'igst' => $invoice->igst,
            'total_tax' => $invoice->total_tax,
            'total_amount' => $invoice->total_amount,
            'tax_breakdowns' => $invoice->tax_breakdowns,
        ];
    }
}
