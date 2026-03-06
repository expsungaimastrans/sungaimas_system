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

{{-- STATUS PENGIRIMAN MANIFEST --}}
@php
  $statusManifest = $manifest->status ?? 'PERSIAPAN';
  $statusColor = match($statusManifest) {
    'PERSIAPAN'       => 'text-bg-secondary',
    'DALAM_PERJALANAN'=> 'text-bg-primary',
    'SELESAI'         => 'text-bg-success',
    default           => 'text-bg-secondary',
  };
  $statusLabel = match($statusManifest) {
    'PERSIAPAN'       => '⏳ Persiapan',
    'DALAM_PERJALANAN'=> '🚚 Dalam Perjalanan',
    'SELESAI'         => '✅ Selesai',
    default           => $statusManifest,
  };
  $canUpdateStatus = auth()->check() && in_array(auth()->user()->role, ['owner','admin'], true);
@endphp

<div class="card shadow-sm mb-3 border-2 {{ $statusManifest === 'SELESAI' ? 'border-success' : ($statusManifest === 'DALAM_PERJALANAN' ? 'border-primary' : 'border-secondary') }}">
  <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
      <div class="text-muted small mb-1">Status Pengiriman Manifest</div>
      <span class="badge fs-6 {{ $statusColor }}">{{ $statusLabel }}</span>
      @if($statusManifest === 'SELESAI')
        <div class="text-muted small mt-1">Semua nota dalam manifest ini otomatis ditandai <strong>SELESAI</strong>.</div>
      @endif
    </div>
    @if($canUpdateStatus && $statusManifest !== 'SELESAI')
      <div class="d-flex gap-2">
        @if($statusManifest === 'PERSIAPAN')
          <button class="btn btn-primary" onclick="updateStatusManifest('DALAM_PERJALANAN')">
            🚚 Berangkat
          </button>
        @endif
        @if($statusManifest === 'DALAM_PERJALANAN')
          <button class="btn btn-outline-primary" onclick="updateStatusManifest('PERSIAPAN')">
            ← Kembali ke Persiapan
          </button>
          <button class="btn btn-success" onclick="konfirmasiSelesai()">
            ✅ Selesai
          </button>
        @endif
      </div>
    @elseif($canUpdateStatus && $statusManifest === 'SELESAI')
      <button class="btn btn-outline-secondary btn-sm" onclick="updateStatusManifest('DALAM_PERJALANAN')">
        ↩ Batalkan Selesai
      </button>
    @endif
  </div>
</div>

{{-- Modal konfirmasi selesai --}}
<div class="modal fade" id="modalSelesai" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi Selesai</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Tandai manifest <strong>{{ $manifest->no_manifest }}</strong> sebagai <strong>SELESAI</strong>?</p>
        <p class="text-muted small">Semua <strong>{{ $total }} nota</strong> dalam manifest ini akan otomatis diubah status pengirimannya menjadi <strong>SELESAI</strong>.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success" onclick="updateStatusManifest('SELESAI')">✅ Ya, Selesai</button>
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
                <div class="d-flex gap-2 align-items-center">
                  <a class="btn btn-sm btn-outline-secondary"
                     href="{{ asset('storage/'.$s->bukti_bayar_path) }}" target="_blank">
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
<script>
function csrf() {
  return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

function konfirmasiSelesai() {
  new bootstrap.Modal(document.getElementById('modalSelesai')).show();
}

async function updateStatusManifest(status) {
  // tutup modal jika terbuka
  const modal = bootstrap.Modal.getInstance(document.getElementById('modalSelesai'));
  if (modal) modal.hide();

  try {
    const res = await fetch('{{ route("manifests.updateStatus", $manifest) }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf(),
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ status }),
    });

    const data = await res.json();

    if (!res.ok || !data.ok) {
      alert('Gagal update status: ' + (data.message ?? 'Unknown error'));
      return;
    }

    // Reload halaman agar badge dan tombol ikut berubah
    window.location.reload();

  } catch (e) {
    alert('Gagal menghubungi server: ' + e.message);
  }
}
</script>
@endsection