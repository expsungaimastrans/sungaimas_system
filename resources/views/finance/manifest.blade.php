@extends('layouts.app')
@section('title','Finance - Manifest')

@section('content')
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Finance Manifest</div>
    <div class="text-muted">
      {{ $manifest->no_manifest }}
      <span class="ms-2 badge {{ $unpaid>0 ? 'text-bg-warning' : 'text-bg-success' }}">
        {{ $unpaid }}/{{ $total }} nota belum lunas
      </span>
    </div>
  </div>
  <div class="d-flex gap-2 mt-2 mt-md-0">
    <a href="{{ route('finance.index') }}" class="btn btn-outline-secondary">Kembali</a>
    <a href="/manifests/{{ $manifest->id }}/pdf" class="btn btn-outline-secondary">PDF Manifest</a>
  </div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-3">
        <div class="text-muted small">Sopir</div>
        <div class="fw-semibold">{{ $manifest->sopir ?: '-' }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Nopol</div>
        <div class="fw-semibold">{{ $manifest->nopol ?: '-' }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Tanggal Muat</div>
        <div class="fw-semibold">{{ $manifest->tanggal_muat ? \Carbon\Carbon::parse($manifest->tanggal_muat)->format('d/m/Y') : '-' }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Nama Kapal</div>
        <div class="fw-semibold">{{ $manifest->nama_kapal ?: '-' }}</div>
      </div>
    </div>
  </div>
</div>

{{-- FORM TAGIHAN (gabungan nota) --}}

{{-- LIST FINANCE UPDATE --}}
<div class="card shadow-sm">
  <div class="card-body">
    <div class="fw-semibold mb-2">Kelola Pembayaran Nota</div>
    <div class="text-muted small mb-3">
      COD/COT ditentukan di sini. Jika COT dan status LUNAS → wajib upload bukti bayar.
    </div>

    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead>
          <tr class="text-center">
            <th style="width:140px;">No Nota</th>
            <th>Penerima</th>
            <th style="width:130px;">Tujuan</th>
            <th style="width:120px;">Total</th>
            <th style="width:120px;">Tipe Bayar</th>
            <th style="width:140px;">Status</th>
            <th style="width:220px;">Bukti Bayar</th>
            <th style="width:120px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
        @forelse($shipments as $s)
          <tr>
            <td class="text-center fw-bold">{{ $s->no_nota }}</td>
            <td>{{ $s->nama_penerima }}</td>
            <td class="text-center">{{ $s->tujuan }}</td>
            <td class="text-end">Rp {{ number_format($s->harga_total,0,',','.') }}</td>

            <td class="text-center">
              <span class="badge text-bg-light border">{{ $s->tipe_bayar ?: '-' }}</span>
            </td>

            <td class="text-center">
              @php
                $payClass = match($s->status_pembayaran){
                  'LUNAS' => 'text-bg-success',
                  'PIUTANG' => 'text-bg-warning',
                  'BATAL' => 'text-bg-danger',
                  default => 'text-bg-secondary'
                };
              @endphp
              <span class="badge {{ $payClass }}">{{ $s->status_pembayaran }}</span>
              @if($s->paid_at)
                <div class="text-muted small mt-1">Paid: {{ \Carbon\Carbon::parse($s->paid_at)->format('d/m/Y H:i') }}</div>
              @endif
            </td>

            <td>
              @if($s->bukti_bayar_path)
                @php
                  $buktiBayarUrl = str_starts_with($s->bukti_bayar_path, 'http')
                    ? $s->bukti_bayar_path
                    : asset('storage/' . $s->bukti_bayar_path);
                @endphp
                <div class="d-flex gap-2 align-items-center">
                  <a class="btn btn-sm btn-outline-secondary"
                     href="{{ $buktiBayarUrl }}" target="_blank">
                    Lihat
                  </a>
                  <div class="text-muted small">{{ basename($s->bukti_bayar_path) }}</div>
                </div>
              @else
                <div class="text-muted small">Belum ada</div>
              @endif
            </td>

            <td class="text-center">
              <button class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#m{{ $s->id }}">
                Update
              </button>
            </td>
          </tr>

          {{-- MODAL UPDATE --}}
          <div class="modal fade" id="m{{ $s->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content">
                <form method="POST" action="{{ route('finance.shipment.update', $s->id) }}" enctype="multipart/form-data">
                  @csrf
                  <div class="modal-header">
                    <h5 class="modal-title">Update Finance - {{ $s->no_nota }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row g-3">
                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Tipe Bayar</label>
                        <select name="tipe_bayar" class="form-select" required>
                          <option value="COD" {{ ($s->tipe_bayar==='COD')?'selected':'' }}>COD</option>
                          <option value="COT" {{ ($s->tipe_bayar==='COT')?'selected':'' }}>COT</option>
                        </select>
                        <div class="text-muted small mt-1">Jika COT dan status LUNAS → wajib upload bukti.</div>
                      </div>

                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Status Pembayaran</label>
                        <select name="status_pembayaran" class="form-select" required>
                          @foreach(['BELUM_BAYAR','LUNAS','PIUTANG','BATAL'] as $x)
                            <option value="{{ $x }}" {{ ($s->status_pembayaran===$x)?'selected':'' }}>{{ $x }}</option>
                          @endforeach
                        </select>
                      </div>

                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Upload Bukti (jpg/png/pdf)</label>
                        <input type="file" name="bukti_bayar" class="form-control">
                        @if($s->bukti_bayar_path)
                          <div class="text-muted small mt-1">
                            Saat ini: {{ basename($s->bukti_bayar_path) }}
                          </div>
                        @endif
                      </div>
                    </div>

                    <hr>

                    <div class="row g-2">
                      <div class="col-md-6">
                        <div class="text-muted small">Penerima</div>
                        <div class="fw-semibold">{{ $s->nama_penerima }}</div>
                      </div>
                      <div class="col-md-3">
                        <div class="text-muted small">Tujuan</div>
                        <div class="fw-semibold">{{ $s->tujuan }}</div>
                      </div>
                      <div class="col-md-3">
                        <div class="text-muted small">Total</div>
                        <div class="fw-semibold">Rp {{ number_format($s->harga_total,0,',','.') }}</div>
                      </div>
                    </div>

                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-brand">Simpan</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

        @empty
          <tr>
            <td colspan="8" class="text-center text-muted py-4">Belum ada nota.</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', ()=>{
  const all = document.getElementById('checkAll');
  if(all){
    all.addEventListener('change', ()=>{
      document.querySelectorAll('.ck').forEach(x => x.checked = all.checked);
    });
  }
});
</script>
@endsection