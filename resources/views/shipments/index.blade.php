@extends('layouts.app')
@section('title','Daftar Nota')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
  $f = $filters ?? [];
  $f_q = $f['q'] ?? '';
  $f_tujuan = $f['tujuan'] ?? '';
  $f_penerima = $f['penerima'] ?? '';
  $f_sp = $f['status_pembayaran'] ?? '';
  $f_sk = $f['status_pengiriman'] ?? '';
  $f_from = $f['from'] ?? '';
  $f_to = $f['to'] ?? '';
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Daftar Nota</div>
    <div class="text-muted">Search & filter nota</div>
  </div>
  <div class="d-flex gap-2 mt-2 mt-md-0">
    <a href="/shipments/create" class="btn btn-brand">+ Buat Nota</a>
  </div>
</div>

{{-- FILTER BAR --}}
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form method="GET" action="{{ route('shipments.index') }}">
      <div class="row g-2 align-items-end">
        <div class="col-lg-4">
          <label class="form-label fw-semibold mb-1">Search</label>
          <input name="q" value="{{ $f_q }}" class="form-control"
                 placeholder="No Nota / Pengirim / Penerima / Telp / Tujuan">
        </div>

        <div class="col-lg-2">
          <label class="form-label fw-semibold mb-1">Tujuan</label>
          <select name="tujuan" class="form-select">
            <option value="">Semua</option>
            @foreach($tujuanOptions as $t)
              <option value="{{ $t }}" {{ $f_tujuan===$t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-lg-2">
          <label class="form-label fw-semibold mb-1">Penerima</label>
          <input name="penerima" value="{{ $f_penerima }}" class="form-control"
                 placeholder="Nama penerima">
        </div>

        <div class="col-lg-2">
          <label class="form-label fw-semibold mb-1">Pembayaran</label>
          <select name="status_pembayaran" class="form-select">
            <option value="">Semua</option>
            @foreach(['BELUM_BAYAR','LUNAS','PIUTANG','BATAL'] as $x)
              <option value="{{ $x }}" {{ $f_sp===$x ? 'selected' : '' }}>{{ $x }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-lg-2">
          <label class="form-label fw-semibold mb-1">Pengiriman</label>
          <select name="status_pengiriman" class="form-select">
            <option value="">Semua</option>
            @foreach(['DITERIMA','DALAM_PENGIRIMAN'] as $x)
              <option value="{{ $x }}" {{ $f_sk===$x ? 'selected' : '' }}>{{ $x }}</option>
            @endforeach
          </select>
        </div>

        @if(!empty($canDateRange) && $canDateRange)
          <div class="col-md-2">
            <label class="form-label small text-muted">Dari</label>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted">Sampai</label>
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
          </div>
        @endif

        <div class="col-lg-8 d-flex gap-2 justify-content-end">
          <button class="btn btn-brand">Terapkan</button>
          <a href="{{ route('shipments.export.csv', request()->query()) }}"
             class="btn btn-outline-success">Export CSV</a>
          <a href="{{ route('shipments.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </div>

      <div class="text-muted small mt-2">
        Menampilkan <b>{{ $shipments->total() }}</b> nota
        @if($f_from || $f_to)
          (range: {{ $f_from ?: '...' }} → {{ $f_to ?: '...' }})
        @endif
      </div>
    </form>
  </div>
</div>

@php
  $rupiah = fn($n) => 'Rp '.number_format((float)$n,0,',','.');
@endphp

<div class="row g-2 mb-3">
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Total Nota (hasil filter)</div>
        <div class="h3 mb-0">{{ $summary['count'] ?? 0 }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Omzet (hasil filter)</div>
        <div class="h3 mb-0">{{ $rupiah($summary['omzet'] ?? 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Piutang (sum)</div>
        <div class="h3 mb-0">{{ $rupiah($summary['piutang'] ?? 0) }}</div>
        <div class="text-muted small mt-1">BELUM_BAYAR: {{ $summary['belum_bayar'] ?? 0 }} nota</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Dalam Pengiriman</div>
        <div class="h3 mb-0">{{ $summary['dalam_pengiriman'] ?? 0 }}</div>
      </div>
    </div>
  </div>
</div>

{{-- TABLE --}}
<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead>
          <tr class="text-center">
            <th style="width:140px;">No Nota</th>
            <th>Detail Barang</th>
            <th>Penerima</th>
            <th style="width:130px;">Tujuan</th>
            <th style="width:140px;">Total</th>
            <th style="width:150px;">Pengiriman</th>
            <th style="width:150px;">Pembayaran</th>
            <th style="width:240px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
        @forelse($shipments as $s)
          <tr>
            <td class="text-center fw-bold">{{ $s->no_nota }}</td>

            {{-- DETAIL BARANG: hanya nama barang --}}
            <td style="font-size:11px;">
              {{ $s->items->pluck('nama_barang')->filter()->implode(', ') ?: '-' }}
            </td>

            <td>{{ $s->nama_penerima }}</td>
            <td class="text-center">{{ $s->tujuan }}</td>
            <td class="text-end">Rp {{ number_format($s->harga_total,0,',','.') }}</td>

            {{-- PENGIRIMAN (read-only, otomatis dari manifest) --}}
            <td class="text-center">
              <span class="badge {{ $s->status_pengiriman === 'DALAM_PENGIRIMAN' ? 'text-bg-primary' : 'text-bg-secondary' }}">
                {{ $s->status_pengiriman }}
              </span>
              @if($s->manifest_id)
                <div class="text-muted small mt-1">Manifest: #{{ $s->manifest_id }}</div>
              @endif
            </td>

            {{-- PEMBAYARAN --}}
            <td class="text-center">
              @php
                $payClass = match($s->status_pembayaran){
                  'LUNAS'      => 'text-bg-success',
                  'PIUTANG'    => 'text-bg-warning',
                  'BATAL'      => 'text-bg-danger',
                  default      => 'text-bg-secondary'
                };
                $canEditPayment = auth()->check()
                  && in_array(strtolower(auth()->user()->role), ['owner', 'finance'], true);
              @endphp

              <span id="pay-{{ $s->id }}" class="badge {{ $payClass }}">
                {{ $s->status_pembayaran }}
              </span>

              @if($canEditPayment)
                <div class="mt-2">
                  <select class="form-select form-select-sm"
                          onchange="setPembayaran({{ $s->id }}, this.value)">
                    <option value="">-- ubah --</option>
                    @foreach(['BELUM_BAYAR','LUNAS','PIUTANG','BATAL'] as $opt)
                      <option value="{{ $opt }}"
                        {{ $s->status_pembayaran === $opt ? 'selected' : '' }}>
                        {{ $opt }}
                      </option>
                    @endforeach
                  </select>
                </div>
              @else
                <div class="small text-muted mt-2">Tidak dapat diubah</div>
              @endif
            </td>

            {{-- AKSI --}}
            <td class="text-center">
              <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="{{ route('shipments.pdfHalf', $s->id) }}" class="btn btn-sm btn-outline-secondary">
                  PDF Half
                </a>
                <a href="/shipments/{{ $s->id }}/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                <a href="/shipments/{{ $s->id }}/success" class="btn btn-sm btn-brand">WA/Download</a>
              </div>
              <div class="small text-muted mt-2" id="msg-{{ $s->id }}"></div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center text-muted py-4">Belum ada nota.</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $shipments->links() }}
    </div>
  </div>
</div>

<script>
function csrf() {
  return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

function showMsg(id, text, ok=true){
  const el = document.getElementById(`msg-${id}`);
  if(!el) return;
  el.textContent = text;
  el.className = ok ? 'small text-success mt-2' : 'small text-danger mt-2';
  setTimeout(()=>{ el.textContent=''; }, 2500);
}

async function setPembayaran(id, status){
  if(!status) return;

  const res = await fetch(`/shipments/${id}/set-pembayaran`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrf(),
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ status })
  });

  let data = null;
  try { data = await res.json(); } catch(e) {}

  if(!res.ok || !data || !data.ok){
    showMsg(id, (data && data.message) ? data.message : `Gagal update (HTTP ${res.status})`, false);
    return;
  }

  const badge = document.getElementById(`pay-${id}`);
  badge.textContent = data.status;
  badge.className = 'badge ' + (
    data.status === 'LUNAS'   ? 'text-bg-success' :
    data.status === 'PIUTANG' ? 'text-bg-warning'  :
    data.status === 'BATAL'   ? 'text-bg-danger'   :
    'text-bg-secondary'
  );

  showMsg(id, 'Status pembayaran diperbarui');
}
</script>
@endsection