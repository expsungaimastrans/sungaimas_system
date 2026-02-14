<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\ManifestController;

// =======================================================================
// HOME
// =======================================================================
Route::redirect('/', '/dashboard');



// =======================================================================
// SHIPMENTS (NOTA)
// =======================================================================
Route::get('/shipments', [ShipmentController::class, 'index']);
Route::get('/shipments/create', [ShipmentController::class, 'create']);
Route::post('/shipments', [ShipmentController::class, 'store']);

Route::get('/shipments/{id}/success', [ShipmentController::class, 'success']);
Route::get('/shipments/{id}/pdf', [ShipmentController::class, 'pdf']);

Route::get('/shipments/{id}/edit', [ShipmentController::class, 'edit']);
Route::put('/shipments/{id}', [ShipmentController::class, 'update']);

// quick update status
Route::post('/shipments/{id}/set-pengiriman', [ShipmentController::class, 'setStatusPengiriman']);
Route::post('/shipments/{id}/set-pembayaran', [ShipmentController::class, 'setStatusPembayaran']);

// API (JSON) untuk manifest picker
Route::get('/api/shipments/search', [ShipmentController::class, 'searchJson']);
Route::get('/api/shipments/{id}', [ShipmentController::class, 'showJson']);


// =======================================================================
// MANIFESTS
// =======================================================================

// daftar manifest
Route::get('/manifests', [ManifestController::class, 'index']);

// buat manifest baru
Route::get('/manifests/create', [ManifestController::class, 'create']);
Route::post('/manifests', [ManifestController::class, 'store']);

// cetak PDF manifest (taruh sebelum {id})
Route::get('/manifests/{id}/pdf', [ManifestController::class, 'pdf']);

// edit manifest (taruh sebelum {id})
Route::get('/manifests/{id}/edit', [ManifestController::class, 'edit']);
Route::put('/manifests/{id}', [ManifestController::class, 'update']);

// AJAX add/remove shipment di edit manifest
Route::post('/manifests/{manifest}/add/{shipment}', [ManifestController::class, 'addShipment']);
Route::delete('/manifests/{manifest}/remove/{shipment}', [ManifestController::class, 'removeShipment']);

// detail manifest (taruh paling bawah)
Route::get('/manifests/{id}', [ManifestController::class, 'show']);


use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/dashboard/data', [DashboardController::class, 'data']); // untuk update chart interaktif

Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
Route::get('/shipments/export/csv', [ShipmentController::class, 'exportCsv'])->name('shipments.export.csv');




