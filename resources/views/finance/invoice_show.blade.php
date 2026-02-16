@extends('layouts.app')
@section('title','Detail Tagihan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Detail Tagihan</div>
    <div class="text-muted">{{ $invoice->invoice_no }}</div>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('finance.invoices.list') }}" class="btn btn-outline-secondary">Kembali</a>
    <a href="{{ route('finance.invoices.pdf', $invoice) }}" class="btn btn-outline-secondary">PDF</a>
  </div>
</div>

@if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-6">
        <div class="text-muted small">Ditagihkan kepada</div>
        <div class="fw-semibold">{{ $invoice->billed_to }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Total</div>
        <div class="fw-semibold">Rp {{ number_format($invoice->total,0,',','.') }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Status</div>
        <div class="fw-semibold">{{ $invoice->status }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="fw-semibold mb-2">Update Status Tagihan</div>

    <form method="POST" action="{{ route('finance.invoices.status', $invoice) }}" enctype="multipart/form-data">
      @csrf

      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Status</label>
          <select name="status" class="form-select" required>
            @foreach(['BELUM_DITAGIH','MENUNGGU_PEMBAYARAN','LUNAS'] as $s)
              <option value="{{ $s }}" {{ $invoice->status===$s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
          </select>
          <div class="text-muted small mt-1">Jika LUNAS wajib upload bukti.</div>
        </div>

        <div class="col-md-5">
          <label class="form-label fw-semibold">Bukti Pembayaran (jpg/png/pdf)</label>
          <input type="file" name="proof" class="form-control">
          @if($invoice->payment_proof_path)
            <div class="text-muted small mt-1">Sudah ada bukti tersimpan.</div>
          @endif
        </div>

        <div class="col-md-3 text-end">
          <button class="btn btn-brand">Simpan</button>
        </div>
      </div>
    </form>

  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="fw-semibold mb-2">Daftar Nota dalam Tagihan</div>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead>
          <tr class="text-center">
            <th>No Nota</th>
            <th>Penerima</th>
            <th>Tujuan</th>
            <th>Total</th>
            <th>Status Bayar Nota</th>
          </tr>
        </thead>
        <tbody>
          @foreach($invoice->items as $it)
            <tr>
              <td class="text-center fw-bold">{{ $it->shipment->no_nota ?? '-' }}</td>
              <td>{{ $it->shipment->nama_penerima ?? '-' }}</td>
              <td class="text-center">{{ $it->shipment->tujuan ?? '-' }}</td>
              <td class="text-end">Rp {{ number_format($it->amount,0,',','.') }}</td>
              <td class="text-center">{{ $it->shipment->status_pembayaran ?? '-' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
