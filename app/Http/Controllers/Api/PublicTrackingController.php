<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

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
        $notaDate = optional($shipment->created_at);
        $trackingNumber = $shipment->no_nota ?? '-';

        $koli = '-';
        if (!empty($trackingNumber) && str_contains($trackingNumber, '/')) {
            $parts = explode('/', $trackingNumber);
            $koli = $parts[1] ?? '-';
        }

        // =========================
        // Ambil manifest terkait nota
        // =========================
        $manifest = null;

        // Opsi 1: kalau Shipment punya relasi manifest langsung
        if (method_exists($shipment, 'manifest') && $shipment->manifest) {
            $manifest = $shipment->manifest;
        }

        // Opsi 2: kalau lewat manifest items
        if (!$manifest && method_exists($shipment, 'manifestItems')) {
            $manifestItem = $shipment->manifestItems()->with('manifest')->latest('id')->first();
            if ($manifestItem && $manifestItem->manifest) {
                $manifest = $manifestItem->manifest;
            }
        }

        // Opsi 3: kalau shipment punya kolom manifest_id
        if (
            !$manifest &&
            isset($shipment->manifest_id) &&
            !empty($shipment->manifest_id) &&
            class_exists(\App\Models\Manifest::class)
        ) {
            $manifest = \App\Models\Manifest::find($shipment->manifest_id);
        }

        $driverName = $manifest->sopir ?? '-';
        $departureRaw = $manifest->keberangkatan ?? $manifest->tanggal_muat ?? null;

        $departureDate = null;
        if (!empty($departureRaw)) {
            try {
                $departureDate = Carbon::parse($departureRaw);
            } catch (\Throwable $e) {
                $departureDate = null;
            }
        }

        // =========================
        // Status logic
        // =========================
        $progress = match ($status) {
            'DITERIMA' => 20,
            'DALAM PENGIRIMAN' => 70,
            'SELESAI' => 100,
            default => 20,
        };

        $currentLocation = match ($status) {
            'DITERIMA' => 'Gudang Surabaya — siap dimuat',
            'DALAM PENGIRIMAN' => 'Dalam perjalanan menuju kota tujuan',
            'SELESAI' => 'Barang telah diantarkan',
            default => 'Gudang Surabaya',
        };

        // =========================
        // Keterangan status
        // =========================
        if ($status === 'DITERIMA') {
            $statusDescription = 'Diterima di gudang SungaiMas Surabaya pada tanggal ' .
                ($notaDate ? $notaDate->format('d M Y') : '-');
        } elseif ($status === 'DALAM PENGIRIMAN') {
            $statusDescription = 'Barang anda dibawa oleh sopir ' . ($driverName ?: '-') .
                ' dan keberangkatan kapal tanggal ' . ($departureDate ? $departureDate->format('d M Y') : '-');
        } else {
            $statusDescription = 'Barang anda sudah diantarkan. Harap konfirmasi ke kami apabila belum menerima barang anda. ' .
                'Komplain kerusakan dan kekurangan barang maksimal 1x24 jam terhitung dari tanggal barang diantarkan.';
        }

        // =========================
        // Estimasi tiba 3-4 hari setelah keberangkatan
        // =========================
        $estimateText = 'Menunggu update';
        $countdownText = null;

        if ($departureDate) {
            $etaStart = $departureDate->copy()->addDays(3);
            $etaEnd = $departureDate->copy()->addDays(4);
            $today = now()->startOfDay();

            if ($status === 'SELESAI') {
                $estimateText = 'Selesai / Barang telah diantarkan';
            } else {
                $estimateText = 'Estimasi tiba ' . $etaStart->format('d M Y') . ' - ' . $etaEnd->format('d M Y');

                if ($today->lt($etaStart->copy()->startOfDay())) {
                    $daysLeft = $today->diffInDays($etaStart->copy()->startOfDay(), false);
                    $countdownText = $daysLeft . ' hari lagi menuju estimasi awal kedatangan';
                } elseif ($today->between($etaStart->copy()->startOfDay(), $etaEnd->copy()->endOfDay())) {
                    $countdownText = 'Sedang dalam rentang estimasi kedatangan';
                } elseif ($today->gt($etaEnd->copy()->endOfDay())) {
                    $lateDays = $etaEnd->copy()->startOfDay()->diffInDays($today, false);
                    $countdownText = 'Melewati estimasi sekitar ' . $lateDays . ' hari, silakan hubungi admin untuk update terbaru';
                }
            }
        }

        return response()->json([
            'found' => true,
            'data' => [
                'tracking_number' => $trackingNumber,
                'status' => $status,
                'status_description' => $statusDescription,
                'tujuan' => $shipment->tujuan ?? '-',
                'koli' => $koli,
                'lokasi' => $currentLocation,
                'estimasi_tiba' => $estimateText,
                'countdown_text' => $countdownText,
                'progress' => $progress,
                'sopir' => $driverName,
                'keberangkatan' => $departureDate?->format('d M Y'),
                'tanggal_nota' => $notaDate?->format('d M Y'),
            ],
        ]);
    }
}