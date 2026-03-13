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

class FinanceController extends Controller
{
    // =========================
    // FINANCE HOME (manifest list)
    // =========================
    public function index()
    {
        $manifests = Manifest::orderBy('created_at', 'desc')->paginate(10);

        // unpaid label per manifest (hitung via manifest_items -> shipments)
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
        // Ambil shipment_id dari manifest_items
        $ids = ManifestItem::where('manifest_id', $manifest->id)
            ->whereNotNull('shipment_id')
            ->pluck('shipment_id')
            ->toArray();

        $shipments = Shipment::with('items')
            ->whereIn('id', $ids)
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $shipments->count();
        $unpaid = $shipments->where('status_pembayaran', '!=', 'LUNAS')->count();

        return view('finance.manifest', compact('manifest', 'shipments', 'total', 'unpaid'));
    }

    // =========================
    // UPDATE FINANCE SHIPMENT
    // =========================
    public function updateShipmentFinance(Request $request, Shipment $shipment)
    {
        $data = $request->validate([
            'tipe_bayar' => 'required|in:COD,COT',
            'status_pembayaran' => 'required|in:BELUM_BAYAR,LUNAS,PIUTANG,BATAL',
            'bukti_bayar' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        // Rule: COT + LUNAS wajib bukti
        $needProof = ($data['tipe_bayar'] === 'COT' && $data['status_pembayaran'] === 'LUNAS');
        if ($needProof && !$request->hasFile('bukti_bayar') && empty($shipment->bukti_bayar_path)) {
            return back()->with('error', 'COT + LUNAS wajib upload bukti bayar.');
        }

        return DB::transaction(function () use ($request, $shipment, $data) {

            if ($request->hasFile('bukti_bayar')) {
                $path = $request->file('bukti_bayar')->store('bukti-bayar', 'public');
                $shipment->bukti_bayar_path = $path;
            }

            $shipment->tipe_bayar = $data['tipe_bayar'];
            $shipment->status_pembayaran = $data['status_pembayaran'];

            $shipment->paid_at = ($data['status_pembayaran'] === 'LUNAS') ? now() : null;

            $shipment->save();

            return back()->with('success', 'Finance nota berhasil diupdate.');
        });
    }

   
    // ==========================================================
// ✅ PAGE BUAT TAGIHAN (mirip create manifest) - TANPA manifest
// ==========================================================
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



    // ✅ DATA untuk table nota (fix: pakai manifest_items)
    // ==========================================================
// ✅ DATA untuk table nota (filter + exclude yang sudah masuk invoice)
// ==========================================================
public function invoiceData(Request $request)
{
    $q        = trim((string)$request->query('q', ''));
    $tujuan   = trim((string)$request->query('tujuan', ''));
    $penerima = trim((string)$request->query('penerima', ''));
    $sp       = trim((string)$request->query('status_pembayaran', ''));

    $shipments = Shipment::query()
        ->whereNotNull('shipments.manifest_id') // hanya yang sudah masuk manifest
        ->whereNotExists(function($q){
            $q->select(DB::raw(1))
              ->from('invoice_items')
              ->whereColumn('invoice_items.shipment_id', 'shipments.id');
        })
        ->when($q, function($query) use ($q){
            $query->where(function($qq) use ($q){
                $qq->where('shipments.no_nota','like',"%{$q}%")
                   ->orWhere('shipments.nama_pengirim','like',"%{$q}%")
                   ->orWhere('shipments.nama_penerima','like',"%{$q}%")
                   ->orWhere('shipments.tujuan','like',"%{$q}%");
            });
        })
        ->when($tujuan, fn($query)=> $query->where('shipments.tujuan', $tujuan))
        ->when($penerima, fn($query)=> $query->where('shipments.nama_penerima','like',"%{$penerima}%"))
        ->when($sp, fn($query)=> $query->where('shipments.status_pembayaran', $sp))
        ->orderByDesc('shipments.created_at')
        ->limit(200)
        ->get();

    $rows = $shipments->map(function($s){
        return [
            'id' => (int)$s->id,
            'no_nota' => $s->no_nota,
            'penerima' => $s->nama_penerima,
            'tujuan' => $s->tujuan,
            'status_pembayaran' => $s->status_pembayaran,
            'total' => (float)$s->harga_total,
            'manifest_id' => $s->manifest_id,
        ];
    })->values();

    return response()->json([
        'ok' => true,
        'rows' => $rows
    ]);
}




    // =========================
    // ✅ SIMPAN TAGIHAN (create invoice + items)
    // =========================
    // ==========================================================
// ✅ SIMPAN TAGIHAN (tanpa manifest wajib)
// ==========================================================
public function storeInvoice(Request $request)
{
    $data = $request->validate([
        'billed_to'     => 'required|string|max:120',
        'shipment_ids'  => 'required|array|min:1',
        'shipment_ids.*'=> 'numeric',
    ]);

    $billedTo = trim($data['billed_to']);
    $ids = array_values(array_unique(array_map('intval', $data['shipment_ids'])));

    return DB::transaction(function () use ($billedTo, $ids) {

        $shipments = Shipment::query()
            ->whereIn('id', $ids)
            ->whereNotNull('manifest_id')
            ->leftJoin('invoice_items as ii','ii.shipment_id','=','shipments.id')
            ->whereNull('ii.shipment_id')
            ->select('shipments.*')
            ->lockForUpdate()
            ->get();

        if ($shipments->count() === 0) {
            return back()->with('error', 'Nota yang dipilih tidak valid / sudah masuk tagihan lain.');
        }

        $grandTotal = (float)$shipments->sum('harga_total');
        $invoiceNo = $this->generateInvoiceNo($billedTo);

        $invoice = Invoice::create([
            'invoice_no' => $invoiceNo,
            'manifest_id' => null,
            'billed_to' => $billedTo,
            'status' => 'BELUM_DITAGIH',
            'total' => $grandTotal,
        ]);

        foreach ($shipments as $s) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'shipment_id' => $s->id,
                'amount' => (float)$s->harga_total,
            ]);
        }

        return redirect()->route('finance.invoices.list')
            ->with('success', "Tagihan dibuat: {$invoice->invoice_no}");
    });
}



    private function generateInvoiceNo(string $billedTo): string
    {
        // slug subject biar rapi
        $slug = Str::upper(Str::substr(preg_replace('/[^a-zA-Z0-9]+/', '-', $billedTo), 0, 12));
        if (!$slug) $slug = 'CUST';

        // urut per subject (billed_to) sepanjang waktu
        $seq = (int)Invoice::where('billed_to', $billedTo)->count() + 1;

        // format: INV-{SLUG}-{0001}-{YYMM}
        return 'INV-' . $slug . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT) . '-' . now()->format('ym');
    }

    // =========================
    // ✅ DAFTAR TAGIHAN
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

    // Update status tagihan (LUNAS wajib bukti)
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

            $invoice->status = $data['status'];
            $invoice->paid_at = ($data['status'] === 'LUNAS') ? now() : null;
            $invoice->save();

            return back()->with('success', 'Status tagihan diupdate.');
        });
    }

    // PDF Tagihan (per invoice)
    public function invoicePdf(Invoice $invoice)
    {
        $invoice->load(['items.shipment.items']);

        $shipments = $invoice->items->map->shipment;
        $grandTotal = (float)$invoice->total;

        $pdf = Pdf::loadView('finance.tagihan-pdf', [
            'invoice' => $invoice,
            'shipments' => $shipments,
            'grandTotal' => $grandTotal,
        ])->setPaper('A4', 'portrait');

        $safe = str_replace(['/', '\\'], '-', $invoice->invoice_no);

        return $pdf->stream("tagihan-{$safe}.pdf");
    }

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
        ->join('manifest_items','manifest_items.shipment_id','=','shipments.id')
        ->where('manifest_items.manifest_id', $manifest->id)
        ->orderBy('shipments.created_at','desc')
        ->get();

    return response()->json([
        'ok' => true,
        'manifest_id' => $manifest->id,
        'count' => $shipments->count(),
        'data' => $shipments,
    ]);
}

    // =========================
    // KIRIM WA TAGIHAN
    // =========================
    public function sendInvoiceWa(Request $request, Invoice $invoice)
    {
        $request->validate(['telp' => 'required|string']);

        $userCode = env('KIRIMI_USER_CODE');
        $secret   = env('KIRIMI_SECRET');
        $deviceId = env('KIRIMI_DEVICE_ID');

        if (empty($userCode) || empty($secret) || empty($deviceId)) {
            return response()->json(['ok' => false, 'message' => 'Kirimi credentials belum lengkap.'], 500);
        }

        $receiver = $this->normalizePhone($request->telp);
        if (empty($receiver)) {
            return response()->json(['ok' => false, 'message' => 'Nomor telepon tidak valid.'], 422);
        }

        $pdfUrl  = route('finance.invoices.pdf', $invoice);
        $total   = 'Rp ' . number_format((float) $invoice->total, 0, ',', '.');
        $message = "Halo,\n\n"
            . "Berikut *Tagihan / Invoice* dari *Sungai Mas Trans*:\n\n"
            . "📄 *No Tagihan* : {$invoice->invoice_no}\n"
            . "👤 *Ditagihkan* : {$invoice->billed_to}\n"
            . "💰 *Total*      : {$total}\n"
            . "🧾 *Status*     : {$invoice->status}\n\n"
            . "Mohon segera dilakukan pembayaran. Terima kasih.";

        try {
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\n",
                    'content' => json_encode([
                        'user_code'          => $userCode,
                        'secret'             => $secret,
                        'device_id'          => $deviceId,
                        'receiver'           => $receiver,
                        'message'            => $message,
                        'media_url'          => $pdfUrl,
                        'enableTypingEffect' => false,
                    ]),
                    'timeout' => 30,
                ],
            ]);

            $raw  = file_get_contents('https://api.kirimi.id/v1/send-message', false, $ctx);
            $body = $raw ? (array) json_decode($raw, true) : [];

            Log::info('Kirimi invoice WA', ['receiver' => $receiver, 'raw' => $raw]);

            if (!($body['success'] ?? false)) {
                return response()->json(['ok' => false, 'message' => 'Kirimi error: ' . ($body['message'] ?? $raw)], 502);
            }
        } catch (\Throwable $e) {
            Log::error('Invoice WA error', ['err' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 502);
        }

        try {
            $invoice->wa_sent_at = now();
            $invoice->wa_sent_to = $receiver;
            $invoice->save();
        } catch (\Throwable $e) {
            Log::warning('Invoice WA: gagal simpan wa_sent_*', ['err' => $e->getMessage()]);
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Tagihan berhasil dikirim ke WhatsApp.',
            'sent_at' => now()->format('d/m/Y H:i'),
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '08')) return '62' . substr($phone, 1);
        if (str_starts_with($phone, '8'))  return '62' . $phone;
        return $phone;
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
        if (InvoiceItem::where('shipment_id', $shipment->id)->exists()) {
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
    // AVAILABLE SHIPMENTS
    // =========================
    public function availableShipments(Request $request)
    {
        $excludeInvoice = $request->query('invoice_id') ?? $request->query('exclude_invoice');
        $search         = trim((string) $request->query('q', ''));

        $shipments = Shipment::query()
            ->whereNotNull('manifest_id')
            ->whereNotExists(function ($qb) use ($excludeInvoice) {
                $qb->select(DB::raw(1))
                   ->from('invoice_items')
                   ->whereColumn('invoice_items.shipment_id', 'shipments.id')
                   ->when($excludeInvoice, fn ($qq) => $qq->where('invoice_items.invoice_id', '!=', $excludeInvoice));
            })
            ->when($search, function ($qb) use ($search) {
                $qb->where(function ($qq) use ($search) {
                    $qq->where('no_nota',        'like', "%{$search}%")
                       ->orWhere('nama_penerima', 'like', "%{$search}%")
                       ->orWhere('nama_pengirim', 'like', "%{$search}%")
                       ->orWhere('tujuan',        'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'no_nota', 'nama_penerima', 'nama_pengirim', 'tujuan', 'harga_total', 'status_pembayaran']);

        return response()->json(['ok' => true, 'data' => $shipments]);
    }

}