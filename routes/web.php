<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\Api\PublicTrackingController;

// ===================================================
// PUBLIC
// ===================================================
Route::get('/', fn () => redirect()->route('login'));

Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

// FILE VIEWER
Route::get('/files/view/{path}', [FileController::class, 'viewBukti'])
    ->where('path', '.+')
    ->name('files.view');

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

        Route::get('/shipments/{id}/pdf', [ShipmentController::class, 'pdfHalf'])->name('shipments.pdf');
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
    Route::get('/shipments/export/muat', [ShipmentController::class, 'exportMuat'])
        ->middleware('role:owner,admin,finance')
        ->name('shipments.export.muat');


    // Kirim nota PDF via WhatsApp
    Route::post('/shipments/{shipment}/send-wa', [WhatsappController::class, 'send'])
        ->middleware('role:owner,admin,finance')->name('shipments.sendWa');

    // ================================
    // MANIFESTS
    // ================================
    // ================================
// MANIFESTS
// ================================

// 🔹 OWNER + ADMIN (CREATE / EDIT HARUS DI ATAS)
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


// 🔹 SEMUA ROLE (LIST + SHOW DI BAWAH)
Route::middleware('role:owner,admin,finance')->group(function () {

    Route::get('/manifests', [ManifestController::class, 'index'])
        ->name('manifests.index');

    Route::get('/manifests/{id}', [ManifestController::class, 'show'])
        ->name('manifests.show');

    Route::get('/manifests/{id}/pdf', [ManifestController::class, 'pdf'])
        ->name('manifests.pdf');
    Route::post('/manifests/{manifest}/status', [ManifestController::class, 'updateStatus'])
        ->name('manifests.updateStatus');
});



    // ================================
    // FINANCE (OWNER + FINANCE ONLY)
    // ================================
    Route::prefix('finance')->middleware('role:owner,finance')->group(function () {

        // Finance home
        Route::get('/', [FinanceController::class, 'index'])->name('finance.index');

        // Kelola finance per manifest (opsional)
        Route::get('/manifest/{manifest}', [FinanceController::class, 'byManifest'])->name('finance.manifest');
        Route::post('/finance/manifests/{manifest}/biaya', [FinanceController::class, 'saveBiayaOps'])->name('finance.manifest.biaya');
        Route::post('/shipments/{shipment}/update', [FinanceController::class, 'updateShipmentFinance'])->name('finance.shipment.update');

        // JSON shipments per manifest (ini yang kamu akses /finance/manifest/8/shipments)
        Route::get('/manifest/{manifest}/shipments', [FinanceController::class, 'manifestShipmentsJson'])
            ->name('finance.manifest.shipments');

        // ================================
        // TAGIHAN (INVOICES)
        // ================================
        // Static routes HARUS di atas {invoice}
        Route::get('/invoices',                      [FinanceController::class, 'invoices'])->name('finance.invoices');
        Route::get('/invoices/data',                 [FinanceController::class, 'invoiceData'])->name('finance.invoices.data');
        Route::post('/invoices/store',               [FinanceController::class, 'storeInvoice'])->name('finance.invoices.store');
        Route::get('/invoices/list',                 [FinanceController::class, 'listInvoices'])->name('finance.invoices.list');
        Route::get('/invoices/available-shipments',  [FinanceController::class, 'availableShipments'])->name('finance.invoices.availableShipments');

        // {invoice} routes — semua yang ada segment setelah {invoice} harus lebih dulu
        Route::get('/invoices/{invoice}/pdf',         [FinanceController::class, 'invoicePdf'])->name('finance.invoices.pdf');
        Route::post('/invoices/{invoice}/status',     [FinanceController::class, 'updateInvoiceStatus'])->name('finance.invoices.status');
        Route::post('/invoices/{invoice}/send-wa',    [FinanceController::class, 'sendInvoiceWa'])->name('finance.invoices.sendWa');
        Route::get('/invoices/{invoice}/edit',
            [FinanceController::class, 'editInvoice'])->middleware('role:owner')->name('finance.invoices.edit');
        Route::post('/invoices/{invoice}/add-shipment/{shipment}',
            [FinanceController::class, 'addShipmentToInvoice'])->middleware('role:owner')->name('finance.invoices.addShipment');
        Route::delete('/invoices/{invoice}/remove-shipment/{shipment}',
            [FinanceController::class, 'removeShipmentFromInvoice'])->middleware('role:owner')->name('finance.invoices.removeShipment');
        // Route catch-all {invoice} HARUS paling bawah
        Route::get('/invoices/{invoice}',             [FinanceController::class, 'showInvoice'])->name('finance.invoices.show');
    });

});

// =====================
// CUSTOMER ROUTES
// =====================
Route::middleware(['auth', 'role:owner,admin'])->prefix('customers')->group(function () {
    Route::get('/',                [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/create',          [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/',               [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/{customer}',      [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/{customer}',      [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/{customer}',   [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::get('/import/form',     [CustomerController::class, 'importForm'])->name('customers.import.form');
    Route::post('/import',         [CustomerController::class, 'import'])->name('customers.import');
    Route::get('/export/csv',      [CustomerController::class, 'exportCsv'])->name('customers.export.csv');
});

// API customer search (untuk autocomplete, semua role yg login)
Route::middleware(['auth'])->get('/api/customers/search', [CustomerController::class, 'apiSearch'])
    ->name('customers.api.search');



   
//Tracking publik tanpa login, bisa akses via URL seperti /public/track/NOTA12345
Route::get('/public/track/{nota}', [PublicTrackingController::class, 'show'])
    ->where('nota', '.*');

    use Illuminate\Support\Facades\Storage;

Route::get('/test-r2', function () {
    Storage::disk('s3')->put('test.txt', 'HELLO R2');

    return [
        'exists' => Storage::disk('s3')->exists('test.txt'),
    ];
});


Route::get('/test-r2-public', function () {
    $path = 'test.txt';

    if (!Storage::disk('s3')->exists($path)) {
        return ['ok' => false, 'message' => 'test.txt tidak ada di R2'];
    }

    $url = rtrim(env('AWS_URL', ''), '/') . '/' . $path;

    return [
        'ok' => true,
        'path' => $path,
        'url' => $url,
    ];
});