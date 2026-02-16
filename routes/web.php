<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\AuthController;

// HOME
Route::get('/', function () {
    return view('welcome');
});


Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// =======================================
// AUTH REQUIRED (SEMUA ROLE)
// =======================================
Route::middleware(['auth'])->group(function () {

    // ✅ OWNER ONLY: Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('role:owner');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->middleware('role:owner');

    // =======================================
    // SHIPMENTS (NOTA)
    // =======================================

    // ✅ Semua role boleh lihat daftar nota (tapi admin nanti dibatasi filter tanggal via step 8)
    Route::get('/shipments', [ShipmentController::class, 'index'])->middleware('role:owner,admin,finance');

    // ✅ Owner + Admin boleh buat/edit nota
    Route::get('/shipments/create', [ShipmentController::class, 'create'])->middleware('role:owner,admin');
    Route::post('/shipments', [ShipmentController::class, 'store'])->middleware('role:owner,admin');

    Route::get('/shipments/{id}/edit', [ShipmentController::class, 'edit'])->middleware('role:owner,admin');
    Route::put('/shipments/{id}', [ShipmentController::class, 'update'])->middleware('role:owner,admin');

    Route::get('/shipments/{id}/pdf', [ShipmentController::class, 'pdf'])->middleware('role:owner,admin,finance');
    Route::get('/shipments/{id}/success', [ShipmentController::class, 'success'])->middleware('role:owner,admin,finance');

    // ✅ Payment status ONLY Finance + Owner
    Route::post('/shipments/{id}/set-pembayaran', [ShipmentController::class, 'setStatusPembayaran'])
        ->middleware('role:owner,finance');

    // ❌ Status pengiriman tidak perlu manual (hapus/disable route kalau masih ada)
    // Route::post('/shipments/{id}/set-pengiriman', [ShipmentController::class, 'setStatusPengiriman']);

    // API manifest picker (owner+admin boleh bikin manifest, finance cuma lihat)
    Route::get('/api/shipments/search', [ShipmentController::class, 'searchJson'])->middleware('role:owner,admin,finance');
    Route::get('/api/shipments/{id}', [ShipmentController::class, 'showJson'])->middleware('role:owner,admin,finance');


    // =======================================
    // MANIFESTS
    // =======================================

    // ✅ Semua boleh lihat daftar manifest (finance butuh untuk finance-by-manifest)
    Route::get('/manifests', [ManifestController::class, 'index'])->middleware('role:owner,admin,finance');

    // ✅ Owner + Admin boleh create/edit manifest
    Route::get('/manifests/create', [ManifestController::class, 'create'])->middleware('role:owner,admin');
    Route::post('/manifests', [ManifestController::class, 'store'])->middleware('role:owner,admin');

    Route::get('/manifests/{id}/edit', [ManifestController::class, 'edit'])->middleware('role:owner,admin');
    Route::put('/manifests/{id}', [ManifestController::class, 'update'])->middleware('role:owner,admin');

    Route::post('/manifests/{manifest}/add/{shipment}', [ManifestController::class, 'addShipment'])->middleware('role:owner,admin');
    Route::delete('/manifests/{manifest}/remove/{shipment}', [ManifestController::class, 'removeShipment'])->middleware('role:owner,admin');

    Route::get('/manifests/{id}/pdf', [ManifestController::class, 'pdf'])->middleware('role:owner,admin,finance');

    // jika /manifests/{id} masih dipakai
    Route::get('/manifests/{id}', [ManifestController::class, 'show'])->middleware('role:owner,admin,finance');


    // =======================================
    // FINANCE (Owner + Finance ONLY)
    // =======================================
    Route::prefix('finance')->middleware('role:owner,finance')->group(function () {
        Route::get('/', [FinanceController::class, 'index']);
        Route::get('/manifest/{manifest}', [FinanceController::class, 'byManifest']);
        Route::post('/shipments/{shipment}/update', [FinanceController::class, 'updateShipmentFinance']);
        Route::post('/invoice/generate', [FinanceController::class, 'generateInvoicePdf']);
    });

});
