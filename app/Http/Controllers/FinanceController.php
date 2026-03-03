<?php

namespace App\Http\Controllers;

use App\Models\Manifest;
use App\Models\ManifestItem;
use App\Models\Shipment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Filesystem\FilesystemAdapter;

class FinanceController extends Controller
{
    // =========================
    // FINANCE HOME (manifest list)
    // =========================
    public function index()
    {
        $manifests = Manifest::orderBy('created_at', 'desc')->paginate(10);

        $stats = DB::table('manifest_items as mi')
            ->join('shipments as s', 's.id', '=', 'mi.shipment_id')
            ->selectRaw('mi.manifest_id as manifest_id, COUNT(*) as total, SUM(CASE WHEN s.status_pembayaran <> "LUNAS" THEN 1 ELSE 0 END) as unpaid')
            ->groupBy('mi.manifest_id')
            ->get()
            ->keyBy('manifest_id');

        return view('finance.index', [
            'manifests' => $manifests,
            'unpaidMap' => $stats,
        ]);
    }

    // =========================
    // FINANCE BY MANIFEST (kelola)
    // =========================
    public function byManifest(Manifest $manifest)
    {
        $ids = ManifestItem::where('manifest_id', $manifest->id)
            ->whereNotNull('shipment_id')
            ->pluck('shipment_id')
            ->toArray();

        $shipments = Shipment::with('items')
            ->whereIn('id', $ids)
            ->get();

        $jalurOrder = [
            'labuan bajo' => 1, 'labuanbajo' => 1,
            'lembor'      => 2,
            'ruteng'      => 3,
            'borong'      => 4,
            'aimere'      => 5, 'aimire' => 5,
            'cancar'      => 6,
            'bajawa'      => 7,
            'soa'         => 8,
            'bowae'       => 9,
            'mbay'        => 10, 'nagekeo' => 10,
            'mataloko'    => 11,
            'ende'        => 12,
            'riung'       => 13,
            'raja'        => 14,
        ];

        $shipments = $shipments->sortBy(function ($s) use ($jalurOrder) {
            $tujuan = strtolower(trim($s->tujuan ?? ''));
            $order  = 99;
            foreach ($jalurOrder as $key => $val) {
                if (str_contains($tujuan, $key)) {
                    $order = $val;
                    break;
                }
            }
            return sprintf('%02d_%s', $order, strtolower($s->nama_penerima ?? ''));
        })->values();

        $total  = $shipments->count();
        $unpaid = $shipments->where('status_pembayaran', '!=', 'LUNAS')->count();

        return view('finance.manifest', compact('manifest', 'shipments', 'total', 'unpaid'));
    }

    // =========================
    // UPDATE FINANCE SHIPMENT
    // =========================
    public function updateShipmentFinance(Request $request, Shipment $shipment)
    {
        $data = $request->validate([
            'tipe_bayar'        => 'required|in:COD,COT',
            'status_pembayaran' => 'required|in:BELUM_BAYAR,LUNAS,PIUTANG,BATAL',
            'bukti_bayar'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $needProof = ($data['tipe_bayar'] === 'COT' && $data['status_pembayaran'] === 'LUNAS');
        if ($needProof && !$request->hasFile('bukti_bayar') && empty($shipment->bukti_bayar_path)) {
            return back()->with('error', 'COT + LUNAS wajib upload bukti bayar.');
        }

        return DB::transaction(function () use ($request, $shipment, $data) {
            if ($request->hasFile('bukti_bayar')) {
                $path = $request->file('bukti_bayar')->store('bukti-bayar', 'public');
                $shipment->bukti_bayar_path = $path;
            }

            $shipment->tipe_bayar        = $data['tipe_bayar'];
            $shipment->status_pembayaran = $data['status_pembayaran'];
            $shipment->paid_at           = ($data['status_pembayaran'] === 'LUNAS') ? now() : null;
            $shipment->save();

            return back()->with('success', 'Finance nota berhasil diupdate.');
        });
    }

    // =========================
    // PAGE BUAT TAGIHAN
    // =========================
    public function invoices(Request $request)
    {
        $tujuanOptions = Shipment::query()
            ->select('tujuan')
            ->whereNotNull('tujuan')
            ->where('tujuan', '<>', '')
            ->distinct()
            ->orderBy('tujuan')
            ->pluck('tujuan');

        return view('finance.invoices', [
            'tujuanOptions' => $tujuanOptions,
        ]);
    }

    // =========================
    // DATA NOTA UNTUK TAGIHAN
    // =========================
    public function invoiceData(Request $request)
    {
        $q        = trim((string) $request->query('q', ''));
        $tujuan   = trim((string) $request->query('tujuan', ''));
        $penerima = trim((string) $request->query('penerima', ''));
        $sp       = trim((string) $request->query('status_pembayaran', ''));

        $shipments = Shipment::query()
            ->where(function ($q) {
                $q->whereNotNull('shipments.manifest_id')
                    ->orWhere('shipments.status_pengiriman', 'DALAM_PENGIRIMAN');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('invoice_items')
                    ->whereColumn('invoice_items.shipment_id', 'shipments.id');
            })
            ->when($q, function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('shipments.no_nota', 'like', "%{$q}%")
                        ->orWhere('shipments.nama_pengirim', 'like', "%{$q}%")
                        ->orWhere('shipments.nama_penerima', 'like', "%{$q}%")
                        ->orWhere('shipments.tujuan', 'like', "%{$q}%");
                });
            })
            ->when($tujuan, fn($query) => $query->where('shipments.tujuan', $tujuan))
            ->when($penerima, fn($query) => $query->where('shipments.nama_penerima', 'like', "%{$penerima}%"))
            ->when($sp, fn($query) => $query->where('shipments.status_pembayaran', $sp))
            ->orderByDesc('shipments.created_at')
            ->limit(200)
            ->get();

