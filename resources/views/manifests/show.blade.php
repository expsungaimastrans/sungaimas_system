@extends('layouts.app')
@section('title','Detail Manifest')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="page-title h4 mb-0">
            Detail Manifest - {{ $manifest->no_manifest }}
        </div>
        <div class="text-muted">Informasi lengkap manifest & nota di dalamnya</div>
    </div>

    <a href="/manifests" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">

        <div class="row g-3">
            <div class="col-md-3"><strong>No Manifest:</strong> {{ $manifest->no_manifest }}</div>
            <div class="col-md-3"><strong>Manifest Ke:</strong> {{ $manifest->manifest_ke }}</div>
            <div class="col-md-3"><strong>Tanggal Muat:</strong> {{ $manifest->tanggal_muat }}</div>
            <div class="col-md-3"><strong>Sopir:</strong> {{ $manifest->sopir }}</div>
            <div class="col-md-3"><strong>Nopol:</strong> {{ $manifest->nopol }}</div>
            <div class="col-md-3"><strong>Nama Kapal:</strong> {{ $manifest->nama_kapal }}</div>
            <div class="col-md-3"><strong>Keberangkatan:</strong> {{ $manifest->keberangkatan }}</div>
        </div>

    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>No Nota</th>
                        <th>Pengirim</th>
                        <th>Penerima</th>
                        <th>Tujuan</th>
                        <th>Koli</th>
                        <th>Harga</th>
                        <th style="width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>

                @foreach($manifest->items as $it)
                    <tr>
                        <td class="text-center fw-semibold">{{ $it->kode }}</td>
                        <td>{{ $it->pengirim }}</td>
                        <td>{{ $it->penerima }}</td>
                        <td class="text-center">{{ $it->tujuan }}</td>
                        <td class="text-center">{{ $it->koli }}</td>
                        <td class="text-end">Rp {{ number_format($it->harga,0,',','.') }}</td>

                        <td class="text-center">
                            <a href="/shipments/{{ $it->shipment_id }}/pdf" target="_blank" class="btn btn-sm btn-primary">
                                Lihat Nota
                            </a>
                        </td>
                    </tr>
                @endforeach

                </tbody>
            </table>
        </div>

    </div>
</div>

@endsection
