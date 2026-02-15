<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function create(Request $request)
    {
        // ambil hanya nota yang belum lunas (umumnya yang ditagih)
        $q = $request->query('q');

        $shipments = Shipment::query()
            ->when($q, function($qq) use ($q){
                $qq->where('no_nota','like',"%{$q}%")
                   ->orWhere('nama_pengirim','like',"%{$q}%")
                   ->orWhere('nama_penerima','like',"%{$q}%")
                   ->orWhere('tujuan','like',"%{$q}%");
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->appends(['q'=>$q]);

        return view('finance.tagihan-create', compact('shipments','q'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'numeric|exists:shipments,id',
            'customer' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
        ]);

        return DB::transaction(function() use ($request){

            // nomor invoice: INV-YYYYMM-#### (4 digit)
            $ym = now()->format('Ym');

            $last = Invoice::where('no_invoice','like',"INV-{$ym}-%")
                ->orderByDesc('no_invoice')
                ->value('no_invoice');

            $seq = 1;
            if ($last) {
                $parts = explode('-', $last); // INV, YYYYMM, ####
                $seq = (int)($parts[2] ?? 0) + 1;
            }
            $no = "INV-{$ym}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'no_invoice' => $no,
                'tanggal' => now()->toDateString(),
                'customer' => $request->customer,
                'catatan' => $request->catatan,
                'total' => 0,
            ]);

            $sum = 0;
            $shipments = Shipment::whereIn('id', $request->shipment_ids)->get();

            foreach ($shipments as $s) {
                $nilai = (float)($s->harga_total ?? 0);
                $sum += $nilai;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'shipment_id' => $s->id,
                    'no_nota' => $s->no_nota,
                    'penerima' => $s->nama_penerima,
                    'tujuan' => $s->tujuan,
                    'nilai' => $nilai,
                ]);
            }

            $invoice->update(['total' => $sum]);

            return redirect("/finance/tagihan/{$invoice->id}/pdf");
        });
    }

    public function pdf($id)
    {
        $invoice = Invoice::with('items.shipment')->findOrFail($id);

        // Template tagihan (tanpa logo supaya aman di Railway kalau GD belum ada)
        $pdf = Pdf::loadView('finance.tagihan-pdf', compact('invoice'))
            ->setPaper('A4', 'portrait');

        $safeNo = str_replace(['/', '\\'], '-', $invoice->no_invoice);

        return $pdf->stream("tagihan-{$safeNo}.pdf");
    }
}
