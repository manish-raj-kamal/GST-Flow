<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Module pages
    Route::get('/business-profiles', [PageController::class, 'businessProfiles'])->name('business-profiles');
    Route::get('/customers', [PageController::class, 'customers'])->name('customers');
    Route::get('/products', [PageController::class, 'products'])->name('products');
    Route::get('/hsn-codes', [PageController::class, 'hsnCodes'])->name('hsn-codes');
    Route::get('/tax-slabs', [PageController::class, 'taxSlabs'])->name('tax-slabs');
    Route::get('/invoices', [PageController::class, 'invoices'])->name('invoices');
    Route::get('/invoices/create', [PageController::class, 'invoiceForm'])->name('invoices.create');
    Route::get('/reports', [PageController::class, 'reports'])->name('reports');
    Route::get('/gstr-summary', [PageController::class, 'gstrSummary'])->name('gstr-summary');
    Route::get('/admin', [PageController::class, 'admin'])->name('admin');
    Route::get('/activity-logs', [PageController::class, 'activityLogs'])->name('activity-logs');
    Route::get('/gstin-validator', [PageController::class, 'gstinValidator'])->name('gstin-validator');
    Route::get('/documentation', [PageController::class, 'documentation'])->name('documentation');
});

require __DIR__.'/auth.php';
