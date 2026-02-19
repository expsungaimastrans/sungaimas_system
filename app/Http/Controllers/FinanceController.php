<?php

namespace App\Http\Controllers;

use App\Models\Manifest;
use App\Models\ManifestItem;
use App\Models\Shipment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class FinanceController extends Controller
{
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

    public function byManifest(Manifest $manifest)
    {
        $ids = ManifestItem::where('manifest_id', $manifest->id)
            ->whereNotNull('shipment_id')
            ->pluck('shipment_id')
            ->toArray();

        $shipments = Shipment::with('items')
            ->whereIn('id', $ids)
            ->orderBy('created_at', 'desc')
            ->get();

        $total  = $shipments->count();
        $unpaid = $shipments->where('status_pembayaran', '!=', 'LUNAS')->count();

        return view('finance.manifest', compact('manifest', 'shipments', 'total', 'unpaid'));
    }

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

    public function invoices(Request $request)
    {
        $tujuanOptions = Shipment::select('tujuan')
            ->whereNotNull('tujuan')
            ->where('tujuan', '<>', '')
            ->distinct()
            ->orderBy('tujuan')
            ->pluck('tujuan');

        return view('finance.invoices', ['tujuanOptions' => $tujuanOptions]);
    }

    public function invoiceData(Request $request)
    {
        $q        = trim((string) $request->query('q', ''));
        $tujuan   = trim((string) $request->query('tujuan', ''));
        $penerima = trim((string) $request->query('penerima', ''));
        $sp       = trim((string) $request->query('status_pembayaran', ''));

        // ID yang sudah masuk invoice (pakai kolom shipment_id yang memang ada)
        $alreadyInvoiced = InvoiceItem::pluck('shipment_id')->toArray();

        $shipments = Shipment::query()
            ->whereNotNull('manifest_id')
            ->whereNotIn('id', empty($alreadyInvoiced) ? [0] : $alreadyInvoiced)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('no_nota',       'like', "%{$q}%")
                       ->orWhere('nama_pengirim','like', "%{$q}%")
                       ->orWhere('nama_penerima','like', "%{$q}%")
                       ->orWhere('tujuan',       'like', "%{$q}%");
                });
            })
            ->when($tujuan,   fn ($q) => $q->where('tujuan', $tujuan))
            ->when($penerima, fn ($q) => $q->where('nama_penerima', 'like', "%{$penerima}%"))
            ->when($sp,       fn ($q) => $q->where('status_pembayaran', $sp))
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $rows = $shipments->map(fn ($s) => [
            'id'                => (int) $s->id,
            'no_nota'           => $s->no_nota,
            'penerima'          => $s->nama_penerima,
            'tujuan'            => $s->tujuan,
            'status_pembayaran' => $s->status_pembayaran,
            'total'             => (float) $s->harga_total,
            'manifest_id'       => $s->manifest_id,
        ])->values();

        return response()->json(['ok' => true, 'rows' => $rows]);
    }

    public function storeInvoice(Request $request)
    {
        $data = $request->validate([
            'billed_to'      => 'required|string|max:120',
            'shipment_ids'   => 'required|array|min:1',
            'shipment_ids.*' => 'numeric',
        ]);

        $billedTo = trim($data['billed_to']);
        $ids      = array_values(array_unique(array_map('intval', $data['shipment_ids'])));

        // Exclude yang sudah masuk invoice
        $alreadyInvoiced = InvoiceItem::whereIn('shipment_id', $ids)
            ->pluck('shipment_id')
            ->toArray();

        $validIds = array_values(array_diff($ids, $alreadyInvoiced));

        if (empty($validIds)) {
            return back()->withInput()
                ->with('error', 'Semua nota yang dipilih sudah masuk tagihan lain.');
        }

        try {
            $invoice = DB::transaction(function () use ($billedTo, $validIds) {

                $shipments = Shipment::whereIn('id', $validIds)
                    ->whereNotNull('manifest_id')
                    ->lockForUpdate()
                    ->get();

                if ($shipments->isEmpty()) {
                    throw new \RuntimeException(
                        'Nota tidak ditemukan atau belum masuk manifest. ' .
                        'Pastikan nota sudah dimasukkan ke manifest terlebih dahulu.'
                    );
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

                // Insert invoice items — sesuai kolom DB: no_nota, penerima, tujuan, nilai
                foreach ($shipments as $s) {
                    InvoiceItem::create([
                        'invoice_id'  => $invoice->id,
                        'shipment_id' => $s->id,
                        'no_nota'     => $s->no_nota,
                        'penerima'    => $s->nama_penerima,
                        'tujuan'      => $s->tujuan,
                        'nilai'       => (float) $s->harga_total,
                    ]);
                }

                return $invoice;
            });

        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        // Langsung buka PDF setelah simpan
        return redirect()->route('finance.invoices.pdf', $invoice->id);
    }

    private function generateInvoiceNo(string $billedTo): string
    {
        $slug = Str::upper(
            Str::substr(preg_replace('/[^a-zA-Z0-9]+/', '-', $billedTo), 0, 12)
        );
        $slug = trim($slug, '-');
        if (!$slug) $slug = 'CUST';

        $seq = (int) Invoice::where('billed_to', $billedTo)->count() + 1;

        return 'INV-' . $slug . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT) . '-' . now()->format('ym');
    }

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

    public function updateInvoiceStatus(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'status' => 'required|in:BELUM_DITAGIH,MENUNGGU_PEMBAYARAN,LUNAS',
            'proof'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        if ($data['status'] === 'LUNAS'
            && !$request->hasFile('proof')
            && empty($invoice->payment_proof_path)
        ) {
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

    public function invoicePdf(Invoice $invoice)
    {
        // Load items dengan shipment dan item barangnya
        $invoice->load(['items.shipment.items']);

        // items->shipment bisa diakses via relasi
        $shipments  = $invoice->items->map(fn ($item) => $item->shipment)->filter();
        $grandTotal = (float) $invoice->total;

        $pdf = Pdf::loadView('finance.tagihan-pdf', [
            'invoice'    => $invoice,
            'shipments'  => $shipments,
            'grandTotal' => $grandTotal,
        ])->setPaper('A4', 'portrait');

        $safe = str_replace(['/', '\\'], '-', $invoice->invoice_no);

        return $pdf->stream("tagihan-{$safe}.pdf");
    }

    public function manifestShipmentsJson(Manifest $manifest)
    {
        $shipments = Shipment::select(
                'shipments.id', 'shipments.no_nota', 'shipments.nama_penerima',
                'shipments.tujuan', 'shipments.status_pembayaran', 'shipments.harga_total'
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
}