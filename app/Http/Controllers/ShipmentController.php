<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\ShipmentLog;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user?->role;
    
        // admin tidak boleh filter tanggal
        $canDateRange = in_array($role, ['owner', 'finance'], true);
    
        $q          = trim((string) $request->query('q', ''));
        $tujuan     = trim((string) $request->query('tujuan', ''));
        $penerima   = trim((string) $request->query('penerima', ''));
        $sp         = trim((string) $request->query('status_pembayaran', ''));
        $sk         = trim((string) $request->query('status_pengiriman', ''));
    
        // hanya dipakai kalau role boleh
        $from       = $canDateRange ? $request->query('from') : null;
        $to         = $canDateRange ? $request->query('to') : null;
    
        $base = Shipment::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('no_nota', 'like', "%{$q}%")
                        ->orWhere('nama_pengirim', 'like', "%{$q}%")
                        ->orWhere('nama_penerima', 'like', "%{$q}%")
                        ->orWhere('telp_pengirim', 'like', "%{$q}%")
                        ->orWhere('telp_penerima', 'like', "%{$q}%")
                        ->orWhere('tujuan', 'like', "%{$q}%");
                });
            })
            ->when($tujuan, fn($query) => $query->where('tujuan', $tujuan))
            ->when($penerima, fn($query) => $query->where('nama_penerima', 'like', "%{$penerima}%"))
            ->when($sp, fn($query) => $query->where('status_pembayaran', $sp))
            ->when($sk, fn($query) => $query->where('status_pengiriman', $sk))
            // ✅ hanya jalan jika role boleh date range
            ->when($canDateRange && $from, fn($query) => $query->whereDate('created_at', '>=', $from))
            ->when($canDateRange && $to, fn($query) => $query->whereDate('created_at', '<=', $to));
    
        // pagination list
        $shipments = (clone $base)
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    
        // tujuan options
        $tujuanOptions = Shipment::query()
            ->select('tujuan')
            ->whereNotNull('tujuan')
            ->where('tujuan', '<>', '')
            ->distinct()
            ->orderBy('tujuan')
            ->pluck('tujuan');
    
        // summary (sesuai filter!)
        $summaryBase = clone $base;
    
        $summary = [
            'count' => (clone $summaryBase)->count(),
            'omzet' => (float) (clone $summaryBase)->sum('harga_total'),
            'piutang' => (float) (clone $summaryBase)->where('status_pembayaran', 'PIUTANG')->sum('harga_total'),
            'belum_bayar' => (clone $summaryBase)->where('status_pembayaran', 'BELUM_BAYAR')->count(),
            'dalam_pengiriman' => (clone $summaryBase)->where('status_pengiriman', 'DALAM_PENGIRIMAN')->count(),
        ];
    
        return view('shipments.index', [
            'shipments'     => $shipments,
            'tujuanOptions' => $tujuanOptions,
            'summary'       => $summary,
            'canDateRange'  => $canDateRange, // ✅ buat blade
            'filters' => [
                'q' => $q,
                'tujuan' => $tujuan,
                'penerima' => $penerima,
                'status_pembayaran' => $sp,
                'status_pengiriman' => $sk,
                // ✅ jika admin, tetap kosong biar gak kepakai
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
    


public function exportCsv(Request $request)
{
    $q          = trim((string) $request->query('q', ''));
    $tujuan     = trim((string) $request->query('tujuan', ''));
    $penerima   = trim((string) $request->query('penerima', ''));
    $sp         = trim((string) $request->query('status_pembayaran', ''));
    $sk         = trim((string) $request->query('status_pengiriman', ''));
    $from       = $request->query('from');
    $to         = $request->query('to');

    $rows = Shipment::query()
        ->when($q, function ($query) use ($q) {
            $query->where(function ($qq) use ($q) {
                $qq->where('no_nota', 'like', "%{$q}%")
                    ->orWhere('nama_pengirim', 'like', "%{$q}%")
                    ->orWhere('nama_penerima', 'like', "%{$q}%")
                    ->orWhere('telp_pengirim', 'like', "%{$q}%")
                    ->orWhere('telp_penerima', 'like', "%{$q}%")
                    ->orWhere('tujuan', 'like', "%{$q}%");
            });
        })
        ->when($tujuan, fn($query) => $query->where('tujuan', $tujuan))
        ->when($penerima, fn($query) => $query->where('nama_penerima', 'like', "%{$penerima}%"))
        ->when($sp, fn($query) => $query->where('status_pembayaran', $sp))
        ->when($sk, fn($query) => $query->where('status_pengiriman', $sk))
        ->when($from, fn($query) => $query->whereDate('created_at', '>=', $from))
        ->when($to, fn($query) => $query->whereDate('created_at', '<=', $to))
        ->orderBy('created_at', 'desc')
        ->with('items')
        ->get([
            'id','no_nota','tanggal','nama_pengirim','telp_pengirim',
            'nama_penerima','telp_penerima','tujuan','harga_total',
            'status_pengiriman','status_pembayaran','manifest_id','created_at'
        ]);

    $filename = 'shipments-export-' . now()->format('Ymd-His') . '.csv';

    $headers = [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
    ];

    $callback = function () use ($rows) {
        $out = fopen('php://output', 'w');

        // BOM biar Excel Indonesia kebaca UTF-8
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($out, [
            'No Nota','Tanggal','Pengirim','Telp Pengirim',
            'Penerima','Telp Penerima','Tujuan','Total',
            'Detail Barang','Total Koli','Total KG',
            'Status Pengiriman','Status Pembayaran','Manifest ID','Created At'
        ]);

        foreach ($rows as $s) {
            $detailBarang = $s->items->map(function ($it) {
                $parts = [$it->nama_barang];
                if ((float)($it->koli ?? 0) > 0)     $parts[] = (float)$it->koli . ' koli';
                if ((float)($it->berat_kg ?? 0) > 0)  $parts[] = (float)$it->berat_kg . ' kg';
                return implode(' ', $parts);
            })->implode(' | ');

            $totalKoli = $s->items->sum(fn($it) => (float)($it->koli ?? 0));
            $totalKg   = $s->items->sum(fn($it) => (float)($it->berat_kg ?? 0));

            fputcsv($out, [
                $s->no_nota,
                $s->tanggal,
                $s->nama_pengirim,
                $s->telp_pengirim,
                $s->nama_penerima,
                $s->telp_penerima,
                $s->tujuan,
                (float)$s->harga_total,
                $detailBarang,
                $totalKoli,
                $totalKg,
                $s->status_pengiriman,
                $s->status_pembayaran,
                $s->manifest_id,
                optional($s->created_at)->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($out);
    };

    return response()->stream($callback, 200, $headers);
}




    public function create()
    {
        return view('shipments.create');
    }

    /**
     * CREATE / STORE (nomor nota: MM + urut4 / totalKoli)
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_pengirim' => 'required',
            'nama_penerima' => 'required',
            'telp_penerima' => 'required',
            'alamat_penerima' => 'required',
            'tujuan' => 'required',
            'barang' => 'required|array|min:1',
            'barang.*.nama' => 'required|string',
            'barang.*.koli' => 'nullable|numeric|min:0',
            'barang.*.berat_kg' => 'nullable|numeric|min:0',
            'barang.*.kubikasi_m3' => 'nullable|numeric|min:0',
            'barang.*.satuan_tarif' => 'required|in:kg,kubik,unit',
            'barang.*.harga' => 'required|numeric|min:0',
        ]);

        // ===== nomor urut di bulan ini (4 digit) =====
        $bulan = now()->format('m');
        $seq = Shipment::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;
        $urut = str_pad($seq, 4, '0', STR_PAD_LEFT);

        // ===== total koli dalam nota =====
        $totalKoli = 0;
        foreach ($request->barang as $b) {
            $totalKoli += (float)($b['koli'] ?? 0);
        }

        $noNota = "{$bulan}{$urut}/" . (int)$totalKoli;

        $shipment = Shipment::create([
            'no_nota' => $noNota,
            'tanggal' => now(),
            'nama_pengirim' => $request->nama_pengirim,
            'telp_pengirim' => $request->telp_pengirim,
            'nama_penerima' => $request->nama_penerima,
            'telp_penerima' => $request->telp_penerima,
            'alamat_penerima' => $request->alamat_penerima,
            'tujuan' => $request->tujuan,
            'harga_total' => 0,
            'keterangan' => $request->keterangan,
            // status default dari DB: DITERIMA + BELUM_BAYAR
        ]);

        $grandTotal = 0;

        foreach ($request->barang as $item) {
            $koli  = (float)($item['koli'] ?? 0);
            $kg    = (float)($item['berat_kg'] ?? 0);
            $kubik = (float)($item['kubikasi_m3'] ?? 0);
            $tarif = $item['satuan_tarif'] ?? 'unit';
            $harga = (float)($item['harga'] ?? 0);

            if ($tarif === 'kg') $qty = $kg;
            elseif ($tarif === 'kubik') $qty = $kubik;
            else $qty = $koli;

            $subtotal = $qty * $harga;
            $grandTotal += $subtotal;

            $shipment->items()->create([
                'nama_barang'  => $item['nama'],
                'koli'         => $koli,
                'berat_kg'     => $kg,
                'kubikasi_m3'  => $kubik,
                'satuan_tarif' => $tarif,
                'harga_satuan' => $harga,
                'subtotal'     => $subtotal,
            ]);
        }

        $shipment->update(['harga_total' => $grandTotal]);

        // ===== LOG CREATED =====
        $shipment->load('items');
        $this->logShipment($shipment, 'CREATED', 'Nota dibuat', [
            'no_nota' => $shipment->no_nota,
            'total'   => (float)$shipment->harga_total,
            'total_koli' => (float)$shipment->items->sum(fn($it)=> (float)($it->koli ?? 0)),
            'total_kg'   => (float)$shipment->items->sum(fn($it)=> (float)($it->berat_kg ?? 0)),
        ]);

        return redirect('/shipments/' . $shipment->id . '/success');
    }

    /**
     * SUCCESS PAGE (WA + Download PDF)
     */
    public function success($id)
    {
        $shipment = Shipment::with('items')->findOrFail($id);

        $waPengirim = $this->waLink($shipment->telp_pengirim, $shipment);
        $waPenerima = $this->waLink($shipment->telp_penerima, $shipment);

        return view('shipments.success', compact('shipment', 'waPengirim', 'waPenerima'));
    }

    public function pdf($id)
    {
        $shipment = Shipment::with('items')->findOrFail($id);

        $pdf = Pdf::loadView('shipments.pdf', compact('shipment'))
            ->setPaper('A4', 'portrait');

        $fileNo = str_replace(['/', '\\'], '-', (string)$shipment->no_nota);

        return $pdf->stream('nota-' . $fileNo . '.pdf');
    }

    /**
     * EDIT
     */
    public function edit($id)
    {
        $shipment = Shipment::with(['items','logs'])->findOrFail($id);
        return view('shipments.edit', compact('shipment'));
    }

    /**
     * UPDATE (jumlah koli di nomor nota ikut berubah)
     */
    public function update(Request $request, $id)
    {
        $shipment = Shipment::with('items')->findOrFail($id);

        $request->validate([
            'nama_pengirim' => 'required',
            'nama_penerima' => 'required',
            'telp_penerima' => 'required',
            'alamat_penerima' => 'required',
            'tujuan' => 'required',
            'barang' => 'required|array|min:1',
            'barang.*.nama' => 'required|string',
            'barang.*.koli' => 'nullable|numeric|min:0',
            'barang.*.berat_kg' => 'nullable|numeric|min:0',
            'barang.*.kubikasi_m3' => 'nullable|numeric|min:0',
            'barang.*.satuan_tarif' => 'required|in:kg,kubik,unit',
            'barang.*.harga' => 'required|numeric|min:0',
        ]);

        // hitung ulang total koli
        $totalKoli = 0;
        foreach ($request->barang as $b) {
            $totalKoli += (float)($b['koli'] ?? 0);
        }
        $totalKoliInt = (int)$totalKoli;

        // prefix nomor nota tetap (sebelum slash)
        $currentNo = (string)($shipment->no_nota ?? '');
        $prefix = str_contains($currentNo, '/') ? explode('/', $currentNo)[0] : $currentNo;
        $newNoNota = $prefix . '/' . $totalKoliInt;

        $shipment->update([
            'no_nota' => $newNoNota,
            'nama_pengirim' => $request->nama_pengirim,
            'telp_pengirim' => $request->telp_pengirim,
            'nama_penerima' => $request->nama_penerima,
            'telp_penerima' => $request->telp_penerima,
            'alamat_penerima' => $request->alamat_penerima,
            'tujuan' => $request->tujuan,
            'keterangan' => $request->keterangan,
            'harga_total' => 0,
        ]);

        // replace items
        $shipment->items()->delete();

        $grandTotal = 0;

        foreach ($request->barang as $item) {
            $koli  = (float)($item['koli'] ?? 0);
            $kg    = (float)($item['berat_kg'] ?? 0);
            $kubik = (float)($item['kubikasi_m3'] ?? 0);
            $tarif = $item['satuan_tarif'] ?? 'unit';
            $harga = (float)($item['harga'] ?? 0);

            if ($tarif === 'kg') $qty = $kg;
            elseif ($tarif === 'kubik') $qty = $kubik;
            else $qty = $koli;

            $subtotal = $qty * $harga;
            $grandTotal += $subtotal;

            $shipment->items()->create([
                'nama_barang'  => $item['nama'],
                'koli'         => $koli,
                'berat_kg'     => $kg,
                'kubikasi_m3'  => $kubik,
                'satuan_tarif' => $tarif,
                'harga_satuan' => $harga,
                'subtotal'     => $subtotal,
            ]);
        }

        $shipment->update(['harga_total' => $grandTotal]);

        // ===== LOG UPDATED =====
        $shipment->load('items');
        $this->logShipment($shipment, 'UPDATED', 'Nota diperbarui', [
            'no_nota' => $shipment->no_nota,
            'total'   => (float)$shipment->harga_total,
            'total_koli' => (float)$shipment->items->sum(fn($it)=> (float)($it->koli ?? 0)),
            'total_kg'   => (float)$shipment->items->sum(fn($it)=> (float)($it->berat_kg ?? 0)),
        ]);

        return redirect('/shipments/' . $shipment->id . '/success');
    }

    /**
     * API: search shipments for manifest picker (yang belum dipakai manifest)
     */
    public function searchJson(Request $request)
    {
        $q = $request->query('q', '');

        $shipments = Shipment::with('items')
            ->whereDoesntHave('manifestItem')
            ->when($q, function($query) use ($q){
                $query->where('no_nota','like',"%{$q}%")
                    ->orWhere('nama_pengirim','like',"%{$q}%")
                    ->orWhere('nama_penerima','like',"%{$q}%")
                    ->orWhere('tujuan','like',"%{$q}%");
            })
            ->orderBy('created_at','desc')
            ->limit(30)
            ->get();

        $result = $shipments->map(function($s){
            $totalKoli = (float)$s->items->sum(fn($it) => (float)($it->koli ?? 0));
            $totalKg   = (float)$s->items->sum(fn($it) => (float)($it->berat_kg ?? 0));

            $ringkas = $s->items->values()->map(function($it, $i){
                return ($i+1).") ".strtoupper((string)($it->nama_barang ?? ''));
            })->implode("\n");

            return [
                'id'            => $s->id,
                'no_nota'       => $s->no_nota,
                'nama_penerima' => $s->nama_penerima,
                'tujuan'        => $s->tujuan,
                'total_koli'    => $totalKoli,
                'total_kg'      => $totalKg,
                'ringkas_barang'=> $ringkas ?: '-',
            ];
        });

        return response()->json($result);
    }

    /**
     * API: shipment detail
     */
    public function showJson($id)
    {
        $s = Shipment::with('items')->findOrFail($id);

        return response()->json([
            'id'            => $s->id,
            'no_nota'       => $s->no_nota,
            'tanggal'       => $s->tanggal,
            'nama_pengirim' => $s->nama_pengirim,
            'telp_pengirim' => $s->telp_pengirim,
            'nama_penerima' => $s->nama_penerima,
            'telp_penerima' => $s->telp_penerima,
            'alamat_penerima'=> $s->alamat_penerima,
            'tujuan'        => $s->tujuan,
            'harga_total'   => (float)($s->harga_total ?? 0),
            'items'         => $s->items->map(function($it){
                return [
                    'nama_barang' => $it->nama_barang,
                    'koli'        => (float)($it->koli ?? 0),
                    'berat_kg'    => (float)($it->berat_kg ?? 0),
                    'harga_satuan'=> (float)($it->harga_satuan ?? 0),
                    'subtotal'    => (float)($it->subtotal ?? 0),
                ];
            }),
        ]);
    }

    /**
     * AJAX: set status pembayaran (JSON)
     */
    public function setStatusPembayaran(Request $request, $id)
{
    $shipment = Shipment::findOrFail($id);

    // frontend kirim: { status: "LUNAS" }
    $val = $request->input('status') ?? $request->input('status_pembayaran');

    $allowed = ['BELUM_BAYAR','LUNAS','PIUTANG','BATAL'];
    if (!in_array($val, $allowed, true)) {
        return response()->json([
            'ok' => false,
            'message' => 'Status pembayaran tidak valid'
        ], 422);
    }

    $shipment->status_pembayaran = $val;
    $shipment->save();

    return response()->json([
        'ok' => true,
        'status' => $shipment->status_pembayaran
    ]);
}


    // =========================
    // Helpers: WA + Logging
    // =========================
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

    private function waLink(?string $phone, Shipment $shipment): ?string
    {
        $phone = $this->normalizePhone($phone);
        if (!$phone) return null;

        $msg = "Halo, berikut detail nota Expedisi Sungai Mas:\n"
             . "No Nota: {$shipment->no_nota}\n"
             . "Pengirim: {$shipment->nama_pengirim}\n"
             . "Penerima: {$shipment->nama_penerima}\n"
             . "Tujuan: {$shipment->tujuan}\n"
             . "Total: Rp " . number_format((float)$shipment->harga_total, 0, ',', '.') . "\n"
             . "Silakan download PDF dari tombol Download di halaman nota.";

        return "https://wa.me/{$phone}?text=" . urlencode($msg);
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;

        $p = preg_replace('/\D+/', '', $phone);
        if (!$p) return null;

        if (str_starts_with($p, '08')) $p = '62' . substr($p, 1);
        if (str_starts_with($p, '8'))  $p = '62' . $p;
        if (str_starts_with($p, '6208')) $p = '62' . substr($p, 3);

        return $p;
    }
}