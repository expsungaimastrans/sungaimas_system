<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\FinanceController;

// ===================================================
// PUBLIC
// ===================================================
Route::get('/', fn () => redirect()->route('login'));

Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

// ===================================================
// AUTH REQUIRED
// ===================================================
Route::middleware(['auth'])->group(function () {

    // ================================
    // DASHBOARD (OWNER ONLY)
    // ================================
    Route::middleware('role:owner')->group(function () {
        Route::get('/dashboard',      [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    });

    // ================================
    // SHIPMENTS (OWNER, ADMIN, FINANCE boleh lihat)
    // ================================
    Route::middleware('role:owner,admin,finance')->group(function () {

        Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');

        Route::get('/shipments/{id}/pdf', [ShipmentController::class, 'pdf'])->name('shipments.pdf');
        Route::get('/shipments/{id}/success', [ShipmentController::class, 'success'])->name('shipments.success');

        // API untuk manifest / picker dll
        Route::get('/api/shipments/search', [ShipmentController::class, 'searchJson'])->name('shipments.search');
        Route::get('/api/shipments/{id}',   [ShipmentController::class, 'showJson'])->name('shipments.showJson');
    });

    // OWNER + ADMIN boleh buat / edit nota
    Route::middleware('role:owner,admin')->group(function () {
        Route::get('/shipments/create', [ShipmentController::class, 'create'])->name('shipments.create');
        Route::post('/shipments',       [ShipmentController::class, 'store'])->name('shipments.store');

        Route::get('/shipments/{id}/edit', [ShipmentController::class, 'edit'])->name('shipments.edit');
        Route::put('/shipments/{id}',      [ShipmentController::class, 'update'])->name('shipments.update');
    });

    // OWNER + FINANCE boleh ubah status pembayaran
    Route::post('/shipments/{id}/set-pembayaran', [ShipmentController::class, 'setStatusPembayaran'])
        ->middleware('role:owner,finance')
        ->name('shipments.setPembayaran');

    // Export CSV: OWNER + FINANCE (admin tidak boleh)
    Route::get('/shipments/export/csv', [ShipmentController::class, 'exportCsv'])
        ->middleware('role:owner,finance')
        ->name('shipments.export.csv');


    // ================================
    // MANIFESTS
    // ================================
    Route::middleware('role:owner,admin,finance')->group(function () {
        Route::get('/manifests',        [ManifestController::class, 'index'])->name('manifests.index');
        Route::get('/manifests/{id}',   [ManifestController::class, 'show'])->name('manifests.show');
        Route::get('/manifests/{id}/pdf',[ManifestController::class, 'pdf'])->name('manifests.pdf');
    });

    // ⚠️ Penting: /manifests/create harus ditulis sebelum /manifests/{id} (sudah aman karena group ini diletakkan setelah show?).
    // Karena Laravel baca sesuai urutan file, kita taruh create lebih atas dari {id} dengan cara pindahkan ke atas:
    // Tapi di file ini create route ada di bawah, maka kita buat ulang urutan dengan menempatkan create BEFORE route {id}.
    // Cara paling aman: definisikan create dulu di group owner/admin.

    Route::middleware('role:owner,admin')->group(function () {
        Route::get('/manifests/create', [ManifestController::class, 'create'])->name('manifests.create');
        Route::post('/manifests',       [ManifestController::class, 'store'])->name('manifests.store');

        Route::get('/manifests/{id}/edit', [ManifestController::class, 'edit'])->name('manifests.edit');
        Route::put('/manifests/{id}',      [ManifestController::class, 'update'])->name('manifests.update');

        Route::post('/manifests/{manifest}/add/{shipment}', [ManifestController::class, 'addShipment'])
            ->name('manifests.addShipment');

        Route::delete('/manifests/{manifest}/remove/{shipment}', [ManifestController::class, 'removeShipment'])
            ->name('manifests.removeShipment');
    });


    // ================================
    // FINANCE (OWNER + FINANCE ONLY)
    // ================================
    Route::prefix('finance')->middleware('role:owner,finance')->group(function () {

        // Finance home
        Route::get('/', [FinanceController::class, 'index'])->name('finance.index');

        // Kelola finance per manifest (opsional)
        Route::get('/manifest/{manifest}', [FinanceController::class, 'byManifest'])->name('finance.manifest');
        Route::post('/shipments/{shipment}/update', [FinanceController::class, 'updateShipmentFinance'])->name('finance.shipment.update');

        // JSON shipments per manifest (ini yang kamu akses /finance/manifest/8/shipments)
        Route::get('/manifest/{manifest}/shipments', [FinanceController::class, 'manifestShipmentsJson'])
            ->name('finance.manifest.shipments');

        // ================================
        // TAGIHAN (INVOICES)
        // ================================
        // Page buat tagihan (yang sekarang kamu pakai: finance.invoices + finance.invoices.data + finance.invoices.store)
        Route::get('/invoices',       [FinanceController::class, 'invoices'])->name('finance.invoices');
        Route::get('/invoices/data',  [FinanceController::class, 'invoiceData'])->name('finance.invoices.data');
        Route::post('/invoices/store',[FinanceController::class, 'storeInvoice'])->name('finance.invoices.store');

        // Daftar tagihan
        Route::get('/invoices/list', [FinanceController::class, 'listInvoices'])->name('finance.invoices.list');
        Route::get('/invoices/{invoice}', [FinanceController::class, 'showInvoice'])->name('finance.invoices.show');
        Route::post('/invoices/{invoice}/status', [FinanceController::class, 'updateInvoiceStatus'])->name('finance.invoices.status');
        Route::get('/invoices/{invoice}/pdf', [FinanceController::class, 'invoicePdf'])->name('finance.invoices.pdf');
    });

});
