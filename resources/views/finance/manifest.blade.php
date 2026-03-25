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

{{-- ===================== RINGKASAN PER KOTA ===================== --}}
@php
  $kotaStats = [];
  foreach ($shipments as $s) {
    $kota = trim($s->tujuan ?? 'Lainnya');
    if (!isset($kotaStats[$kota])) {
      $kotaStats[$kota] = ['total' => 0, 'lunas' => 0, 'nilai_lunas' => 0, 'nilai_total' => 0];
    }
    $kotaStats[$kota]['total']++;
    $kotaStats[$kota]['nilai_total'] += (float)($s->harga_total ?? 0);
    if ($s->status_pembayaran === 'LUNAS') {
      $kotaStats[$kota]['lunas']++;
      $kotaStats[$kota]['nilai_lunas'] += (float)($s->harga_total ?? 0);
    }
  }
  ksort($kotaStats);

  $grandLunas = $shipments->where('status_pembayaran','LUNAS')->sum('harga_total');
  $grandTotal = $shipments->sum('harga_total');
@endphp

<div class="card shadow-sm mt-3 mb-3">
  <div class="card-body">
    <div class="fw-semibold mb-3">📊 Ringkasan Pembayaran per Kota</div>
    <div class="table-responsive">
      <table class="table table-bordered align-middle table-sm">
        <thead class="table-light">
          <tr class="text-center">
            <th>Kota Tujuan</th>
            <th>Total Nota</th>
            <th>Lunas</th>
            <th>Belum Lunas</th>
            <th>Nilai Lunas</th>
            <th>Nilai Total</th>
            <th>Progress</th>
          </tr>
        </thead>
        <tbody>
          @foreach($kotaStats as $kota => $stat)
            @php
              $pct      = $stat['total'] > 0 ? round($stat['lunas'] / $stat['total'] * 100) : 0;
              $barClass = $pct == 100 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
            @endphp
            <tr>
              <td class="fw-semibold">{{ $kota }}</td>
              <td class="text-center">{{ $stat['total'] }}</td>
              <td class="text-center text-success fw-semibold">{{ $stat['lunas'] }}</td>
              <td class="text-center text-danger">{{ $stat['total'] - $stat['lunas'] }}</td>
              <td class="text-end">Rp {{ number_format($stat['nilai_lunas'],0,',','.') }}</td>
              <td class="text-end">Rp {{ number_format($stat['nilai_total'],0,',','.') }}</td>
              <td style="min-width:120px;">
                <div class="progress" style="height:18px;">
                  <div class="progress-bar {{ $barClass }}" style="width:{{ $pct }}%">{{ $pct }}%</div>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
        <tfoot class="table-light fw-bold">
          <tr>
            <td>TOTAL</td>
            <td class="text-center">{{ $shipments->count() }}</td>
            <td class="text-center text-success">{{ $shipments->where('status_pembayaran','LUNAS')->count() }}</td>
            <td class="text-center text-danger">{{ $shipments->where('status_pembayaran','!=','LUNAS')->count() }}</td>
            <td class="text-end">Rp {{ number_format($grandLunas,0,',','.') }}</td>
            <td class="text-end">Rp {{ number_format($grandTotal,0,',','.') }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

{{-- ===================== BIAYA OPERASIONAL ===================== --}}
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="fw-semibold mb-1">💰 Biaya Operasional Manifest</div>
    <div class="text-muted small mb-3">Catat pengeluaran operasional per lokasi pengurus untuk manifest ini.</div>

    <form method="POST" action="{{ route('finance.manifest.biaya', $manifest) }}">
      @csrf
      @php
        // Mapping kota tujuan ke nama lokasi pengurus
        $lokasiPengurus = [
          'Mbay'       => ['mbay', 'nagekeo'],
          'Ende'       => ['ende'],
          'Bajawa'     => ['bajawa'],
          'Lembor'     => ['lembor'],
          'Ruteng'     => ['ruteng'],
          'Labuan Bajo'=> ['labuan bajo', 'labuanbajo'],
        ];

        // Cek kota mana yang ada dalam manifest ini
        $kotaDiManifest = $shipments->pluck('tujuan')->map(fn($t) => strtolower(trim($t)))->unique();

        $lokasiAktif = collect($lokasiPengurus)->filter(function($keywords) use ($kotaDiManifest) {
          foreach ($keywords as $kw) {
            foreach ($kotaDiManifest as $kota) {
              if (str_contains($kota, $kw)) return true;
            }
          }
          return false;
        })->keys();
      @endphp

      @if($lokasiAktif->isEmpty())
        <div class="text-muted small">Tidak ada kota yang memiliki pengurus dalam manifest ini.</div>
      @else
      <div class="row g-3">
        @foreach($lokasiAktif as $lokasi)
          
          @php $existing = $biayaOps[$lokasi] ?? null; @endphp
          <div class="col-md-4">
            <label class="form-label fw-semibold">{{ $lokasi }}</label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" name="biaya[{{ $lokasi }}]" class="form-control"
                     value="{{ $existing ? (int)$existing['jumlah'] : '' }}"
                     placeholder="0" min="0">
            </div>
            @if($existing)
              <div class="text-muted small mt-1">
                Diupdate: {{ \Carbon\Carbon::parse($existing['updated_at'])->format('d/m/Y H:i') }}
              </div>
            @endif
          </div>
          @endforeach
        </div>
        @endif

      <div class="row g-3 mt-1">
        <div class="col-12">
          <label class="form-label fw-semibold">Keterangan Tambahan</label>
          <input type="text" name="keterangan" class="form-control"
                 value="{{ $biayaOpsKeterangan ?? '' }}"
                 placeholder="Misal: transport, makan sopir, dll">
        </div>
      </div>

      <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        @php
          $totalBiaya     = collect($biayaOps ?? [])->sum('jumlah');
          $totalPemasukan = $grandLunas;
          $netto          = $totalPemasukan - $totalBiaya;
        @endphp
        <div class="text-muted small">
          Total Biaya: <strong>Rp {{ number_format($totalBiaya,0,',','.') }}</strong> &nbsp;|&nbsp;
          Pemasukan Lunas: <strong>Rp {{ number_format($totalPemasukan,0,',','.') }}</strong> &nbsp;|&nbsp;
          Netto: <strong class="{{ $netto >= 0 ? 'text-success' : 'text-danger' }}">
            Rp {{ number_format($netto,0,',','.') }}
          </strong>
        </div>
        <button class="btn btn-brand px-4">💾 Simpan Biaya</button>
      </div>
    </form>
  </div>
</div>

@endsection