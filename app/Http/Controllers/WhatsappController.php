<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsappController extends Controller
{
    /**
     * Kirim nota PDF via Fonnte ke penerima atau pengirim.
     * POST /shipments/{shipment}/send-wa
     * body: { target: 'penerima' | 'pengirim' }
     */
    public function send(Request $request, Shipment $shipment)
    {
        $request->validate([
            'target' => 'required|in:penerima,pengirim',
        ]);

        $target = $request->input('target');

        // ── 1. Tentukan nomor & nama tujuan ──────────────────────────────────
        if ($target === 'penerima') {
            $noHp = $shipment->telp_penerima;
            $nama = $shipment->nama_penerima;
        } else {
            $noHp = $shipment->telp_pengirim;
            $nama = $shipment->nama_pengirim;
        }

        if (empty($noHp)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Nomor HP ' . $target . ' belum diisi.',
            ], 422);
        }

        // ── 2. Normalisasi nomor (08xx → 628xx) ──────────────────────────────
        $receiver = $this->normalizePhone($noHp);

        // ── 3. Generate PDF → upload ke R2/storage ───────────────────────────
        try {
            $pdf = Pdf::loadView('shipments.pdf', compact('shipment'))
                ->setPaper([0, 0, 684, 396], 'portrait')
                ->setOption('margin-top', 6)
                ->setOption('margin-right', 6)
                ->setOption('margin-bottom', 6)
                ->setOption('margin-left', 6);

            $pdfContent = $pdf->output();

            $safeNota = str_replace(['/', '\\'], '-', $shipment->no_nota);
            $filename  = 'nota-wa/' . $safeNota . '-' . Str::random(6) . '.pdf';
            $disk      = config('filesystems.default') === 's3' ? 's3' : 'public';

            Storage::disk($disk)->put($filename, $pdfContent, 'public');

            // URL publik file
            if ($disk === 's3') {
                $pdfUrl = rtrim(config('filesystems.disks.s3.url'), '/') . '/' . $filename;
            } else {
                $pdfUrl = url('storage/' . $filename);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Gagal generate PDF: ' . $e->getMessage(),
            ], 500);
        }

        // ── 4. Susun pesan ────────────────────────────────────────────────────
        $noNota  = $shipment->no_nota;
        $tujuan  = $shipment->tujuan;
        $total   = 'Rp ' . number_format((float) $shipment->harga_total, 0, ',', '.');
        $tanggal = \Carbon\Carbon::parse($shipment->tanggal)->format('d/m/Y');

        $message = "Halo *{$nama}*,\n\n"
                 . "Berikut nota pengiriman dari *Sungai Mas Trans*:\n\n"
                 . "📄 *No Nota* : {$noNota}\n"
                 . "📅 *Tanggal* : {$tanggal}\n"
                 . "📦 *Tujuan*  : {$tujuan}\n"
                 . "💰 *Total*   : {$total}\n\n"
                 . "Terima kasih telah menggunakan jasa kami.\n"
                 . "Info: (031) 3550447 / 081330572008";

        // ── 5. Kirim via Fonnte ───────────────────────────────────────────────
        // Fonnte: POST https://api.fonnte.com/send
        // Header: Authorization: {token}
        // Body (multipart/form-data):
        //   target   = nomor HP
        //  message  = caption / pesan
        //   url      = URL publik file PDF
        //   filename = nama file yang tampil di WA
        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => 'https://api.fonnte.com/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => [
                    'target'      => $receiver,
                    'message'     => $message,
                    'url'         => $pdfUrl,
                    'filename'    => 'Nota-' . $safeNota . '.pdf',
                    'countryCode' => '62',
                ],
                CURLOPT_HTTPHEADER     => [
                    'Authorization: ' . config('services.fonnte.token'),
                ],
            ]);

            $rawResponse = curl_exec($curl);
            $curlError   = curl_error($curl);
            curl_close($curl);

            Log::info('Fonnte response', [
                'receiver' => $receiver,
                'raw'      => $rawResponse,
                'error'    => $curlError,
            ]);

            if ($curlError) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'CURL error: ' . $curlError,
                ], 502);
            }

            $body   = (array) json_decode($rawResponse, true);
            $status = (bool) ($body['status'] ?? false);

            if (!$status) {
                $errMsg = (string) ($body['reason'] ?? $body['message'] ?? $rawResponse);
                return response()->json([
                    'ok'      => false,
                    'message' => 'Fonnte error: ' . $errMsg,
                    'debug'   => $rawResponse,
                ], 502);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Gagal menghubungi Fonnte API: ' . $e->getMessage(),
            ], 502);
        }

        // ── 6. Catat waktu kirim ──────────────────────────────────────────────
        $col        = $target === 'penerima' ? 'wa_penerima_sent_at' : 'wa_pengirim_sent_at';
        $shipment->$col = now();
        $shipment->save();

        return response()->json([
            'ok'      => true,
            'message' => 'Nota berhasil dikirim ke WhatsApp ' . $target . '.',
            'sent_at' => $shipment->$col->format('d/m/Y H:i'),
        ]);
    }

    // ── Helper: normalisasi nomor HP ke format 628xx ──────────────────────────
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '08')) {
            return '62' . substr($phone, 1);
        }
        if (str_starts_with($phone, '8')) {
            return '62' . $phone;
        }
        return $phone;
    }
}