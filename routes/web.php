<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\AuthController;

// ===================================================
// PUBLIC
// ===================================================

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ===================================================
// AUTH REQUIRED
// ===================================================

Route::middleware(['auth'])->group(function () {

    // ================================
    // DASHBOARD (OWNER ONLY)
    // ================================
    Route::middleware('role:owner')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('/dashboard/data', [DashboardController::class, 'data'])
            ->name('dashboard.data');
    });

    // ================================
    // SHIPMENTS
    // ================================
    Route::middleware('role:owner,admin,finance')->group(function () {

        Route::get('/shipments', [ShipmentController::class, 'index'])
            ->name('shipments.index');

            Route::get('/shipments/export/csv', [ShipmentController::class, 'exportCsv'])
    ->middleware('role:owner,finance') // admin tidak boleh export (sesuai aturan finance/owner)
    ->name('shipments.export.csv');


        Route::get('/shipments/{id}/pdf', [ShipmentController::class, 'pdf'])
            ->name('shipments.pdf');

        Route::get('/shipments/{id}/success', [ShipmentController::class, 'success'])
            ->name('shipments.success');

        Route::get('/api/shipments/search', [ShipmentController::class, 'searchJson'])
            ->name('shipments.search');

        Route::get('/api/shipments/{id}', [ShipmentController::class, 'showJson'])
            ->name('shipments.showJson');
    });

    

    // OWNER + ADMIN
    Route::middleware('role:owner,admin')->group(function () {

        Route::get('/shipments/create', [ShipmentController::class, 'create'])
            ->name('shipments.create');

        Route::post('/shipments', [ShipmentController::class, 'store'])
            ->name('shipments.store');

        Route::get('/shipments/{id}/edit', [ShipmentController::class, 'edit'])
            ->name('shipments.edit');

        Route::put('/shipments/{id}', [ShipmentController::class, 'update'])
            ->name('shipments.update');
    });

    // OWNER + FINANCE
    Route::post('/shipments/{id}/set-pembayaran',
        [ShipmentController::class, 'setStatusPembayaran']
    )->middleware('role:owner,finance')
     ->name('shipments.setPembayaran');

    // ================================
    // MANIFESTS
    // ================================

    Route::middleware('role:owner,admin,finance')->group(function () {

        Route::get('/manifests', [ManifestController::class, 'index'])
            ->name('manifests.index');

        Route::get('/manifests/{id}', [ManifestController::class, 'show'])
            ->name('manifests.show');

        Route::get('/manifests/{id}/pdf', [ManifestController::class, 'pdf'])
            ->name('manifests.pdf');
    });

    Route::middleware('role:owner,admin')->group(function () {

        Route::get('/manifests/create', [ManifestController::class, 'create'])
            ->name('manifests.create');

        Route::post('/manifests', [ManifestController::class, 'store'])
            ->name('manifests.store');

        Route::get('/manifests/{id}/edit', [ManifestController::class, 'edit'])
            ->name('manifests.edit');

        Route::put('/manifests/{id}', [ManifestController::class, 'update'])
            ->name('manifests.update');

        Route::post('/manifests/{manifest}/add/{shipment}',
            [ManifestController::class, 'addShipment']
        )->name('manifests.addShipment');

        Route::delete('/manifests/{manifest}/remove/{shipment}',
            [ManifestController::class, 'removeShipment']
        )->name('manifests.removeShipment');
    });

    // ================================
// FINANCE
// ================================
Route::prefix('finance')->middleware(['auth','role:owner,finance'])->group(function () {

    Route::get('/', [FinanceController::class, 'index'])->name('finance.index');

    // ✅ PAGE: daftar tagihan
    Route::get('/invoices', [FinanceController::class, 'invoices'])->name('finance.invoices');

    // ✅ PAGE: buat tagihan (tanpa pilih manifest)
    Route::get('/invoices/create', [FinanceController::class, 'createInvoice'])->name('finance.invoices.create');

    // ✅ AJAX: cari nota untuk dimasukkan ke tagihan
    Route::get('/invoices/shipments/search', [FinanceController::class, 'searchShipmentsForInvoice'])
        ->name('finance.invoices.shipments.search');

    // ✅ generate PDF tagihan (POST)
    Route::post('/invoices/generate', [FinanceController::class, 'generateInvoicePdf'])
        ->name('finance.invoices.generate');

    // (opsional: kalau masih pakai kelola finance per manifest)
    Route::get('/manifest/{manifest}', [FinanceController::class, 'byManifest'])->name('finance.manifest');
    Route::post('/shipments/{shipment}/update', [FinanceController::class, 'updateShipmentFinance'])->name('finance.shipment.update');


 // TAGIHAN
 Route::get('/invoices', [FinanceController::class, 'invoices'])->name('finance.invoices');
 Route::get('/invoices/data', [FinanceController::class, 'invoiceData'])->name('finance.invoices.data');
 Route::post('/invoices/store', [FinanceController::class, 'storeInvoice'])->name('finance.invoices.store');
 
 Route::get('/invoices/list', [FinanceController::class, 'listInvoices'])->name('finance.invoices.list');
 Route::get('/invoices/{invoice}', [FinanceController::class, 'showInvoice'])->name('finance.invoices.show');
 Route::post('/invoices/{invoice}/status', [FinanceController::class, 'updateInvoiceStatus'])->name('finance.invoices.status');
 Route::get('/invoices/{invoice}/pdf', [FinanceController::class, 'invoicePdf'])->name('finance.invoices.pdf');
 

});


    
   


});
