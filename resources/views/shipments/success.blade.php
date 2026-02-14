@extends('layouts.app')
@section('title','Nota Berhasil')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <div class="page-title h4 mb-0">Nota Berhasil Dibuat</div>
        <div class="text-muted">No Nota: <strong>{{ $shipment->no_nota }}</strong></div>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="/shipments" class="btn btn-outline-secondary">Kembali</a>
        <a href="/shipments/{{ $shipment->id }}/pdf" target="_blank" class="btn btn-primary">Lihat PDF</a>
        <a href="/shipments/{{ $shipment->id }}/pdf" download class="btn btn-success">⬇ Download PDF</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body content-card-body">

        {{-- ===== WA ACTIONS ===== --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="fw-bold mb-2">Kirim WA ke Penerima</div>
                @if(!empty($waPenerima))
                    <a class="btn btn-success w-100" target="_blank" href="{{ $waPenerima }}">
                        WhatsApp Penerima
                    </a>
                    <div class="mt-2">
                        <a href="/shipments/{{ $shipment->id }}/pdf" download class="btn btn-outline-success w-100">
                            ⬇ Download PDF untuk dikirim manual
                        </a>
                    </div>
                @else
                    <div class="alert alert-warning mb-0">Nomor WA penerima belum diisi.</div>
                @endif
            </div>

            <div class="col-md-6">
                <div class="fw-bold mb-2">Kirim WA ke Pengirim</div>
                @if(!empty($waPengirim))
                    <a class="btn btn-success w-100" target="_blank" href="{{ $waPengirim }}">
                        WhatsApp Pengirim
                    </a>
                    <div class="mt-2">
                        <a href="/shipments/{{ $shipment->id }}/pdf" download class="btn btn-outline-success w-100">
                            ⬇ Download PDF untuk dikirim manual
                        </a>
                    </div>
                @else
                    <div class="alert alert-warning mb-0">Nomor WA pengirim belum diisi.</div>
                @endif
            </div>
        </div>

        <hr class="my-4">

        {{-- ===== INFO ===== --}}
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="text-muted small">Tanggal</div>
                <div class="fw-bold">{{ \Carbon\Carbon::parse($shipment->tanggal)->format('d-m-Y') }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Tujuan</div>
                <div class="fw-bold">{{ $shipment->tujuan }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Total</div>
                <div class="fw-bold">Rp {{ number_format((float)$shipment->harga_total,0,',','.') }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">Pengirim</div>
                <div class="fw-bold">{{ $shipment->nama_pengirim }}</div>
                <div class="text-muted">{{ $shipment->telp_pengirim }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Penerima</div>
                <div class="fw-bold">{{ $shipment->nama_penerima }}</div>
                <div class="text-muted">{{ $shipment->telp_penerima }}</div>
            </div>

            <div class="col-12">
                <div class="text-muted small">Alamat Penerima</div>
                <div class="fw-semibold">{{ $shipment->alamat_penerima }}</div>
            </div>
        </div>

        <hr class="my-4">

        {{-- ===== TABEL BARANG STRUCTURE BARU ===== --}}
        <div class="h5 mb-2">Detail Barang</div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr class="text-center">
                        <th style="width:32%;">Barang</th>
                        <th style="width:8%;">Koli</th>
                        <th style="width:10%;">Kg</th>
                        <th style="width:10%;">Kubik (m³)</th>
                        <th style="width:10%;">Tarif</th>
                        <th style="width:15%;">Harga Satuan</th>
                        <th style="width:15%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($shipment->items as $it)
                    @php
                        $tarif = $it->satuan_tarif ?? 'unit';
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $it->nama_barang }}</td>
                        <td class="text-center">{{ (int)($it->koli ?? 0) }}</td>
                        <td class="text-center">{{ number_format((float)($it->berat_kg ?? 0), 2, ',', '.') }}</td>
                        <td class="text-center">{{ number_format((float)($it->kubikasi_m3 ?? 0), 3, ',', '.') }}</td>
                        <td class="text-center">
                            <span class="badge text-bg-success">{{ strtoupper($tarif) }}</span>
                        </td>
                        <td class="text-end">Rp {{ number_format((float)$it->harga_satuan, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float)$it->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($shipment->keterangan)
            <div class="mt-3">
                <div class="text-muted small">Keterangan</div>
                <div class="fw-semibold">{{ $shipment->keterangan }}</div>
            </div>
        @endif

    </div>
</div>
@endsection