        $rows = $shipments->map(function ($s) {
            return [
                'id'                => (int) $s->id,
                'no_nota'           => $s->no_nota,
                'penerima'          => $s->nama_penerima,
                'tujuan'            => $s->tujuan,
                'status_pembayaran' => $s->status_pembayaran,
                'total'             => (float) $s->harga_total,
                'manifest_id'       => $s->manifest_id,
            ];
        })->values();

        return response()->json(['ok' => true, 'rows' => $rows]);
    }

    // =========================
    // SIMPAN TAGIHAN
    // =========================
    public function storeInvoice(Request $request)
    {
        $data = $request->validate([
            'billed_to'      => 'required|string|max:120',
            'shipment_ids'   => 'required|array|min:1',
            'shipment_ids.*' => 'numeric',
        ]);

        $billedTo = trim($data['billed_to']);
        $ids      = array_values(array_unique(array_map('intval', $data['shipment_ids'])));

        return DB::transaction(function () use ($billedTo, $ids) {

            $shipments = Shipment::query()
                ->whereIn('shipments.id', $ids)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('invoice_items')
                        ->whereColumn('invoice_items.shipment_id', 'shipments.id');
                })
                ->lockForUpdate()
                ->get();

            if ($shipments->count() === 0) {
                return back()->with('error', 'Nota yang dipilih tidak valid / sudah masuk tagihan lain.');
            }

            $grandTotal = (float) $shipments->sum('harga_total');
            $invoiceNo  = $this->generateInvoiceNo($billedTo);

            $invoice = Invoice::create([
                'invoice_no'  => $invoiceNo,
                'manifest_id' => null,
                'billed_to'   => $billedTo,
                'status'      => 'BELUM_DITAGIH',
                'total'       => $grandTotal,
            ]);

            foreach ($shipments as $s) {
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'shipment_id' => $s->id,
                    'no_nota'     => $s->no_nota,
                    'penerima'    => $s->nama_penerima,
                    'tujuan'      => $s->tujuan,
                    'nilai'       => (float) $s->harga_total,
                    'amount'      => (float) $s->harga_total,
                ]);
            }

            return redirect()->route('finance.invoices.list')
                ->with('success', "Tagihan dibuat: {$invoice->invoice_no}");
        });
    }

    // =========================
    // GENERATE INVOICE NO
    // =========================
    private function generateInvoiceNo(string $billedTo): string
    {
        $slug = Str::upper(Str::substr(preg_replace('/[^a-zA-Z0-9]+/', '-', $billedTo), 0, 12));
        if (!$slug) $slug = 'CUST';

        $seq = (int) Invoice::where('billed_to', $billedTo)->count() + 1;

        return 'INV-' . $slug . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT) . '-' . now()->format('ym');
    }

    // =========================
    // DAFTAR TAGIHAN
    // =========================
    public function listInvoices()
    {
        $invoices = Invoice::withCount('items')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('finance.invoices_list', compact('invoices'));
    }

    public function showInvoice(Invoice $invoice)
    {
        $invoice->load(['items.shipment']);
        return view('finance.invoice_show', compact('invoice'));
    }

    // =========================
    // UPDATE STATUS TAGIHAN
    // =========================
    public function updateInvoiceStatus(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'status' => 'required|in:BELUM_DITAGIH,MENUNGGU_PEMBAYARAN,LUNAS',
            'proof'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $needProof = ($data['status'] === 'LUNAS');
        if ($needProof && !$request->hasFile('proof') && empty($invoice->payment_proof_path)) {
            return back()->with('error', 'Jika status LUNAS wajib upload bukti pembayaran.');
        }

        return DB::transaction(function () use ($request, $invoice, $data) {
            if ($request->hasFile('proof')) {
                $path = $request->file('proof')->store('bukti-tagihan', 'public');
                $invoice->payment_proof_path = $path;
            }

            $invoice->status  = $data['status'];
            $invoice->paid_at = ($data['status'] === 'LUNAS') ? now() : null;
            $invoice->save();

            return back()->with('success', 'Status tagihan diupdate.');
        });
    }

    // =========================
    // PDF TAGIHAN (stream untuk user login)
    // =========================
    public function invoicePdf(Invoice $invoice)
    {
        $invoice->load(['items.shipment.items']);

        $shipments  = $invoice->items->map->shipment;
        $grandTotal = (float) $invoice->total;

        $pdf = Pdf::loadView('finance.tagihan-pdf', [
            'invoice'    => $invoice,
            'shipments'  => $shipments,
            'grandTotal' => $grandTotal,
        ])->setPaper('A4', 'portrait');

        $safe = str_replace(['/', '\\'], '-', $invoice->invoice_no);

        return $pdf->stream("tagihan-{$safe}.pdf");
    }

    // =========================
    // KIRIM WA TAGIHAN (R2 + Kirimi)
    // =========================
    public function sendInvoiceWa(Request $request, Invoice $invoice)
    {
        $request->validate([
            'telp' => ['required', 'string', 'min:8', 'max:30'],
        ]);

        $receiver = $this->normalizeWaNumber($request->telp);
        if (!$receiver) {
            return back()->with('error', 'Nomor WhatsApp tidak valid. Gunakan 08xxxx atau 62xxxx.');
        }

        try {
            $mediaUrl = $this->ensureInvoicePdfOnR2AndGetUrl($invoice);
        } catch (\Throwable $e) {
            Log::error('Invoice PDF R2 error', ['invoice_id' => $invoice->id, 'err' => $e->getMessage()]);
            return back()->with('error', 'Gagal siapkan PDF invoice: ' . $e->getMessage());
        }

        $userCode = config('services.kirimi.user_code') ?: env('KIRIMI_USER_CODE');
        $secret   = config('services.kirimi.secret') ?: env('KIRIMI_SECRET');
        $deviceId = config('services.kirimi.device_id') ?: env('KIRIMI_DEVICE_ID');

        if (!$userCode || !$secret || !$deviceId) {
            return back()->with('error', 'Konfigurasi Kirimi belum lengkap (KIRIMI_USER_CODE/SECRET/DEVICE_ID).');
        }

        $total = number_format((float) $invoice->total, 0, ',', '.');
        $message =
            "Halo, berikut *Tagihan / Invoice* dari Sungai Mas Trans.\n" .
            "No: *{$invoice->invoice_no}*\n" .
            "Kepada: *{$invoice->billed_to}*\n" .
            "Total: *Rp {$total}*\n\n" .
            "PDF tagihan terlampir. Terima kasih.";

        try {
            /** @var Response $resp */
            $resp = Http::timeout(30)
                ->acceptJson()
                ->asJson()
                ->post('https://kirimi.id/api/v1/send-message', [
                    'user_code' => $userCode,
                    'secret'    => $secret,
                    'device_id' => $deviceId,
                    'receiver'  => $receiver,
                    'message'   => $message,
                    'media_url' => $mediaUrl,
                    'enableTypingEffect' => false,
                ]);

            $json = $resp->json();

            Log::info('Kirimi send invoice', [
                'status'     => $resp->status(),
                'json'       => $json,
                'invoice_id' => $invoice->id,
                'receiver'   => $receiver,
                'media_url'  => $mediaUrl,
            ]);

            if (!$resp->ok()) {
                $msg = is_array($json) ? ($json['message'] ?? $json['error'] ?? $resp->body()) : $resp->body();
                return back()->with('error', 'Gagal kirim WA: ' . $msg);
            }

            if (!($json['success'] ?? false)) {
                $msg = $json['message'] ?? 'Unknown error (Kirimi)';
                return back()->with('error', 'Gagal kirim WA: ' . $msg);
            }

            $invoice->wa_sent_at = now();
            $invoice->wa_sent_to = $receiver;
            $invoice->save();

            return back()->with('success', 'Invoice berhasil dikirim via WhatsApp.');
        } catch (\Throwable $e) {
            Log::error('Kirimi exception send invoice', ['invoice_id' => $invoice->id, 'err' => $e->getMessage()]);
            return back()->with('error', 'Gagal kirim WA: ' . $e->getMessage());
        }
    }

    /**
     * Meniru pola "kirim nota": PDF dibuat -> upload ke R2 -> ambil URL publik/presigned.
     */
    private function ensureInvoicePdfOnR2AndGetUrl(Invoice $invoice): string
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('r2');

        $safeNo = preg_replace('/[^a-zA-Z0-9\-_\.]/', '-', (string) $invoice->invoice_no);
        if (!$safeNo) $safeNo = 'invoice-' . $invoice->id;

        $path = "invoices/{$safeNo}.pdf";

        if (!$disk->exists($path)) {
            // Generate PDF pakai view yang sama dengan invoicePdf()
            $invoice->load(['items.shipment.items']);
            $shipments  = $invoice->items->map->shipment;
            $grandTotal = (float) $invoice->total;

            $pdfBinary = Pdf::loadView('finance.tagihan-pdf', [
                'invoice'    => $invoice,
                'shipments'  => $shipments,
                'grandTotal' => $grandTotal,
            ])->setPaper('A4', 'portrait')->output();

            $disk->put($path, $pdfBinary, [
                'visibility'   => 'private',
                'ContentType'  => 'application/pdf',
                'CacheControl' => 'no-cache',
            ]);
        }

        // presigned URL (lebih aman)
        if (method_exists($disk, 'temporaryUrl')) {
            return $disk->temporaryUrl($path, now()->addMinutes(15));
        }

        // fallback jika bucket public
        return $disk->url($path);
    }

    private function normalizeWaNumber(string $input): ?string
    {
        $p = preg_replace('/\D+/', '', trim($input));
        if (!$p) return null;

        if (str_starts_with($p, '08')) return '62' . substr($p, 1);
        if (str_starts_with($p, '8'))  return '62' . $p;
        if (str_starts_with($p, '62')) return $p;

        return null;
    }

    // =========================
    // EDIT INVOICE
    // =========================
    public function editInvoice(Invoice $invoice)
    {
        $invoice->load(['items.shipment']);
        return view('finance.invoice_edit', compact('invoice'));
    }

    public function addShipmentToInvoice(Invoice $invoice, Shipment $shipment)
    {
        $alreadyIn = InvoiceItem::where('shipment_id', $shipment->id)->exists();
        if ($alreadyIn) {
            return back()->with('error', 'Nota sudah masuk tagihan lain.');
        }

        InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'shipment_id' => $shipment->id,
            'no_nota'     => $shipment->no_nota,
            'penerima'    => $shipment->nama_penerima,
            'tujuan'      => $shipment->tujuan,
            'nilai'       => (float) $shipment->harga_total,
            'amount'      => (float) $shipment->harga_total,
        ]);

        $invoice->total = (float) $invoice->items()->sum('amount');
        $invoice->save();

        return back()->with('success', 'Nota berhasil ditambahkan ke tagihan.');
    }

    public function removeShipmentFromInvoice(Invoice $invoice, Shipment $shipment)
    {
        InvoiceItem::where('invoice_id', $invoice->id)
            ->where('shipment_id', $shipment->id)
            ->delete();

        $invoice->total = (float) $invoice->items()->sum('amount');
        $invoice->save();

        return back()->with('success', 'Nota berhasil dihapus dari tagihan.');
    }

    // =========================
    // MANIFEST SHIPMENTS JSON
    // =========================
    public function manifestShipmentsJson(Manifest $manifest)
    {
        $shipments = Shipment::query()
            ->select(
                'shipments.id',
                'shipments.no_nota',
                'shipments.nama_penerima',
                'shipments.tujuan',
                'shipments.status_pembayaran',
                'shipments.harga_total'
            )
            ->join('manifest_items', 'manifest_items.shipment_id', '=', 'shipments.id')
            ->where('manifest_items.manifest_id', $manifest->id)
            ->orderBy('shipments.created_at', 'desc')
            ->get();

        return response()->json([
            'ok'          => true,
            'manifest_id' => $manifest->id,
            'count'       => $shipments->count(),
            'data'        => $shipments,
        ]);
    }

    // =========================
    // AVAILABLE SHIPMENTS
    // =========================
    public function availableShipments(Request $request)
    {
        $excludeInvoice = $request->query('exclude_invoice');

        $shipments = Shipment::query()
            ->whereNotExists(function ($q) use ($excludeInvoice) {
                $q->select(DB::raw(1))
                    ->from('invoice_items')
                    ->whereColumn('invoice_items.shipment_id', 'shipments.id')
                    ->when($excludeInvoice, fn($qq) => $qq->where('invoice_items.invoice_id', '!=', $excludeInvoice));
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'no_nota', 'nama_penerima', 'tujuan', 'harga_total', 'status_pembayaran']);

        return response()->json(['ok' => true, 'data' => $shipments]);
    }
}