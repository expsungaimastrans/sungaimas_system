<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;


class PublicTrackingController extends Controller
{
    public function show(string $nota): JsonResponse
    {
        $nota = strtoupper(trim($nota));

        $shipment = Shipment::query()
            ->where('shipment_no', $nota)
            ->orWhere('no_nota', $nota)
            ->latest('id')
            ->first();

        if (! $shipment) {
            return response()->json([
                'found' => false,
                'message' => 'Nomor nota tidak ditemukan.',
            ], 404);
        }

        $status = $this->normalizeStatus($shipment->status ?? null);

        return response()->json([
            'found' => true,
            'data' => [
                'tracking_number' => $shipment->shipment_no ?? $shipment->no_nota ?? '-',
                'status' => $status,
                'tujuan' => $shipment->tujuan ?? '-',
                'koli' => $this->extractKoli($shipment->shipment_no ?? $shipment->no_nota ?? ''),
                'lokasi' => $this->resolveLocation($status, $shipment),
                'estimasi_tiba' => $this->resolveEstimatedArrival($status, $shipment),
                'progress' => $this->resolveProgress($status),
                'updated_at' => optional($shipment->updated_at)?->format('d M Y H:i'),
            ],
        ]);
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtoupper(trim((string) $status));

        return match ($status) {
            'DITERIMA' => 'DITERIMA',
            'DALAM PENGIRIMAN' => 'DALAM PENGIRIMAN',
            'SELESAI' => 'SELESAI',
            default => 'DITERIMA',
        };
    }

    private function resolveProgress(string $status): int
    {
        return match ($status) {
            'DITERIMA' => 20,
            'DALAM PENGIRIMAN' => 70,
            'SELESAI' => 100,
            default => 20,
        };
    }

    private function resolveLocation(string $status, Shipment $shipment): string
    {
        return match ($status) {
            'DITERIMA' => 'Gudang Surabaya',
            'DALAM PENGIRIMAN' => 'Dalam Pengiriman',
            'SELESAI' => $shipment->tujuan ?? 'Tujuan',
            default => 'Gudang Surabaya',
        };
    }

    private function resolveEstimatedArrival(string $status, Shipment $shipment): string
    {
        if ($status === 'SELESAI') {
            return optional($shipment->updated_at)?->format('d M Y') ?? '-';
        }

        if (! empty($shipment->tanggal_muat)) {
            try {
                return \Carbon\Carbon::parse($shipment->tanggal_muat)
                    ->addDays(5)
                    ->format('d M Y');
            } catch (\Throwable $e) {
                return 'Menunggu update';
            }
        }

        return 'Menunggu update';
    }

    private function extractKoli(string $nota): string
    {
        if (str_contains($nota, '/')) {
            $parts = explode('/', $nota);
            return $parts[1] ?? '-';
        }

        return '-';
    }
}