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

        return view('finance.index', ['manifests' => $manifests, 'unpaidMap' => $stats]);
    }

    public function byManifest(Manifest $manifest)
    {
        $ids = ManifestItem::where('manifest_id', $manifest->id)
            ->whereNotNull('shipment_id')->pluck('shipment_id')->toArray();

        $shipments = Shipment::with('items')->whereIn('id', $ids)
            ->orderBy('created_at', 'desc')->get();

        return view('finance.manifest', [
            'manifest' => $manifest,
            'shipments' => $shipments,
            'total'  => $shipments->count(),
            'unpaid' => $shipments->where('status_pembayaran', '!=', 'LUNAS')->count(),
        ]);
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
                $shipment->bukti_bayar_path = $request->file('bukti_bayar')->store('bukti-bayar', 'public');
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
            ->whereNotNull('tujuan')->where('tujuan', '<>', '')
            ->distinct()->orderBy('tujuan')->pluck('tujuan');

        return view('finance.invoices', ['tujuanOptions' => $tujuanOptions]);
    }

    public function invoiceData(Request $request)
    {
        $q        = trim((string) $request->query('q', ''));
        $tujuan   = trim((string) $request->query('tujuan', ''));
        $penerima = trim((string) $request->query('penerima', ''));
        $sp       = trim((string) $request->query('status_pembayaran', ''));

        $alreadyInvoiced = InvoiceItem::pluck('shipment_id')->toArray();

        $shipments = Shipment::query()
            ->whereNotNull('manifest_id')
            ->whereNotIn('id', empty($alreadyInvoiced) ? [0] : $alreadyInvoiced)
            ->when($q, fn ($q2, $v) => $q2->where(fn ($qq) => $qq
                ->where('no_nota',        'like', "%{$v}%")
                ->orWhere('nama_pengirim', 'like', "%{$v}%")
                ->orWhere('nama_penerima', 'like', "%{$v}%")
                ->orWhere('tujuan',        'like', "%{$v}%")))
            ->when($tujuan,   fn ($q2) => $q2->where('tujuan', $tujuan))
            ->when($penerima, fn ($q2) => $q2->where('nama_penerima', 'like', "%{$penerima}%"))
            ->when($sp,       fn ($q2) => $q2->where('status_pembayaran', $sp))
            ->orderByDesc('created_at')->limit(200)->get();

        return response()->json(['ok' => true, 'rows' => $shipments->map(fn ($s) => [
            'id'                => (int) $s->id,
            'no_nota'           => $s->no_nota,
            'penerima'          => $s->nama_penerima,
            'tujuan'            => $s->tujuan,
            'status_pembayaran' => $s->status_pembayaran,
            'total'             => (float) $s->harga_total,
            'manifest_id'       => $s->manifest_id,
        ])->values()]);
    }

    private function createInvoiceItem(array $data): InvoiceItem
    {
        $nilai = (float) ($data['nilai'] ?? 0);
        return InvoiceItem::create([
            'invoice_id'  => $data['invoice_id'],
            'shipment_id' => $data['shipment_id'],
            'no_nota'     => $data['no_nota']  ?? null,
            'penerima'    => $data['penerima'] ?? null,
            'tujuan'      => $data['tujuan']   ?? null,
            'nilai'       => $nilai,
        ]);
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

        $alreadyInvoiced = InvoiceItem::whereIn('shipment_id', $ids)->pluck('shipment_id')->toArray();
        $validIds = array_values(array_diff($ids, $alreadyInvoiced));

        if (empty($validIds)) {
            return back()->withInput()->with('error', 'Semua nota yang dipilih sudah masuk tagihan lain.');
        }

        try {
            $invoice = DB::transaction(function () use ($billedTo, $validIds) {
                $shipments = Shipment::whereIn('id', $validIds)
                    ->whereNotNull('manifest_id')->lockForUpdate()->get();

                if ($shipments->isEmpty()) {
                    throw new \RuntimeException('Nota tidak ditemukan atau belum masuk manifest.');
                }

                $invoice = Invoice::create([
                    'invoice_no'  => $this->generateInvoiceNo($billedTo),
                    'manifest_id' => null,
                    'billed_to'   => $billedTo,
                    'status'      => 'BELUM_DITAGIH',
                    'total'       => (float) $shipments->sum('harga_total'),
                ]);

                foreach ($shipments as $s) {
                    $this->createInvoiceItem([
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
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        return redirect()->route('finance.invoices.pdf', $invoice->id);
    }

    private function generateInvoiceNo(string $billedTo): string
    {
        $slug = trim(Str::upper(Str::substr(preg_replace('/[^a-zA-Z0-9]+/', '-', $billedTo), 0, 12)), '-');
        if (!$slug) $slug = 'CUST';
        $seq = (int) Invoice::where('billed_to', $billedTo)->count() + 1;
        return 'INV-' . $slug . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT) . '-' . now()->format('ym');
    }

    public function listInvoices()
    {
        $invoices = Invoice::withCount('items')->orderBy('created_at', 'desc')->paginate(12);
        return view('finance.invoices_list', compact('invoices'));
    }

    public function showInvoice(Invoice $invoice)
    {
        $invoice->load(['items.shipment']);
        return view('finance.invoice_show', compact('invoice'));
    }

    public function editInvoice(Invoice $invoice)
    {
        $invoice->load(['items.shipment']);
        return view('finance.invoice_edit', compact('invoice'));
    }

    public function availableShipments(Request $request)
    {
        $q         = trim((string) $request->query('q', ''));
        $invoiceId = (int) $request->query('invoice_id', 0);

        $currentIds  = $invoiceId ? InvoiceItem::where('invoice_id', $invoiceId)->pluck('shipment_id')->toArray() : [];
        $otherInvoiced = InvoiceItem::when($invoiceId, fn ($q2) => $q2->where('invoice_id', '!=', $invoiceId))
            ->pluck('shipment_id')->toArray();

        $shipments = Shipment::query()
            ->whereNotNull('manifest_id')
            ->whereNotIn('id', empty($otherInvoiced) ? [0] : $otherInvoiced)
            ->whereNotIn('id', empty($currentIds) ? [0] : $currentIds)
            ->when($q, fn ($q2) => $q2->where(fn ($qq) => $qq
                ->where('no_nota',        'like', "%{$q}%")
                ->orWhere('nama_penerima', 'like', "%{$q}%")
                ->orWhere('tujuan',        'like', "%{$q}%")))
            ->orderByDesc('created_at')->limit(30)->get();

        return response()->json(['ok' => true, 'rows' => $shipments->map(fn ($s) => [
            'id'                => (int) $s->id,
            'no_nota'           => $s->no_nota,
            'penerima'          => $s->nama_penerima,
            'tujuan'            => $s->tujuan,
            'status_pembayaran' => $s->status_pembayaran,
            'total'             => (float) $s->harga_total,
        ])->values()]);
    }

    public function addShipmentToInvoice(Invoice $invoice, Shipment $shipment)
    {
        if (InvoiceItem::where('invoice_id', $invoice->id)->where('shipment_id', $shipment->id)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Nota sudah ada dalam tagihan ini.'], 409);
        }
        if (InvoiceItem::where('shipment_id', $shipment->id)->where('invoice_id', '!=', $invoice->id)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Nota sudah masuk tagihan lain.'], 409);
        }

        return DB::transaction(function () use ($invoice, $shipment) {
            $this->createInvoiceItem([
                'invoice_id'  => $invoice->id,
                'shipment_id' => $shipment->id,
                'no_nota'     => $shipment->no_nota,
                'penerima'    => $shipment->nama_penerima,
                'tujuan'      => $shipment->tujuan,
                'nilai'       => (float) $shipment->harga_total,
            ]);

            $newTotal = (float) InvoiceItem::where('invoice_id', $invoice->id)->sum('nilai');
            $invoice->update(['total' => $newTotal]);

            return response()->json(['ok' => true, 'nilai' => (float) $shipment->harga_total, 'total' => $newTotal]);
        });
    }

    public function removeShipmentFromInvoice(Invoice $invoice, Shipment $shipment)
    {
        return DB::transaction(function () use ($invoice, $shipment) {
            $deleted = InvoiceItem::where('invoice_id', $invoice->id)
                ->where('shipment_id', $shipment->id)->delete();

            if (!$deleted) {
                return response()->json(['ok' => false, 'message' => 'Item tidak ditemukan.'], 404);
            }

            $newTotal = (float) InvoiceItem::where('invoice_id', $invoice->id)->sum('nilai');
            $invoice->update(['total' => $newTotal]);

            return response()->json(['ok' => true, 'total' => $newTotal]);
        });
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
                $invoice->payment_proof_path = $request->file('proof')->store('bukti-tagihan', 'public');
            }
            $invoice->status  = $data['status'];
            $invoice->paid_at = ($data['status'] === 'LUNAS') ? now() : null;
            $invoice->save();
            return back()->with('success', 'Status tagihan berhasil diupdate.');
        });
    }

    public function invoicePdf(Invoice $invoice)
    {
        $invoice->load(['items.shipment.items']);
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
            ->orderBy('shipments.created_at', 'desc')->get();

        return response()->json([
            'ok' => true, 'manifest_id' => $manifest->id,
            'count' => $shipments->count(), 'data' => $shipments,
        ]);
    }
}