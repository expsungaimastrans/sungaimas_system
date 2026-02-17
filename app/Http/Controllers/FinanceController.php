// =========================
// ✅ PAGE BUAT TAGIHAN (mirip create manifest)
// =========================
public function invoices(Request $request)
{
    // opsi filter tujuan (biar dropdown)
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
// ✅ DATA untuk table nota (filter + exclude yang sudah masuk invoice)
// =========================
public function invoiceData(Request $request)
{
    $q        = trim((string)$request->query('q', ''));
    $tujuan   = trim((string)$request->query('tujuan', ''));
    $penerima = trim((string)$request->query('penerima', ''));
    $sp       = trim((string)$request->query('status_pembayaran', ''));

    $rows = Shipment::query()
        ->select('shipments.id','shipments.no_nota','shipments.nama_penerima','shipments.tujuan','shipments.status_pembayaran','shipments.harga_total','shipments.manifest_id')
        // ✅ hanya nota yg sudah masuk manifest (opsional; kalau mau semua, hapus baris ini)
        ->whereNotNull('shipments.manifest_id')
        // ✅ exclude yg sudah pernah masuk invoice item
        ->leftJoin('invoice_items as ii','ii.shipment_id','=','shipments.id')
        ->whereNull('ii.shipment_id')
        ->when($q, function($query) use ($q){
            $query->where(function($qq) use ($q){
                $qq->where('shipments.no_nota','like',"%{$q}%")
                   ->orWhere('shipments.nama_penerima','like',"%{$q}%")
                   ->orWhere('shipments.tujuan','like',"%{$q}%");
            });
        })
        ->when($tujuan, fn($query)=> $query->where('shipments.tujuan', $tujuan))
        ->when($penerima, fn($query)=> $query->where('shipments.nama_penerima','like',"%{$penerima}%"))
        ->when($sp, fn($query)=> $query->where('shipments.status_pembayaran', $sp))
        ->orderByDesc('shipments.created_at')
        ->limit(200)
        ->get()
        ->map(function($s){
            return [
                'id' => (int)$s->id,
                'no_nota' => $s->no_nota,
                'penerima' => $s->nama_penerima,
                'tujuan' => $s->tujuan,
                'status_pembayaran' => $s->status_pembayaran,
                'total' => (float)$s->harga_total,
                'manifest_id' => $s->manifest_id,
            ];
        });

    return response()->json(['ok'=>true,'rows'=>$rows]);
}

// =========================
// ✅ SIMPAN TAGIHAN (tanpa manifest wajib)
// =========================
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

        // ambil shipment, validasi belum ada di invoice_items
        $shipments = Shipment::query()
            ->whereIn('id', $ids)
            ->whereNotNull('manifest_id') // kalau kamu mau boleh tanpa manifest, hapus baris ini
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

        // manifest_id di invoice boleh null (karena tagihan lintas manifest bisa)
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
