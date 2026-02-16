<?php

namespace App\Http\Controllers;

use App\Models\Manifest;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class FinanceController extends Controller
{
    /**
     * Finance Home: list manifests + badge unpaid
     */
    public function index()
    {
        // Ambil manifests terbaru + hitung total nota & unpaid
        $manifests = Manifest::query()
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate(12);

        // hitung unpaid per manifest_id (shipments.manifest_id)
        $unpaidMap = Shipment::query()
            ->selectRaw('manifest_id, COUNT(*) as total, SUM(CASE WHEN status_pembayaran != "LUNAS" THEN 1 ELSE 0 END) as unpaid')
            ->whereNotNull('manifest_id')
            ->groupBy('manifest_id')
            ->get()
            ->keyBy('manifest_id');

        return view('finance.index', [
            'manifests' => $manifests,
            'unpaidMap' => $unpaidMap,
        ]);
    }

    /**
     * Finance by Manifest: list shipments in the manifest
     * URL: /finance/manifest/{manifest}
     */
    public function byManifest(Manifest $manifest)
    {
        // shipments yang masuk manifest ini
        $shipments = Shipment::query()
            ->with('items')
            ->where('manifest_id', $manifest->id)
            ->orderByDesc('created_at')
            ->get();

        $total = $shipments->count();
        $unpaid = $shipments->where('status_pembayaran', '!=', 'LUNAS')->count();

        return view('finance.manifest', [
            'manifest' => $manifest,
            'shipments' => $shipments,
            'total' => $total,
            'unpaid' => $unpaid,
        ]);
    }

    /**
     * Update finance fields for one shipment
     * POST /finance/shipments/{shipment}/update
     *
     * Rules:
     * - tipe_bayar: COD / COT
     * - kalau COT wajib upload bukti bayar ketika status LUNAS
     */
    public function updateShipmentFinance(Request $request, Shipment $shipment)
    {
        $request->validate([
            'status_pembayaran' => 'required|in:BELUM_BAYAR,LUNAS,PIUTANG,BATAL',
            'tipe_bayar'        => 'required|in:COD,COT',
            'bukti_bayar'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        return DB::transaction(function () use ($request, $shipment) {
            $status = $request->status_pembayaran;
            $tipe   = $request->tipe_bayar;

            // Handle upload file
            $path = $shipment->bukti_bayar_path ?? null;

            if ($request->hasFile('bukti_bayar')) {
                $path = $request->file('bukti_bayar')->store('bukti-bayar', 'public');
            }

            // Rule: kalau COT & status LUNAS wajib ada bukti bayar
            if ($tipe === 'COT' && $status === 'LUNAS' && !$path) {
                return back()->with('error', 'COT + LUNAS wajib upload bukti bayar.');
            }

            $shipment->status_pembayaran = $status;
            $shipment->tipe_bayar = $tipe;
            $shipment->bukti_bayar_path = $path;

            if ($status === 'LUNAS') {
                $shipment->paid_at = now();
            } else {
                $shipment->paid_at = null;
            }

            $shipment->save();

            return back()->with('success', 'Finance nota berhasil diupdate.');
        });
    }

    /**
     * Generate Invoice PDF (Tagihan gabungan beberapa nota)
     * POST /finance/invoice/generate
     *
     * Request: shipment_ids[] (checkbox list)
     */
    public function generateInvoicePdf(Request $request)
    {
        $request->validate([
            'shipment_ids'   => 'required|array|min:1',
            'shipment_ids.*' => 'numeric',
        ]);

        $shipments = Shipment::with('items')
            ->whereIn('id', $request->shipment_ids)
            ->orderBy('created_at')
            ->get();

        if ($shipments->isEmpty()) {
            return back()->with('error', 'Tidak ada nota dipilih.');
        }

        // data invoice sederhana
        $invoiceNo = 'INV-' . now()->format('Ymd-His');
        $grandTotal = (float)$shipments->sum('harga_total');

        $pdf = Pdf::loadView('finance.invoice_pdf', [
            'invoiceNo' => $invoiceNo,
            'shipments' => $shipments,
            'grandTotal' => $grandTotal,
        ])->setPaper('A4', 'portrait');

        return $pdf->stream("tagihan-{$invoiceNo}.pdf");
    }
}
