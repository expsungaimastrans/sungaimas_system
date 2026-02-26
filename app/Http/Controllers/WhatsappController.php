<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsappController extends Controller
{
    /**
     * Kirim nota PDF via Kirimi.id ke penerima atau pengirim.
     * POST /shipments/{shipment}/send-wa
     * body: { target: 'penerima' | 'pengirim' }
     */
    public function send(Request $request, Shipment $shipment)
    {
        $request->validate([
            'target' => 'required|in:penerima,pengirim',
        ]);

        $target = $request->input('target'); // 'penerima' atau 'pengirim'

        // ── 1. Tentukan nomor tujuan ─────────────────────────────────────────
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

        // ── 2. Normalisasi nomor (08xx → 628xx) ─────────────────────────────
        $receiver = $this->normalizePhone($noHp);

        // ── 3. Generate PDF nota → simpan sementara ke R2 ───────────────────
        try {
            $pdf     = Pdf::loadView('shipments.pdf', compact('shipment'))
                          ->setPaper([0, 0, 684, 396], 'portrait')
                          ->setOption('margin-top', 6)
                          ->setOption('margin-right', 6)
                          ->setOption('margin-bottom', 6)
                          ->setOption('margin-left', 6);

            $pdfContent = $pdf->output();

            // Simpan ke R2 dengan nama unik
            $filename  = 'nota-wa/' . $shipment->no_nota . '-' . Str::random(8) . '.pdf';
            $disk      = config('filesystems.default') === 's3' ? 's3' : 'public';
            Storage::disk($disk)->put($filename, $pdfContent, 'public');

            // Ambil URL publik
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

        // ── 4. Susun pesan ───────────────────────────────────────────────────
        $noNota  = $shipment->no_nota;
        $tujuan  = $shipment->tujuan;
        $total   = 'Rp ' . number_format((float)$shipment->harga_total, 0, ',', '.');
        $tanggal = \Carbon\Carbon::parse($shipment->tanggal)->format('d/m/Y');

        $message = "Halo *{$nama}*,\n\n"
                 . "Berikut nota pengiriman dari *Sungai Mas Trans*:\n\n"
                 . "📄 *No Nota* : {$noNota}\n"
                 . "📅 *Tanggal* : {$tanggal}\n"
                 . "📦 *Tujuan*  : {$tujuan}\n"
                 . "💰 *Total*   : {$total}\n\n"
                 . "Terima kasih telah menggunakan jasa kami.\n"
                 . "Info: (031) 3550447 / 081330572008";

        // ── 5. Kirim via Kirimi.id ───────────────────────────────────────────
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(30)
                ->asJson()  // kirim sebagai JSON bukan form
                ->post('https://api.kirimi.id/v1/send-document', [
                    'user_code' => config('services.kirimi.user_code'),
                    'secret'    => config('services.kirimi.secret'),
                    'device_id' => config('services.kirimi.device_id'),
                    'receiver'  => $receiver,
                    'message'   => $message,
                    'media'     => $pdfUrl,
                ]);

            $statusCode = $response->status();
            $rawBody    = $response->body();
            $body       = (array) $response->json();
            $bodyStatus = (string) ($body['status'] ?? '');

            // Log full response untuk debug
            \Illuminate\Support\Facades\Log::info('Kirimi response', [
                'status_code' => $statusCode,
                'body'        => $rawBody,
            ]);

            if ($statusCode >= 400 || $bodyStatus === 'error') {
                // Tampilkan full body agar mudah debug
                $errMsg = (string) ($body['message'] ?? $rawBody);
                return response()->json([
                    'ok'      => false,
                    'message' => 'Kirimi API error: ' . $errMsg,
                    'debug'   => [
                        'http_status' => $statusCode,
                        'raw'         => $rawBody,
                    ],
                ], 502);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Gagal menghubungi Kirimi API: ' . $e->getMessage(),
            ], 502);
        }

        // ── 6. Catat waktu kirim di DB ───────────────────────────────────────
        $col = $target === 'penerima' ? 'wa_penerima_sent_at' : 'wa_pengirim_sent_at';
        $shipment->$col = now();
        $shipment->save();

        return response()->json([
            'ok'      => true,
            'message' => 'Nota berhasil dikirim ke WhatsApp ' . $target . '.',
            'sent_at' => $shipment->$col->format('d/m/Y H:i'),
        ]);
    }

    // ── Helper: normalisasi nomor HP ─────────────────────────────────────────
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone); // hapus non-digit
        if (str_starts_with($phone, '08')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }
        return $phone;
    }
}