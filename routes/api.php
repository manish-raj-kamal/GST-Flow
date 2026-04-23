<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessProfileController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\GstrSummaryController;
use App\Http\Controllers\Api\HsnCodeController;
use App\Http\Controllers\Api\InvoiceController;
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
    Route::apiResource('products', ProductController::class);
    Route::apiResource('hsn-codes', HsnCodeController::class);
    Route::apiResource('tax-slabs', TaxSlabController::class);
    Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate']);
    Route::get('invoices/{invoice}/versions', [InvoiceController::class, 'versions']);
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
        Route::get('/tax-summary.csv', [ExportController::class, 'taxSummaryCsv']);
        Route::get('/monthly-summary.xls', [ExportController::class, 'monthlySummaryXls']);
    });

    Route::get('gstr-summary', [GstrSummaryController::class, 'index']);
    Route::get('activity-logs', [ActivityLogController::class, 'index']);

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{user}/toggle-status', [AdminController::class, 'toggleUserStatus']);
        Route::put('/users/{user}/role', [AdminController::class, 'assignRole']);
        Route::get('/analytics', [AdminController::class, 'analytics']);
    });
});
