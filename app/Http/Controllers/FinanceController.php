<?php

namespace App\Http\Controllers;

use App\Models\Manifest;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class FinanceController extends Controller
{
    /**
     * Landing Finance: list manifest + badge unpaid
     */
    public function index()
    {
        $manifests = Manifest::orderBy('created_at', 'desc')->paginate(10);

        // unpaidMap: manifest_id => { total, unpaid }
        $stats = Shipment::selectRaw('manifest_id, COUNT(*) as total, SUM(CASE WHEN status_pembayaran <> "LUNAS" THEN 1 ELSE 0 END) as unpaid')
            ->whereNotNull('manifest_id')
            ->groupBy('manifest_id')
            ->get()
            ->keyBy('manifest_id');

        return view('finance.index', [
            'manifests' => $manifests,
            'unpaidMap' => $stats,
        ]);
    }

    /**
     * Kelola finance per manifest (detail)
     */
    public function byManifest(Manifest $manifest)
    {
        $shipments = Shipment::with('items')
            ->where('manifest_id', $manifest->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $shipments->count();
        $unpaid = $shipments->where('status_pembayaran', '!=', 'LUNAS')->count();

        return view('finance.manifest', compact('manifest', 'shipments', 'total', 'unpaid'));
    }

    /**
     * Update finance shipment (status, tipe bayar, bukti bayar)
     */
    public function updateShipmentFinance(Request $request, Shipment $shipment)
    {
        $data = $request->validate([
            'tipe_bayar' => 'required|in:COD,COT',
            'status_pembayaran' => 'required|in:BELUM_BAYAR,LUNAS,PIUTANG,BATAL',
            'bukti_bayar' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        // Rule: jika COT dan status LUNAS -> wajib bukti
        $needProof = ($data['tipe_bayar'] === 'COT' && $data['status_pembayaran'] === 'LUNAS');

        if ($needProof && !$request->hasFile('bukti_bayar') && empty($shipment->bukti_bayar_path)) {
            return back()->with('error', 'COT + LUNAS wajib upload bukti bayar.');
        }

        return DB::transaction(function () use ($request, $shipment, $data) {

            // Upload bukti jika ada
            if ($request->hasFile('bukti_bayar')) {
                $file = $request->file('bukti_bayar');
                $path = $file->store('bukti-bayar', 'public'); // storage/app/public/bukti-bayar/...
                $shipment->bukti_bayar_path = $path;
            }

            $shipment->tipe_bayar = $data['tipe_bayar'];
            $shipment->status_pembayaran = $data['status_pembayaran'];

            // paid_at otomatis jika lunas
            if ($data['status_pembayaran'] === 'LUNAS') {
                $shipment->paid_at = now();
            } else {
                $shipment->paid_at = null;
            }

            $shipment->save();

            return back()->with('success', 'Finance nota berhasil diupdate.');
        });
    }

    /**
     * ✅ PAGE LUAR: Buat Tagihan
     * - pilih manifest, lalu tampilkan list nota (via AJAX table)
     */
    public function invoices(Request $request)
    {
        $manifestId = (int)($request->query('manifest_id', 0));

        $manifests = Manifest::orderBy('created_at', 'desc')->limit(200)->get();

        // label xx/xx unpaid per manifest
        $stats = Shipment::selectRaw('manifest_id, COUNT(*) as total, SUM(CASE WHEN status_pembayaran <> "LUNAS" THEN 1 ELSE 0 END) as unpaid')
            ->whereNotNull('manifest_id')
            ->groupBy('manifest_id')
            ->get()
            ->keyBy('manifest_id');

        return view('finance.invoices', [
            'manifests' => $manifests,
            'stats' => $stats,
            'manifestId' => $manifestId,
        ]);
    }

    /**
     * Data AJAX untuk tabel invoice berdasarkan manifest
     */
    public function invoiceData(Request $request)
    {
        $manifestId = (int)$request->query('manifest_id', 0);
        if (!$manifestId) return response()->json(['ok'=>true,'rows'=>[]]);

        $shipments = Shipment::with('items')
            ->where('manifest_id', $manifestId)
            ->orderBy('created_at', 'desc')
            ->get();

        $rows = $shipments->map(function($s){
            return [
                'id' => $s->id,
                'no_nota' => $s->no_nota,
                'penerima' => $s->nama_penerima,
                'tujuan' => $s->tujuan,
                'total' => (float)$s->harga_total,
                'status_pembayaran' => $s->status_pembayaran,
            ];
        });

        return response()->json(['ok'=>true,'rows'=>$rows]);
    }

    /**
     * Generate PDF tagihan gabungan (rekap beberapa nota)
     */
    public function generateInvoicePdf(Request $request)
    {
        $data = $request->validate([
            'manifest_id' => 'required|numeric',
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'numeric',
        ]);

        $manifestId = (int)$data['manifest_id'];
        $ids = array_map('intval', $data['shipment_ids']);

        // Ambil shipments yg benar-benar ada di manifest tsb
        $shipments = Shipment::with('items')
            ->where('manifest_id', $manifestId)
            ->whereIn('id', $ids)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($shipments->count() === 0) {
            return back()->with('error', 'Tidak ada nota valid untuk dibuat tagihan.');
        }

        $grandTotal = (float)$shipments->sum('harga_total');

        // nomor invoice sederhana
        $invoiceNo = 'INV-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(4));

        $pdf = Pdf::loadView('finance.invoice_pdf', compact('shipments','grandTotal','invoiceNo'))
            ->setPaper('A4', 'portrait');

        // aman dari karakter / \
        $safe = str_replace(['/', '\\'], '-', $invoiceNo);

        return $pdf->stream("tagihan-{$safe}.pdf");
    }
}
