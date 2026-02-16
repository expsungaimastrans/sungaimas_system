@extends('layouts.app')
@section('title','Buat Tagihan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Buat Tagihan (PDF)</div>
    <div class="text-muted">Pilih beberapa nota → direkap jadi 1 PDF tagihan</div>
  </div>
  <a href="/finance" class="btn btn-outline-secondary">Kembali</a>
</div>

<form method="GET" action="/finance/tagihan/create" class="row g-2 align-items-end mb-3">
  <div class="col-md-8">
    <label class="form-label fw-semibold">Cari Nota</label>
    <input class="form-control" name="q" value="{{ $q ?? '' }}" placeholder="no nota / pengirim / penerima / tujuan">
  </div>
  <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary">Cari</button></div>
  <div class="col-md-2 d-grid"><a class="btn btn-outline-secondary" href="/finance/tagihan/create">Reset</a></div>
</form>

<form method="POST" action="{{ route('finance.invoice.generate') }}">
    @csrf


  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Customer (opsional)</label>
          <input name="customer" class="form-control" placeholder="Nama perusahaan/toko/customer">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Catatan (opsional)</label>
          <input name="catatan" class="form-control" placeholder="Contoh: mohon pelunasan sebelum keberangkatan">
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead class="text-center">
            <tr>
              <th style="width:60px;">Pilih</th>
              <th>No Nota</th>
              <th>Pengirim</th>
              <th>Penerima</th>
              <th>Tujuan</th>
              <th>Status Bayar</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            @foreach($shipments as $s)
              <tr>
                <td class="text-center">
                  <input type="checkbox" name="shipment_ids[]" value="{{ $s->id }}" style="transform:scale(1.2);">
                </td>
                <td class="text-center fw-bold">{{ $s->no_nota }}</td>
                <td>{{ $s->nama_pengirim }}</td>
                <td>{{ $s->nama_penerima }}</td>
                <td class="text-center">{{ $s->tujuan }}</td>
                <td class="text-center">{{ $s->status_pembayaran }}</td>
                <td class="text-end">Rp {{ number_format($s->harga_total,0,',','.') }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-2">
        <div>{{ $shipments->links() }}</div>
        <button class="btn btn-brand" style="min-width:220px;">Generate PDF Tagihan</button>
      </div>
    </div>
  </div>
</form>
@endsection
