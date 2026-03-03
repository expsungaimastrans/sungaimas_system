@extends('layouts.app')
@section('title','Detail Tagihan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Detail Tagihan</div>
    <div class="text-muted">{{ $invoice->invoice_no }}</div>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <a href="{{ route('finance.invoices.list') }}" class="btn btn-outline-secondary">Kembali</a>
    <a href="{{ route('finance.invoices.pdf', $invoice) }}" class="btn btn-outline-secondary">PDF</a>
    <button class="btn btn-success" onclick="document.getElementById('waBox').classList.toggle('d-none')">
      📱 Kirim WA
    </button>
  </div>
</div>

@if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

<meta name="csrf-token" content="{{ csrf_token() }}">

<div id="waBox" class="card shadow-sm mb-3 border-success d-none">
  <div class="card-body">
    <div class="fw-semibold mb-2">📱 Kirim PDF Tagihan via WhatsApp</div>

    @if($invoice->wa_sent_at)
      <div class="alert alert-success py-2 mb-2">
        ✓ Terakhir dikirim: <strong>{{ \Carbon\Carbon::parse($invoice->wa_sent_at)->format('d/m/Y H:i') }}</strong>
        ke {{ $invoice->wa_sent_to }}
      </div>
    @endif

    <div class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label fw-semibold mb-1">Nomor WhatsApp</label>
        <input type="text" id="waTelp" class="form-control"
               placeholder="08xxxxxxxxxx" value="{{ $invoice->wa_sent_to ?? '' }}">
      </div>
      <div class="col-md-3">
        <button id="waSendBtn" class="btn btn-success w-100" onclick="kirimWaTagihan(this)">
          Kirim Sekarang
        </button>
      </div>
      <div class="col-12">
        <div id="waMsg" class="small"></div>
      </div>
    </div>
  </div>
</div>

<script>
async function kirimWaTagihan(btn) {
  const telp = document.getElementById('waTelp').value.trim();
  if (!telp) { alert('Masukkan nomor WA terlebih dahulu.'); return; }

  btn.disabled = true;
  const orig = btn.textContent;
  btn.textContent = 'Mengirim...';
  document.getElementById('waMsg').innerHTML = '';

  try {
    const res = await fetch('{{ route("finance.invoices.sendWa", $invoice) }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ telp }),
    });

    const data = await res.json();
    const msg  = document.getElementById('waMsg');

    if (data.ok) {
      msg.innerHTML = '<span class="text-success">✓ ' + data.message + ' (' + data.sent_at + ')</span>';
      btn.textContent = 'Kirim Ulang';
    } else {
      msg.innerHTML = '<span class="text-danger">✗ ' + data.message + '</span>';
      btn.textContent = orig;
    }
  } catch (e) {
    document.getElementById('waMsg').innerHTML = '<span class="text-danger">✗ Terjadi kesalahan koneksi.</span>';
    btn.textContent = orig;
  }

  btn.disabled = false;
}
</script>

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