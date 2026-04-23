<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BusinessProfile;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GstReportService
{
    public function resolveProfile(User $user, ?string $businessProfileId = null): ?BusinessProfile
    {
        if ($businessProfileId) {
            $profile = BusinessProfile::query()->findOrFail($businessProfileId);
            abort_if(! $user->isAdmin() && $profile->user_id !== $user->id, 403, 'Forbidden');

            return $profile;
        }

        return $user->isAdmin()
            ? BusinessProfile::query()->first()
            : BusinessProfile::query()->where('user_id', $user->id)->first();
    }

    public function dashboard(BusinessProfile $profile): array
    {
        $invoices = $this->invoicesForProfile($profile);
        $salesInvoices = $invoices->where('transaction_type', 'sales')->values();
        $purchaseInvoices = $invoices->where('transaction_type', 'purchase')->values();
        $monthlyInvoices = $invoices->filter(fn (Invoice $invoice) => $this->invoiceDate($invoice)->isCurrentMonth())->values();

        $recentInvoices = $invoices
            ->sortByDesc(fn (Invoice $invoice) => $this->invoiceDate($invoice)->timestamp)
            ->take(5)
            ->values()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $this->invoiceDate($invoice)->toDateString(),
                'transaction_type' => $invoice->transaction_type,
                'customer_name' => Customer::query()->find($invoice->customer_id)?->customer_name,
                'status' => $invoice->status,
                'total_amount' => (float) $invoice->total_amount,
            ])->all();

        $topProducts = $salesInvoices
            ->flatMap(fn (Invoice $invoice) => collect($invoice->items ?? []))
            ->groupBy('product_name')
            ->map(fn (Collection $items, string $productName) => [
                'label' => $productName,
                'value' => round($items->sum('line_total'), 2),
            ])
            ->sortByDesc('value')
            ->take(5)
            ->values()
            ->all();

        $stateWiseSales = $salesInvoices
            ->groupBy('place_of_supply')
            ->map(fn (Collection $invoiceGroup, string $state) => [
                'label' => $state,
                'value' => round($invoiceGroup->sum('total_amount'), 2),
            ])
            ->sortByDesc('value')
            ->values()
            ->all();

        $activityLogs = ActivityLog::query()->latest()->get()
            ->filter(function (ActivityLog $log) use ($profile): bool {
                return data_get($log->affected_record, 'business_profile_id') === $profile->id
                    || data_get($log->meta, 'business_profile_id') === $profile->id
                    || $log->user_id === $profile->user_id;
            })
            ->take(8)
            ->map(fn (ActivityLog $log) => [
                'action_type' => str($log->action_type)->replace('_', ' ')->title()->toString(),
                'created_at' => optional($log->created_at)->toDateTimeString(),
                'ip_address' => $log->ip_address,
            ])
            ->values()
            ->all();

        return [
            'business_profile' => $profile,
            'overview' => [
                'total_invoices' => $invoices->count(),
                'monthly_invoices' => $monthlyInvoices->count(),
                'gst_collected' => round($salesInvoices->sum('total_tax'), 2),
                'gst_payable' => round($salesInvoices->sum('total_tax') - $purchaseInvoices->sum('total_tax'), 2),
                'total_revenue' => round($salesInvoices->sum('total_amount'), 2),
                'active_customers' => $salesInvoices->pluck('customer_id')->unique()->count(),
            ],
            'recent_invoices' => $recentInvoices,
            'top_products' => $topProducts,
            'state_wise_sales' => $stateWiseSales,
            'activity_logs' => $activityLogs,
            'monthly_tax_summary' => $this->monthlySummary($profile, null, []),
        ];
    }

    public function salesReport(BusinessProfile $profile, ?User $user, array $filters = []): array
    {
        $invoices = $this->applyFilters(
            $this->invoicesForProfile($profile)->where('transaction_type', 'sales')->values(),
            $filters
        );

        $summary = $this->reportSummary('sales', $invoices, $filters);
        $this->storeReport($user, 'sales', $filters, $summary);

        return $summary;
    }

    public function purchaseReport(BusinessProfile $profile, ?User $user, array $filters = []): array
    {
        $invoices = $this->applyFilters(
            $this->invoicesForProfile($profile)->where('transaction_type', 'purchase')->values(),
            $filters
        );

        $summary = $this->reportSummary('purchase', $invoices, $filters);
        $this->storeReport($user, 'purchase', $filters, $summary);

        return $summary;
    }

    public function monthlySummary(BusinessProfile $profile, ?User $user, array $filters = []): array
    {
        $month = isset($filters['month']) && $filters['month']
            ? Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth()
            : now()->startOfMonth();

        $invoices = $this->invoicesForProfile($profile)->filter(function (Invoice $invoice) use ($month): bool {
            return $this->invoiceDate($invoice)->isSameMonth($month);
        })->values();

        $summary = [
            'type' => 'monthly_gst_summary',
            'month' => $month->format('Y-m'),
            'totals' => [
                'cgst' => round($invoices->sum('cgst'), 2),
                'sgst' => round($invoices->sum('sgst'), 2),
                'igst' => round($invoices->sum('igst'), 2),
                'net_payable_gst' => round($invoices->sum('total_tax'), 2),
                'taxable_turnover' => round($invoices->sum('taxable_value'), 2),
            ],
        ];

        $this->storeReport($user, 'monthly_gst_summary', ['month' => $month->format('Y-m')], $summary);

        return $summary;
    }

    public function gstrSummary(BusinessProfile $profile, ?User $user, array $filters = []): array
    {
        $salesInvoices = $this->applyFilters(
            $this->invoicesForProfile($profile)->where('transaction_type', 'sales')->values(),
            $filters
        );

        $summary = [
            'type' => 'gstr_summary',
            'invoice_count' => $salesInvoices->count(),
            'outward_supplies_summary' => $salesInvoices
                ->groupBy(fn (Invoice $invoice) => data_get($invoice->tax_breakdowns, 'supply_type', 'intrastate'))
                ->map(fn (Collection $group, string $label) => [
                    'label' => $label,
                    'value' => round($group->sum('total_amount'), 2),
                ])
                ->values()
                ->all(),
            'tax_liability_totals' => [
                'cgst' => round($salesInvoices->sum('cgst'), 2),
                'sgst' => round($salesInvoices->sum('sgst'), 2),
                'igst' => round($salesInvoices->sum('igst'), 2),
                'total_tax' => round($salesInvoices->sum('total_tax'), 2),
            ],
            'hsn_wise_summary' => $salesInvoices
                ->flatMap(fn (Invoice $invoice) => collect($invoice->items ?? []))
                ->groupBy('hsn_code')
                ->map(fn (Collection $items, string $hsnCode) => [
                    'hsn_code' => $hsnCode,
                    'invoice_count' => $items->count(),
                    'taxable_value' => round($items->sum('taxable_value'), 2),
                    'tax_amount' => round($items->sum('tax_amount'), 2),
                ])
                ->values()
                ->all(),
            'state_wise_supply_totals' => $salesInvoices
                ->groupBy('place_of_supply')
                ->map(fn (Collection $group, string $state) => [
                    'label' => $state,
                    'value' => round($group->sum('taxable_value'), 2),
                ])
                ->values()
                ->all(),
        ];

        $this->storeReport($user, 'gstr_summary', $filters, $summary);

        return $summary;
    }

    private function invoicesForProfile(BusinessProfile $profile): Collection
    {
        return Invoice::query()
            ->where('business_profile_id', $profile->id)
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->status !== 'deleted')
            ->values();
    }

    private function applyFilters(Collection $invoices, array $filters): Collection
    {
        $from = isset($filters['from_date']) && $filters['from_date'] ? Carbon::parse($filters['from_date'])->startOfDay() : null;
        $to = isset($filters['to_date']) && $filters['to_date'] ? Carbon::parse($filters['to_date'])->endOfDay() : null;

        return $invoices->filter(function (Invoice $invoice) use ($filters, $from, $to): bool {
            $date = $this->invoiceDate($invoice);
            $customerMatch = empty($filters['customer_id']) || $invoice->customer_id === $filters['customer_id'];
            $statusMatch = empty($filters['status']) || $invoice->status === $filters['status'];
            $gstMatch = empty($filters['gst_rate'])
                || collect($invoice->items ?? [])->contains(fn (array $item): bool => (float) ($item['tax_rate'] ?? 0) === (float) $filters['gst_rate']);
            $searchMatch = empty($filters['search'])
                || str_contains(strtolower($invoice->invoice_number), strtolower($filters['search']))
                || str_contains(strtolower((string) $invoice->place_of_supply), strtolower($filters['search']));
            $minAmountMatch = ! isset($filters['min_amount']) || $filters['min_amount'] === null || (float) $invoice->total_amount >= (float) $filters['min_amount'];
            $maxAmountMatch = ! isset($filters['max_amount']) || $filters['max_amount'] === null || (float) $invoice->total_amount <= (float) $filters['max_amount'];

            if ($from && $date->lt($from)) {
                return false;
            }

            if ($to && $date->gt($to)) {
                return false;
            }

            return $customerMatch && $statusMatch && $gstMatch && $searchMatch && $minAmountMatch && $maxAmountMatch;
        })->values();
    }

    private function reportSummary(string $type, Collection $invoices, array $filters): array
    {
        return [
            'type' => $type,
            'filters' => $filters,
            'invoice_count' => $invoices->count(),
            'totals' => [
                'taxable_value' => round($invoices->sum('taxable_value'), 2),
                'cgst' => round($invoices->sum('cgst'), 2),
                'sgst' => round($invoices->sum('sgst'), 2),
                'igst' => round($invoices->sum('igst'), 2),
                'total_tax' => round($invoices->sum('total_tax'), 2),
                'grand_total' => round($invoices->sum('total_amount'), 2),
            ],
            'invoices' => $invoices->sortByDesc(fn (Invoice $invoice) => $this->invoiceDate($invoice)->timestamp)->values()->all(),
        ];
    }

    private function storeReport(?User $user, string $type, array $filters, array $summary): void
    {
        if (! $user) {
            return;
        }

        Report::create([
            'user_id' => $user->id,
            'report_type' => $type,
            'from_date' => $filters['from_date'] ?? null,
            'to_date' => $filters['to_date'] ?? null,
            'filters' => $filters,
            'summary' => $summary,
        ]);
    }

    private function invoiceDate(Invoice $invoice): Carbon
    {
        return $invoice->invoice_date instanceof Carbon
            ? $invoice->invoice_date
            : Carbon::parse($invoice->invoice_date);
    }
}
