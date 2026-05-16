<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StateCode;
use App\Models\TaxSlab;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

class PageController extends Controller
{
    private function resolveProfile(Request $request): ?BusinessProfile
    {
        $user = $request->user();
        $profileId = $request->query('business_profile_id') ?: $user->businessProfiles()->first()?->id;
        if (! $profileId) return null;
        $profile = BusinessProfile::query()->find($profileId);
        if (! $profile) return null;
        if (! $user->isAdmin() && $profile->user_id !== $user->id) return null;
        return $profile;
    }

    private function getUserProfiles(Request $request): Collection
    {
        return BusinessProfile::query()
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('user_id', $request->user()->id))
            ->get();
    }

    private function customersForProfile(?BusinessProfile $profile): Collection
    {
        if (! $profile) {
            return collect();
        }

        return Customer::query()
            ->get()
            ->filter(fn (Customer $customer): bool => $customer->isRelatedToProfile((string) $profile->id))
            ->values();
    }

    private function presentCustomers(Collection $customers, Collection $profiles, ?BusinessProfile $contextProfile = null): Collection
    {
        return $customers->map(function (Customer $customer) use ($profiles, $contextProfile): array {
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
        })->values();
    }

    private function presentBusinessProfiles(Collection $profiles): Collection
    {
        return $profiles->map(fn (BusinessProfile $profile): array => [
            'id' => (string) $profile->id,
            'business_name' => $profile->business_name,
            'legal_name' => $profile->legal_name,
            'gstin' => $profile->gstin,
            'pan' => $profile->pan,
            'address' => $profile->address,
            'city' => $profile->city,
            'state' => $profile->state,
            'state_code' => $profile->state_code,
            'pincode' => $profile->pincode,
            'email' => $profile->email,
            'phone' => $profile->phone,
            'business_type' => $profile->business_type,
            'registration_date' => optional($profile->registration_date)->toDateString(),
        ])->values();
    }

    private function presentProducts(Collection $products, Collection $profiles, Collection $allUserProducts): Collection
    {
        $groupedProducts = $allUserProducts
            ->groupBy(fn (array $product): string => (string) ($product['product_key'] ?: $product['id']));

        return $products->map(function (Product $product) use ($profiles, $groupedProducts): array {
            $groupKey = (string) ($product->product_key ?: $product->id);
            $relatedProfileIds = collect($groupedProducts->get($groupKey, collect([
                ['business_profile_id' => (string) $product->business_profile_id],
            ])))
                ->pluck('business_profile_id')
                ->filter()
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values()
                ->all();

            $relatedProfiles = $profiles
                ->filter(fn (BusinessProfile $profile): bool => in_array((string) $profile->id, $relatedProfileIds, true))
                ->map(fn (BusinessProfile $profile): array => [
                    'id' => (string) $profile->id,
                    'business_name' => $profile->business_name,
                ])
                ->values()
                ->all();

            return [
                'id' => (string) $product->id,
                'business_profile_id' => (string) $product->business_profile_id,
                'business_profile_ids' => $relatedProfileIds,
                'business_profiles' => $relatedProfiles,
                'product_key' => $product->product_key ? (string) $product->product_key : null,
                'product_name' => $product->product_name,
                'description' => $product->description,
                'category' => $product->category,
                'hsn_code' => $product->hsn_code,
                'unit' => $product->unit,
                'price' => (float) $product->price,
                'gst_rate' => (float) $product->gst_rate,
                'status' => $product->status,
            ];
        })->values();
    }

    public function businessProfiles(Request $request): View
    {
        return view('modules.business-profiles', [
            'profiles' => collect(),
            'pageTitle' => 'Business Profiles',
        ]);
    }

    public function customers(Request $request): View
    {
        try {
            $profile = $this->resolveProfile($request);
            $profiles = $this->getUserProfiles($request);
        } catch (Throwable) {
            $profiles = collect();
            $profile = null;
        }
        return view('modules.customers', [
            'customers' => collect(),
            'profiles' => $profiles,
            'activeProfile' => $profile,
            'pageTitle' => 'Customers',
        ]);
    }

    public function products(Request $request): View
    {
        try {
            $profile = $this->resolveProfile($request);
            $profiles = $this->getUserProfiles($request);
            $taxSlabs = TaxSlab::query()->where('status', 'active')->get();
        } catch (Throwable) {
            $profiles = collect();
            $taxSlabs = collect();
            $profile = null;
        }
        return view('modules.products', [
            'products' => collect(),
            'profiles' => $profiles,
            'activeProfile' => $profile,
            'taxSlabs' => $taxSlabs,
            'pageTitle' => 'Products',
        ]);
    }

    public function hsnCodes(): View
    {
        return view('modules.hsn-codes', [
            'codes' => collect(),
            'pageTitle' => 'HSN Codes',
        ]);
    }

    public function taxSlabs(): View
    {
        try {
            $slabs = TaxSlab::query()->get()->sortBy('rate')->values();
        } catch (Throwable) {
            $slabs = collect();
        }
        return view('modules.tax-slabs', [
            'slabs' => $slabs,
            'pageTitle' => 'Tax Slabs',
        ]);
    }

    public function invoices(Request $request): View
    {
        try {
            $profile = $this->resolveProfile($request);
            $profiles = $this->getUserProfiles($request);
        } catch (Throwable) {
            $profiles = collect();
            $profile = null;
        }
        return view('modules.invoices', [
            'invoices' => collect(),
            'profiles' => $profiles,
            'activeProfile' => $profile,
            'customers' => collect(),
            'pageTitle' => 'Invoices',
        ]);
    }

    public function invoiceForm(Request $request): View
    {
        try {
            $profile = $this->resolveProfile($request);
            $profiles = $this->getUserProfiles($request);
            $customers = $this->presentCustomers($this->customersForProfile($profile), $profiles, $profile);
            $products = $profile ? Product::query()->where('business_profile_id', $profile->id)->where('status', 'active')->get() : collect();
            $stateCodes = StateCode::query()->orderBy('code')->get();
        } catch (Throwable) {
            $profiles = collect();
            $customers = collect();
            $products = collect();
            $stateCodes = collect();
            $profile = null;
        }
        return view('modules.invoice-form', [
            'profiles' => $profiles,
            'activeProfile' => $profile,
            'customers' => $customers,
            'products' => $products,
            'stateCodes' => $stateCodes,
            'pageTitle' => 'Create Invoice',
        ]);
    }

    public function reports(Request $request): View
    {
        $profiles = collect();
        $profile = null;
        try {
            $profiles = $this->getUserProfiles($request);
            $profile = $this->resolveProfile($request);
        } catch (Throwable) {}
        return view('modules.reports', [
            'profiles' => $profiles,
            'activeProfile' => $profile,
            'pageTitle' => 'Reports',
        ]);
    }

    public function gstrSummary(Request $request): View
    {
        $profiles = collect();
        $profile = null;
        try {
            $profiles = $this->getUserProfiles($request);
            $profile = $this->resolveProfile($request);
        } catch (Throwable) {}
        return view('modules.gstr-summary', [
            'profiles' => $profiles,
            'activeProfile' => $profile,
            'pageTitle' => 'GSTR Summary',
        ]);
    }

    public function admin(Request $request): View
    {
        abort_if(! $request->user()->isAdmin(), 403);
        try {
            $users = User::query()
                ->when($request->user()->isTestAdmin(), fn ($query) => $query->whereIn('email', User::TEST_ACCOUNT_EMAILS))
                ->get()
                ->sortBy('name')
                ->values();
            $totalProfiles = BusinessProfile::query()->count();
            $totalInvoices = Invoice::query()->count();
        } catch (Throwable) {
            $users = collect();
            $totalProfiles = 0;
            $totalInvoices = 0;
        }
        return view('modules.admin', [
            'users' => $users,
            'totalProfiles' => $totalProfiles,
            'totalInvoices' => $totalInvoices,
            'pageTitle' => 'Admin Panel',
        ]);
    }

    public function activityLogs(Request $request): View
    {
        return view('modules.activity-logs', [
            'logs' => collect(),
            'pageTitle' => 'Activity Logs',
        ]);
    }

    public function gstinValidator(): View
    {
        return view('modules.gstin-validator', ['pageTitle' => 'GSTIN Validator']);
    }

    public function documentation(): View
    {
        return view('modules.documentation', ['pageTitle' => 'Documentation']);
    }
}
