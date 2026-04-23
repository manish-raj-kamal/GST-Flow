<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BusinessProfile;
use App\Models\Customer;
use App\Models\HsnCode;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StateCode;
use App\Models\TaxSlab;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
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

    private function getUserProfiles(Request $request)
    {
        return BusinessProfile::query()
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('user_id', $request->user()->id))
            ->get();
    }

    public function businessProfiles(Request $request): View
    {
        try {
            $profiles = $this->getUserProfiles($request);
        } catch (Throwable) {
            $profiles = collect();
        }
        return view('modules.business-profiles', [
            'profiles' => $profiles,
            'pageTitle' => 'Business Profiles',
        ]);
    }

    public function customers(Request $request): View
    {
        try {
            $profile = $this->resolveProfile($request);
            $customers = $profile
                ? Customer::query()->where('business_profile_id', $profile->id)->get()
                : collect();
            $profiles = $this->getUserProfiles($request);
        } catch (Throwable) {
            $customers = collect();
            $profiles = collect();
            $profile = null;
        }
        return view('modules.customers', [
            'customers' => $customers,
            'profiles' => $profiles,
            'activeProfile' => $profile,
            'pageTitle' => 'Customers',
        ]);
    }

    public function products(Request $request): View
    {
        try {
            $profile = $this->resolveProfile($request);
            $products = $profile
                ? Product::query()->where('business_profile_id', $profile->id)->get()
                : collect();
            $profiles = $this->getUserProfiles($request);
            $hsnCodes = HsnCode::query()->where('status', 'active')->get();
            $taxSlabs = TaxSlab::query()->where('status', 'active')->get();
        } catch (Throwable) {
            $products = collect();
            $profiles = collect();
            $hsnCodes = collect();
            $taxSlabs = collect();
            $profile = null;
        }
        return view('modules.products', [
            'products' => $products,
            'profiles' => $profiles,
            'activeProfile' => $profile,
            'hsnCodes' => $hsnCodes,
            'taxSlabs' => $taxSlabs,
            'pageTitle' => 'Products',
        ]);
    }

    public function hsnCodes(): View
    {
        try {
            $codes = HsnCode::query()->get();
        } catch (Throwable) {
            $codes = collect();
        }
        return view('modules.hsn-codes', [
            'codes' => $codes,
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
            $invoices = $profile
                ? Invoice::query()->where('business_profile_id', $profile->id)->where('status', '!=', 'deleted')->get()->sortByDesc('invoice_date')->values()
                : collect();
            $profiles = $this->getUserProfiles($request);
            $customers = $profile
                ? Customer::query()->where('business_profile_id', $profile->id)->get()
                : collect();
        } catch (Throwable) {
            $invoices = collect();
            $profiles = collect();
            $customers = collect();
            $profile = null;
        }
        return view('modules.invoices', [
            'invoices' => $invoices,
            'profiles' => $profiles,
            'activeProfile' => $profile,
            'customers' => $customers,
            'pageTitle' => 'Invoices',
        ]);
    }

    public function invoiceForm(Request $request): View
    {
        try {
            $profile = $this->resolveProfile($request);
            $profiles = $this->getUserProfiles($request);
            $customers = $profile ? Customer::query()->where('business_profile_id', $profile->id)->get() : collect();
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
            $users = User::query()->get()->sortBy('name')->values();
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
        try {
            $logs = ActivityLog::query()
                ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('user_id', $request->user()->id))
                ->get()
                ->sortByDesc('created_at')
                ->take(100)
                ->values();
        } catch (Throwable) {
            $logs = collect();
        }
        return view('modules.activity-logs', [
            'logs' => $logs,
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
