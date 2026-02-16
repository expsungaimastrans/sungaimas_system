@extends('layouts.app')
@section('title','Daftar Tagihan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Daftar Tagihan</div>
    <div class="text-muted">Kelola status tagihan & bukti bayar</div>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('finance.invoices') }}" class="btn btn-brand">+ Buat Tagihan</a>
    <a href="{{ route('finance.index') }}" class="btn btn-outline-secondary">Kembali</a>
  </div>
</div>

@if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead>
          <tr class="text-center">
            <th>No Tagihan</th>
            <th>Ditagihkan Kepada</th>
            <th>Jumlah Nota</th>
            <th>Total</th>
            <th>Status</th>
            <th style="width:220px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoices as $inv)
            <tr>
              <td class="text-center fw-bold">{{ $inv->invoice_no }}</td>
              <td>{{ $inv->billed_to }}</td>
              <td class="text-center">{{ $inv->items_count }}</td>
              <td class="text-end">Rp {{ number_format($inv->total,0,',','.') }}</td>
              <td class="text-center">
                @php
                  $cls = match($inv->status){
                    'LUNAS' => 'text-bg-success',
                    'MENUNGGU_PEMBAYARAN' => 'text-bg-warning',
                    default => 'text-bg-secondary'
                  };
                @endphp
                <span class="badge {{ $cls }}">{{ $inv->status }}</span>
              </td>
              <td class="text-center">
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                  <a href="{{ route('finance.invoices.show', $inv) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                  <a href="{{ route('finance.invoices.pdf', $inv) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada tagihan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $invoices->links() }}</div>
  </div>
</div>
@endsection
