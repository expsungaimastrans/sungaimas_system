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
    public function send(Request $request, Shipment $shipment)
    {
        $request->validate([
            'target' => 'required|in:penerima,pengirim',
        ]);

        $target = $request->input('target');

        // ── 1. Tentukan nomor & nama ──────────────────────────────────────────
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

        $receiver = $this->normalizePhone($noHp);

        // ── 2. Validasi credentials ───────────────────────────────────────────
        $userCode = (string) config('services.kirimi.user_code');
        $secret   = (string) config('services.kirimi.secret');
        $deviceId = (string) config('services.kirimi.device_id');

        if (empty($userCode) || empty($secret) || empty($deviceId)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Kirimi credentials belum lengkap di environment variables.',
            ], 500);
        }

        // ── 3. Generate PDF → upload ke storage ───────────────────────────────
        try {
            $pdf = Pdf::loadView('shipments.pdf', compact('shipment'))
                ->setPaper([0, 0, 684, 396], 'portrait')
                ->setOption('margin-top', 6)
                ->setOption('margin-right', 6)
                ->setOption('margin-bottom', 6)
                ->setOption('margin-left', 6);

            $pdfContent = $pdf->output();
            $safeNota   = str_replace(['/', '\\'], '-', $shipment->no_nota);
            $filename   = 'nota-wa/' . $safeNota . '-' . Str::random(6) . '.pdf';
            $disk       = config('filesystems.default') === 's3' ? 's3' : 'public';

            Storage::disk($disk)->put($filename, $pdfContent, 'public');

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

        // ── 5. Kirim via Kirimi /v1/send-message + media_url ─────────────────
        try {
            $payload = [
                'user_code'          => $userCode,
                'secret'             => $secret,
                'device_id'          => $deviceId,
                'receiver'           => $receiver,
                'message'            => $message,
                'media_url'          => $pdfUrl,
                'enableTypingEffect' => false,
            ];

            $context = stream_context_create([
                'http' => [
                    'header'  => "Content-Type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode($payload),
                    'timeout' => 30,
                ],
            ]);

            $rawResponse = file_get_contents('https://api.kirimi.id/v1/send-message', false, $context);

            Log::info('Kirimi response', [
                'receiver' => $receiver,
                'raw'      => $rawResponse,
            ]);

            if ($rawResponse === false) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Gagal menghubungi Kirimi API.',
                ], 502);
            }

            $body    = (array) json_decode($rawResponse, true);
            $success = (bool) ($body['success'] ?? false);

            if (!$success) {
                $errMsg = (string) ($body['message'] ?? $rawResponse);
                return response()->json([
                    'ok'      => false,
                    'message' => 'Kirimi error: ' . $errMsg,
                    'debug'   => $rawResponse,
                ], 502);
            }

        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Gagal menghubungi Kirimi API: ' . $e->getMessage(),
            ], 502);
        }

        // ── 6. Catat waktu kirim ──────────────────────────────────────────────
        $col            = $target === 'penerima' ? 'wa_penerima_sent_at' : 'wa_pengirim_sent_at';
        $shipment->$col = now();
        $shipment->save();

        return response()->json([
            'ok'      => true,
            'message' => 'Nota berhasil dikirim ke WhatsApp ' . $target . '.',
            'sent_at' => $shipment->$col->format('d/m/Y H:i'),
        ]);
    }

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