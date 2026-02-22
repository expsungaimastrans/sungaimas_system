<?php

namespace App\Http\Controllers;

use App\Models\Manifest;
use App\Models\ManifestItem;
use App\Models\Shipment;
use App\Models\ShipmentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ManifestController extends Controller
{
    public function index()
    {
        $manifests = Manifest::withCount('items')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('manifests.index', compact('manifests'));
    }

    public function create()
    {
        return view('manifests.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal_muat' => 'required|date',
            'items' => 'nullable|array',
            'items.*.shipment_id' => 'nullable|numeric',
        ]);

        return DB::transaction(function () use ($request) {

            $ym = now()->format('Ym');

            $lastNo = Manifest::where('no_manifest', 'like', $ym . '%')
                ->orderBy('no_manifest', 'desc')
                ->value('no_manifest');

            $seq = $lastNo ? (int)substr($lastNo, 6) + 1 : 1;
            $noManifest = $ym . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $manifest = Manifest::create([
                'no_manifest'    => $noManifest,
                'manifest_ke'    => $seq,
                'sopir'          => $request->sopir,
                'nopol'          => $request->nopol,
                'tanggal_muat'   => $request->tanggal_muat,
                'nama_kapal'     => $request->nama_kapal,
                'keberangkatan'  => $request->keberangkatan,
            ]);

            if ($request->items && is_array($request->items)) {
                foreach ($request->items as $row) {
                    $shipmentId = $row['shipment_id'] ?? null;
                    if (!$shipmentId) continue;

                    $shipment = Shipment::where('id', $shipmentId)->lockForUpdate()->first();
                    if (!$shipment) continue;

                    // RULE: shipment tidak boleh masuk manifest lain
                    if ($shipment->manifest_id || ManifestItem::where('shipment_id', $shipment->id)->exists()) {
                        abort(409, 'Nota ini sudah masuk manifest lain.');
                    }

                    $shipment->load('items');

                    $totalKoli = (float)$shipment->items->sum(fn($it) => (float)($it->koli ?? 0));
                    $totalKg   = (float)$shipment->items->sum(fn($it) => (float)($it->berat_kg ?? 0));
                    $barangLines = $shipment->items->values()->map(function ($it, $i) {
                        return ($i + 1) . ") " . strtoupper((string)($it->nama_barang ?? ''));
                    })->implode("\n");

                    $manifest->items()->create([
                        'shipment_id'  => $shipment->id,
                        'kode'         => $shipment->no_nota,
                        'koli'         => $totalKoli,
                        'jenis_barang' => $barangLines,
                        'pengirim'     => $shipment->nama_pengirim,
                        'kg'           => $totalKg,
                        'penerima'     => $shipment->nama_penerima,
                        'tipe'         => $row['tipe'] ?? '',
                        'tujuan'       => $shipment->tujuan,
                        'harga'        => (float)($shipment->harga_total ?? 0),
                        'keterangan'   => $row['keterangan'] ?? '',
                    ]);

                    // status otomatis
                    $shipment->update([
                        'status_pengiriman' => 'DALAM_PENGIRIMAN',
                        'manifest_id'       => $manifest->id,
                        'manifested_at'     => now(),
                    ]);

                    // LOG manifest added
                    $this->logShipment($shipment, 'MANIFEST_ADDED', "Masuk manifest {$manifest->no_manifest}", [
                        'manifest_id' => $manifest->id,
                        'no_manifest' => $manifest->no_manifest,
                    ]);
                }
            }

            return redirect("/manifests/{$manifest->id}/pdf");
        });
    }

    public function pdf($id)
    {
        $manifest = Manifest::with('items')->findOrFail($id);
        $items = $manifest->items()->with(['shipment.items'])->get();

        $pdf = Pdf::loadView('manifests.pdf', compact('manifest'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("manifest-{$manifest->no_manifest}.pdf");
    }

    public function edit($id)
    {
        $manifest = Manifest::with('items')->findOrFail($id);
        return view('manifests.edit', compact('manifest'));
    }

    public function update(Request $request, $id)
    {
        $manifest = Manifest::findOrFail($id);

        $manifest->update([
            'sopir'         => $request->sopir,
            'nopol'         => $request->nopol,
            'tanggal_muat'  => $request->tanggal_muat,
            'nama_kapal'    => $request->nama_kapal,
            'keberangkatan' => $request->keberangkatan,
        ]);

        return redirect("/manifests/{$manifest->id}/edit")->with('success', 'Manifest diperbarui');
    }

    // AJAX add shipment ke manifest (di halaman edit)
    public function addShipment(Manifest $manifest, Shipment $shipment)
    {
        return DB::transaction(function () use ($manifest, $shipment) {

            $shipment = Shipment::where('id', $shipment->id)->lockForUpdate()->firstOrFail();

            if ($shipment->manifest_id || ManifestItem::where('shipment_id', $shipment->id)->exists()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Nota ini sudah masuk manifest lain.'
                ], 409);
            }

            $shipment->load('items');

            $totalKoli = (float)$shipment->items->sum(fn($it) => (float)($it->koli ?? 0));
            $totalKg   = (float)$shipment->items->sum(fn($it) => (float)($it->berat_kg ?? 0));

            $barangLines = $shipment->items->values()->map(function ($it, $i) {
                return ($i + 1) . ") " . strtoupper((string)($it->nama_barang ?? ''));
            })->implode("\n");

            $item = $manifest->items()->create([
                'shipment_id'  => $shipment->id,
                'kode'         => $shipment->no_nota,
                'koli'         => $totalKoli,
                'jenis_barang' => $barangLines,
                'pengirim'     => $shipment->nama_pengirim,
                'kg'           => $totalKg,
                'penerima'     => $shipment->nama_penerima,
                'tipe'         => '',
                'tujuan'       => $shipment->tujuan,
                'harga'        => (float)($shipment->harga_total ?? 0),
                'keterangan'   => '',
            ]);

            $shipment->update([
                'status_pengiriman' => 'DALAM_PENGIRIMAN',
                'manifest_id'       => $manifest->id,
                'manifested_at'     => now(),
            ]);

            $this->logShipment($shipment, 'MANIFEST_ADDED', "Masuk manifest {$manifest->no_manifest}", [
                'manifest_id' => $manifest->id,
                'no_manifest' => $manifest->no_manifest,
            ]);

            return response()->json([
                'ok' => true,
                'item' => [
                    'shipment_id' => $item->shipment_id,
                    'kode'        => $item->kode,
                    'koli'        => (float)$item->koli,
                    'jenis_barang'=> $item->jenis_barang,
                    'kg'          => (float)$item->kg,
                    'penerima'    => $item->penerima,
                    'tujuan'      => $item->tujuan,
                    'harga'       => (float)$item->harga,
                ]
            ]);
        });
    }

    // AJAX remove shipment dari manifest
    public function removeShipment(Manifest $manifest, Shipment $shipment)
    {
        return DB::transaction(function () use ($manifest, $shipment) {

            $deleted = $manifest->items()
                ->where('shipment_id', $shipment->id)
                ->delete();

            if ($deleted) {
                $s = Shipment::where('id', $shipment->id)->lockForUpdate()->first();
                if ($s && (int)$s->manifest_id === (int)$manifest->id) {
                    $s->update([
                        'status_pengiriman' => 'DITERIMA',
                        'manifest_id'       => null,
                        'manifested_at'     => null,
                    ]);

                    $this->logShipment($s, 'MANIFEST_REMOVED', "Dikeluarkan dari manifest {$manifest->no_manifest}", [
                        'manifest_id' => $manifest->id,
                        'no_manifest' => $manifest->no_manifest,
                    ]);
                }
            }

            return response()->json(['ok' => (bool)$deleted]);
        });
    }

    public function show($id)
    {
        return redirect("/manifests/{$id}/edit");
    }

    // ===== logging helper =====
    private function logShipment(Shipment $shipment, string $action, ?string $desc = null, array $meta = []): void
    {
        ShipmentLog::create([
            'shipment_id' => $shipment->id,
            'action'      => $action,
            'description' => $desc,
            'meta'        => $meta ?: null,
            'logged_at'   => now(),
        ]);
    }
}
