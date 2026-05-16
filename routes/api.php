<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessProfileController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\GstRuleController;
use App\Http\Controllers\Api\GstrSummaryController;
use App\Http\Controllers\Api\HsnCodeController;
use App\Http\Controllers\Api\HsnSearchController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\GstNotificationController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TaxSlabController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
    });

    Route::get('gstin/validate', [BusinessProfileController::class, 'validateGstin']);
    Route::get('state-codes', [BusinessProfileController::class, 'stateCodes']);

    Route::apiResource('business-profiles', BusinessProfileController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::get('products/audit/tax-rates', [ProductController::class, 'auditTaxRates']);
    Route::post('products/{product}/clone-to-profile', [ProductController::class, 'cloneToProfile']);
    Route::apiResource('products', ProductController::class);
    Route::get('hsn-codes/catalog', [HsnCodeController::class, 'catalog']);
    Route::post('hsn-codes/sync', [HsnCodeController::class, 'sync']);
    Route::apiResource('hsn-codes', HsnCodeController::class);
    Route::get('hsn/search', [HsnSearchController::class, 'search']);
    Route::post('hsn/search/select', [HsnSearchController::class, 'select']);
    Route::apiResource('tax-slabs', TaxSlabController::class);
    Route::get('gst-rules', [GstRuleController::class, 'index']);
    Route::post('gst-rules', [GstRuleController::class, 'store']);
    Route::put('gst-rules/{gstRule}', [GstRuleController::class, 'update']);
    Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate']);
    Route::get('invoices/{invoice}/versions', [InvoiceController::class, 'versions']);
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus']);
    Route::apiResource('invoices', InvoiceController::class);

    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::prefix('reports')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales']);
        Route::get('/purchases', [ReportController::class, 'purchases']);
        Route::get('/monthly-gst-summary', [ReportController::class, 'monthlyGstSummary']);
    });

    Route::prefix('export')->group(function () {
        Route::get('/invoices/{invoice}/pdf', [ExportController::class, 'invoicePdf']);
        Route::get('/sales.csv', [ExportController::class, 'salesCsv']);
        Route::get('/purchases.csv', [ExportController::class, 'purchaseCsv']);
        Route::get('/tax-summary.csv', [ExportController::class, 'taxSummaryCsv']);
        Route::get('/monthly-summary.xls', [ExportController::class, 'monthlySummaryXls']);
    });

    Route::get('gstr-summary', [GstrSummaryController::class, 'index']);
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
    Route::get('gst-notifications', [GstNotificationController::class, 'index']);
    Route::patch('gst-notifications/{notification}/read', [GstNotificationController::class, 'markRead']);

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{user}/toggle-status', [AdminController::class, 'toggleUserStatus']);
        Route::put('/users/{user}/role', [AdminController::class, 'assignRole']);
        Route::get('/analytics', [AdminController::class, 'analytics']);
    });

    Route::middleware('role:admin')->prefix('hsn')->group(function () {
        Route::get('/analytics', [HsnSearchController::class, 'analytics']);
        Route::post('/import', [HsnSearchController::class, 'import']);
        Route::get('/products', [HsnSearchController::class, 'products']);
        Route::post('/products', [HsnSearchController::class, 'storeProduct']);
        Route::put('/products/{hsnProduct}', [HsnSearchController::class, 'updateProduct']);
        Route::delete('/products/{hsnProduct}', [HsnSearchController::class, 'destroyProduct']);
    });
});
