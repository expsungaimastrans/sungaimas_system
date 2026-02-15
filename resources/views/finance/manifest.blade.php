@extends('layouts.app')
@section('title','Finance Manifest')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Finance - Manifest {{ $manifest->no_manifest }}</div>
    <div class="text-muted">
      <span class="badge {{ $unpaid>0?'text-bg-warning':'text-bg-success' }}">
        {{ $unpaid }}/{{ $total }} nota belum lunas
      </span>
    </div>
  </div>
  <div class="d-flex gap-2">
    <a href="/finance" class="btn btn-outline-secondary">Kembali</a>
    <a href="/finance/tagihan/create?q={{ urlencode($manifest->no_manifest) }}" class="btn btn-brand">+ Buat Tagihan</a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="text-center">
          <tr>
            <th>No Nota</th>
            <th>Penerima</th>
            <th>Tujuan</th>
            <th>Total</th>
            <th>COD/COT</th>
            <th>Status Bayar</th>
            <th>Bukti</th>
            <th style="width:220px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($shipments as $s)
            @php
              $payClass = match($s->status_pembayaran){
                'LUNAS' => 'text-bg-success',
                'PIUTANG' => 'text-bg-warning',
                'BATAL' => 'text-bg-danger',
                default => 'text-bg-secondary'
              };
            @endphp
            <tr>
              <td class="text-center fw-bold">{{ $s->no_nota }}</td>
              <td>{{ $s->nama_penerima }}</td>
              <td class="text-center">{{ $s->tujuan }}</td>
              <td class="text-end">Rp {{ number_format($s->harga_total,0,',','.') }}</td>
              <td class="text-center">
                <span class="badge {{ $s->tipe_bayar==='COT'?'text-bg-info':'text-bg-secondary' }}">
                  {{ $s->tipe_bayar ?? 'COD' }}
                </span>
              </td>
              <td class="text-center">
                <span class="badge {{ $payClass }}">{{ $s->status_pembayaran }}</span>
              </td>
              <td class="text-center">
                @if($s->bukti_bayar_path)
                  <a class="btn btn-sm btn-outline-secondary" target="_blank"
                     href="{{ asset('storage/'.$s->bukti_bayar_path) }}">Lihat</a>
                @else
                  <span class="text-muted small">-</span>
                @endif
              </td>
              <td>
                <form method="POST" action="/finance/shipments/{{ $s->id }}/update" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 justify-content-center">
                  @csrf
                  <select name="tipe_bayar" class="form-select form-select-sm" style="width:90px;">
                    <option value="COD" {{ ($s->tipe_bayar ?? 'COD')==='COD'?'selected':'' }}>COD</option>
                    <option value="COT" {{ ($s->tipe_bayar ?? 'COD')==='COT'?'selected':'' }}>COT</option>
                  </select>

                  <select name="status_pembayaran" class="form-select form-select-sm" style="width:140px;">
                    <option value="BELUM_BAYAR" {{ $s->status_pembayaran==='BELUM_BAYAR'?'selected':'' }}>BELUM_BAYAR</option>
                    <option value="LUNAS" {{ $s->status_pembayaran==='LUNAS'?'selected':'' }}>LUNAS</option>
                    <option value="PIUTANG" {{ $s->status_pembayaran==='PIUTANG'?'selected':'' }}>PIUTANG</option>
                    <option value="BATAL" {{ $s->status_pembayaran==='BATAL'?'selected':'' }}>BATAL</option>
                  </select>

                  <input type="file" name="bukti" class="form-control form-control-sm" style="width:220px;" />

                  <button class="btn btn-sm btn-brand">Simpan</button>
                  <a class="btn btn-sm btn-outline-secondary" href="/shipments/{{ $s->id }}/pdf">PDF Nota</a>
                </form>

                <div class="text-muted small mt-1 text-center">
                  *Jika COT & set LUNAS, bukti bayar wajib diupload.
                </div>
              </td>
            </tr>
          @endforeach

          @if($shipments->count()===0)
            <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada nota pada manifest ini.</td></tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

