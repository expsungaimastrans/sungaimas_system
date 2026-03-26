<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;

class PublicTrackingController extends Controller
{
    public function show(string $nota): JsonResponse
    {
        $nota = strtoupper(trim($nota));

        $shipment = Shipment::query()
            ->where('no_nota', $nota)
            ->first();

        if (! $shipment) {
            return response()->json([
                'found' => false,
                'message' => 'Nomor nota tidak ditemukan.',
            ], 404);
        }

        $status = strtoupper(trim((string) ($shipment->status_pengiriman ?? 'DITERIMA')));

        $progress = match ($status) {
            'DITERIMA' => 20,
            'DALAM PENGIRIMAN' => 70,
            'SELESAI' => 100,
            default => 20,
        };

        $lokasi = match ($status) {
            'DITERIMA' => 'Gudang Surabaya',
            'DALAM PENGIRIMAN' => 'Dalam Pengiriman',
            'SELESAI' => $shipment->tujuan ?? 'Tujuan',
            default => 'Gudang Surabaya',
        };

        $estimasi = $status === 'SELESAI'
            ? optional($shipment->updated_at)?->format('d M Y')
            : 'Menunggu update';

        $koli = '-';
        if (! empty($shipment->no_nota) && str_contains($shipment->no_nota, '/')) {
            $parts = explode('/', $shipment->no_nota);
            $koli = $parts[1] ?? '-';
        }

        return response()->json([
            'found' => true,
            'data' => [
                'tracking_number' => $shipment->no_nota,
                'status' => $status,
                'tujuan' => $shipment->tujuan ?? '-',
                'koli' => $koli,
                'lokasi' => $lokasi,
                'estimasi_tiba' => $estimasi,
                'progress' => $progress,
            ],
        ]);
    }
}