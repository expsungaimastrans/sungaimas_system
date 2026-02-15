<?php

namespace App\Http\Controllers;

use App\Models\Manifest;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FinanceController extends Controller
{
    /**
     * List manifest, tampilkan label xx/xx belum lunas
     */
    public function index()
    {
        $manifests = Manifest::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(function($m){
                $total = Shipment::where('manifest_id', $m->id)->count();
                $unpaid = Shipment::where('manifest_id', $m->id)
                    ->whereIn('status_pembayaran', ['BELUM_BAYAR','PIUTANG'])
                    ->count();

                return (object)[
                    'id' => $m->id,
                    'no_manifest' => $m->no_manifest,
                    'manifest_ke' => $m->manifest_ke,
                    'tanggal_muat' => $m->tanggal_muat,
                    'sopir' => $m->sopir,
                    'nopol' => $m->nopol,
                    'total' => $total,
                    'unpaid' => $unpaid,
                ];
            });

        return view('finance.index', compact('manifests'));
    }

    /**
     * Detail 1 manifest: list shipments di manifest tsb
     */
    public function manifest($id)
    {
        $manifest = Manifest::findOrFail($id);

        $shipments = Shipment::where('manifest_id', $manifest->id)
            ->orderByDesc('created_at')
            ->get();

        $total = $shipments->count();
        $unpaid = $shipments->whereIn('status_pembayaran', ['BELUM_BAYAR','PIUTANG'])->count();

        return view('finance.manifest', compact('manifest','shipments','total','unpaid'));
    }

    /**
     * Update finance shipment:
     * - admin set COD/COT
     * - update status pembayaran
     * - jika COT dan status jadi LUNAS => wajib upload bukti bayar
     */
    public function updateShipmentFinance(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id);

        $request->validate([
            'tipe_bayar' => 'required|in:COD,COT',
            'status_pembayaran' => 'required|in:BELUM_BAYAR,LUNAS,PIUTANG,BATAL',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $tipe = $request->tipe_bayar;
        $status = $request->status_pembayaran;

        // Rule: COT wajib upload bukti bayar (minimal saat mau jadi LUNAS)
        if ($tipe === 'COT' && $status === 'LUNAS') {
            $hasExisting = !empty($shipment->bukti_bayar_path);
            $hasNew = $request->hasFile('bukti');

            if (!$hasExisting && !$hasNew) {
                return back()->with('error', 'COT wajib upload bukti bayar sebelum status menjadi LUNAS.');
            }
        }

        return DB::transaction(function() use ($request, $shipment, $tipe, $status){
            // upload bukti jika ada
            if ($request->hasFile('bukti')) {
                $path = $request->file('bukti')->store('bukti-bayar', 'public');

                // hapus lama (optional)
                if ($shipment->bukti_bayar_path) {
                    Storage::disk('public')->delete($shipment->bukti_bayar_path);
                }

                $shipment->bukti_bayar_path = $path;
            }

            $shipment->tipe_bayar = $tipe;
            $shipment->status_pembayaran = $status;

            if ($status === 'LUNAS') {
                $shipment->paid_at = now();
            } else {
                $shipment->paid_at = null;
            }

            $shipment->save();

            return back()->with('success', 'Finance nota berhasil diupdate.');
        });
    }
}
