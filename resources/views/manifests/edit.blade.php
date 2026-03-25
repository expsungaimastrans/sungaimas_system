@extends('layouts.app')
@section('title','Edit Manifest')

@push('styles')
<style>
    .section-note{ font-size:12px; color:#6c757d; }
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="page-title h4 mb-0">Edit Manifest</div>
        <div class="text-muted">{{ $manifest->no_manifest }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="/manifests/{{ $manifest->id }}/pdf" target="_blank" class="btn btn-brand">🖨 Cetak</a>
        <a href="/manifests" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

<div id="ajaxAlert" class="alert d-none" role="alert"></div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="POST" action="/manifests/{{ $manifest->id }}">
            @csrf
            @method('PUT')

            <div class="section-title">Header Manifest</div>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sopir</label>
                    <input name="sopir" class="form-control" value="{{ $manifest->sopir }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Nopol</label>
                    <input name="nopol" class="form-control" value="{{ $manifest->nopol }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tanggal Muat</label>
                    <input type="date" name="tanggal_muat" class="form-control" value="{{ $manifest->tanggal_muat }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Nama Kapal</label>
                    <input name="nama_kapal" class="form-control" value="{{ $manifest->nama_kapal }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Keberangkatan</label>
                    <input type="datetime-local" name="keberangkatan" class="form-control"
                        value="{{ $manifest->keberangkatan ? \Carbon\Carbon::parse($manifest->keberangkatan)->format('Y-m-d\TH:i') : '' }}">
                </div>
            </div>

            <div class="d-flex justify-content-center mt-4">
                <button class="btn btn-brand px-4">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- STATUS MANIFEST --}}
@php
  $statusManifest = $manifest->status ?? 'PERSIAPAN';
  $statusColor = match($statusManifest) {
    'DALAM_PERJALANAN' => 'text-bg-primary',
    'SELESAI'          => 'text-bg-success',
    default            => 'text-bg-secondary',
  };
  $statusLabel = match($statusManifest) {
    'DALAM_PERJALANAN' => '🚚 Dalam Perjalanan',
    'SELESAI'          => '✅ Selesai',
    default            => '⏳ Persiapan',
  };
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="card shadow-sm mb-4">
  <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
      <div class="text-muted small mb-1">Status Pengiriman Manifest</div>
      <span class="badge fs-6 {{ $statusColor }}">{{ $statusLabel }}</span>
    </div>
    <div class="d-flex gap-2">
      @if($statusManifest === 'PERSIAPAN')
        <button class="btn btn-primary" onclick="updateStatus('DALAM_PERJALANAN')">
          🚚 Berangkat
        </button>
      @elseif($statusManifest === 'DALAM_PERJALANAN')
        <button class="btn btn-outline-primary" onclick="updateStatus('PERSIAPAN')">
          ← Kembali ke Persiapan
        </button>
        <button class="btn btn-success" onclick="konfirmasiSelesai()">
          ✅ Selesai
        </button>
      @elseif($statusManifest === 'SELESAI')
        <button class="btn btn-outline-secondary btn-sm" onclick="updateStatus('DALAM_PERJALANAN')">
          ↩ Batalkan Selesai
        </button>
      @endif
    </div>
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
        <p class="text-muted small">Semua nota dalam manifest ini akan otomatis diubah status pengirimannya menjadi <strong>SELESAI</strong>.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success" onclick="updateStatus('SELESAI')">✅ Ya, Selesai</button>
      </div>
    </div>
  </div>
</div>

<script>
function konfirmasiSelesai() {
  new bootstrap.Modal(document.getElementById('modalSelesai')).show();
}

async function updateStatus(status) {
  const modal = bootstrap.Modal.getInstance(document.getElementById('modalSelesai'));
  if (modal) modal.hide();

  try {
    const res = await fetch('{{ route("manifests.updateStatus", $manifest) }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
    window.location.reload();
  } catch (e) {
    alert('Gagal menghubungi server: ' + e.message);
  }
}
</script>

{{-- ===================== TAMBAH NOTA (TABLE) ===================== --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="section-title mb-2">Tambah Nota ke Manifest</div>
        <div class="section-note mb-3">Cari nota lalu klik “Tambah”. (1 nota = 1 baris, koli diakumulasi).</div>

        <div class="row g-2 align-items-end mb-2">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Cari Nota</label>
                <input type="text" id="searchShipment" class="form-control"
                       placeholder="contoh: SMF / Bajawa / Gallery">
            </div>
            <div class="col-md-4">
                <div id="shipmentHint" class="text-muted small mt-4"></div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="searchResultsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:16%;">No Nota</th>
                        <th class="text-center" style="width:7%;">Koli</th>
                        <th>Jenis Barang</th>
                        <th style="width:18%;">Penerima</th>
                        <th class="text-center" style="width:12%;">Tujuan</th>
                        <th class="text-center" style="width:8%;">Kg</th>
                        <th class="text-center" style="width:110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">Ketik di pencarian untuk melihat hasil.</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</div>

@php
    $totalKoli = 0;
    $totalHarga = 0;
@endphp

<div class="card shadow-sm">
    <div class="card-body">
        <div class="section-title mb-3">Daftar Nota dalam Manifest</div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="manifestItemsTable">
                <thead class="table-light">
                    <tr>
                        <th>No Nota</th>
                        <th>Pengirim</th>
                        <th>Penerima</th>
                        <th class="text-center">Tujuan</th>
                        <th class="text-center" style="width:90px;">Koli</th>
                        <th class="text-end" style="width:140px;">Harga</th>
                        <th class="text-center" style="width:110px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($manifest->items as $it)
                    @php
                        $totalKoli += (float)($it->koli ?? 0);
                        $totalHarga += (float)($it->harga ?? 0);
                    @endphp

                    <tr data-shipment-id="{{ $it->shipment_id }}">
                        <td class="text-center fw-bold">{{ $it->kode }}</td>
                        <td>{{ $it->pengirim }}</td>
                        <td>{{ $it->penerima }}</td>
                        <td class="text-center">{{ $it->tujuan }}</td>

                        <td class="text-center cell-koli" data-value="{{ (float)($it->koli ?? 0) }}">
                            {{ number_format((float)($it->koli ?? 0),0,',','.') }}
                        </td>

                        <td class="text-end cell-harga" data-value="{{ (float)($it->harga ?? 0) }}">
                            Rp {{ number_format((float)($it->harga ?? 0),0,',','.') }}
                        </td>

                        <td class="text-center">
                            @if($it->shipment_id)
                                <button type="button"
                                    class="btn btn-sm btn-danger"
                                    onclick="removeShipmentAjax({{ $manifest->id }}, {{ $it->shipment_id }}, this)">
                                    Hapus
                                </button>
                            @else
                                <span class="text-muted small">Manual</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>

                <tfoot>
                    <tr class="table-light">
                        <td colspan="4" class="text-end fw-bold">TOTAL</td>
                        <td class="text-center fw-bold" id="sumKoli">{{ number_format($totalKoli,0,',','.') }}</td>
                        <td class="text-end fw-bold" id="sumHarga">Rp {{ number_format($totalHarga,0,',','.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
const manifestId = {{ $manifest->id }};

// sudah ada di manifest -> disable tombol tambah
const existingShipments = new Set(
    Array.from(document.querySelectorAll('#manifestItemsTable tbody tr'))
        .map(tr => String(tr.getAttribute('data-shipment-id') || ''))
        .filter(Boolean)
);

let searchTimer = null;

function esc(s){
  return String(s ?? '')
    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
    .replaceAll('"','&quot;').replaceAll("'","&#039;");
}

function showAlert(type, message){
    const el = document.getElementById('ajaxAlert');
    el.classList.remove('d-none','alert-success','alert-danger');
    el.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
    el.textContent = message;
    window.scrollTo({top: 0, behavior: 'smooth'});
    setTimeout(() => el.classList.add('d-none'), 2500);
}

function moneyIDR(n){
    n = Number(n || 0);
    return 'Rp ' + n.toLocaleString('id-ID');
}

function recalcTotals(){
    let sumKoli = 0;
    let sumHarga = 0;

    document.querySelectorAll('#manifestItemsTable tbody tr').forEach(tr => {
        const koli = Number(tr.querySelector('.cell-koli')?.dataset.value || 0);
        const harga = Number(tr.querySelector('.cell-harga')?.dataset.value || 0);
        sumKoli += koli;
        sumHarga += harga;
    });

    document.getElementById('sumKoli').textContent = sumKoli.toLocaleString('id-ID');
    document.getElementById('sumHarga').textContent = moneyIDR(sumHarga);
}

// =================== REMOVE (tanpa reload) ===================
async function removeShipmentAjax(manifestId, shipmentId, btnEl){
    if(!confirm('Hapus nota dari manifest?')) return;

    btnEl.disabled = true;
    btnEl.textContent = '...';

    try {
        const res = await fetch(`/manifests/${manifestId}/remove/${shipmentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });

        if(!res.ok){
            throw new Error('HTTP ' + res.status);
        }

        btnEl.closest('tr').remove();
        existingShipments.delete(String(shipmentId));
        recalcTotals();
        showAlert('success', 'Nota berhasil dihapus dari manifest.');

        const q = document.getElementById('searchShipment').value.trim();
        loadSearchResults(q);

    } catch (e){
        console.error(e);
        showAlert('error', 'Gagal menghapus nota. Coba ulangi.');
        btnEl.disabled = false;
        btnEl.textContent = 'Hapus';
    }
}

// =================== SEARCH TABLE ===================
document.getElementById('searchShipment')?.addEventListener('input', function(){
  clearTimeout(searchTimer);
  const q = this.value.trim();
  searchTimer = setTimeout(() => loadSearchResults(q), 250);
});

async function loadSearchResults(q=''){
  const tbody = document.querySelector('#searchResultsTable tbody');
  tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-3">Loading...</td></tr>`;

  const res = await fetch(`/api/shipments/search?q=${encodeURIComponent(q)}`);
  const data = await res.json();

  document.getElementById('shipmentHint').textContent =
    data.length ? `Menampilkan ${data.length} nota.` : `Tidak ada hasil.`;

  if(!data.length){
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-3">Tidak ada nota.</td></tr>`;
    return;
  }

  tbody.innerHTML = '';
  data.forEach(s => {
    const already = existingShipments.has(String(s.id));
    const btn = already
      ? `<button type="button" class="btn btn-sm btn-outline-secondary" disabled>Sudah</button>`
      : `<button type="button" class="btn btn-sm btn-brand" onclick="addShipmentAjax(${s.id}, this)">Tambah</button>`;

    tbody.innerHTML += `
      <tr>
        <td class="text-center fw-semibold">${esc(s.no_nota)}</td>
        <td class="text-center">${Number(s.total_koli || 0).toLocaleString('id-ID')}</td>
        <td style="white-space:pre-line;">${esc(s.ringkas_barang || '-')}</td>
        <td>${esc(s.nama_penerima || '')}</td>
        <td class="text-center">${esc(s.tujuan || '')}</td>
        <td class="text-center">${Number(s.total_kg || 0).toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
        <td class="text-center">${btn}</td>
      </tr>
    `;
  });
}

// =================== ADD SHIPMENT TO MANIFEST (AJAX POST) ===================
async function addShipmentAjax(shipmentId, btnEl){
  if(existingShipments.has(String(shipmentId))) return;

  btnEl.disabled = true;
  btnEl.textContent = '...';

  try{
    const res = await fetch(`/manifests/${manifestId}/add/${shipmentId}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      }
    });

    if(!res.ok){
      btnEl.disabled = false;
      btnEl.textContent = 'Tambah';
      showAlert('error','Gagal menambahkan nota.');
      return;
    }

    const json = await res.json();
    if(!json.ok){
      btnEl.disabled = false;
      btnEl.textContent = 'Tambah';
      showAlert('error','Nota sudah ada / gagal ditambahkan.');
      return;
    }

    const it = json.item;

    const tbody = document.querySelector('#manifestItemsTable tbody');
    const tr = document.createElement('tr');
    tr.setAttribute('data-shipment-id', it.shipment_id);

    tr.innerHTML = `
      <td class="text-center fw-bold">${esc(it.kode)}</td>
      <td>${esc(it.pengirim)}</td>
      <td>${esc(it.penerima)}</td>
      <td class="text-center">${esc(it.tujuan)}</td>
      <td class="text-center cell-koli" data-value="${Number(it.koli||0)}">${Number(it.koli||0).toLocaleString('id-ID')}</td>
      <td class="text-end cell-harga" data-value="${Number(it.harga||0)}">${moneyIDR(it.harga)}</td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-danger"
          onclick="removeShipmentAjax(${manifestId}, ${it.shipment_id}, this)">Hapus</button>
      </td>
    `;

    tbody.prepend(tr);

    existingShipments.add(String(it.shipment_id));
    recalcTotals();

    btnEl.className = 'btn btn-sm btn-outline-secondary';
    btnEl.textContent = 'Sudah';

    showAlert('success','Nota berhasil ditambahkan.');

  }catch(e){
    console.error(e);
    btnEl.disabled = false;
    btnEl.textContent = 'Tambah';
    showAlert('error','Gagal menambahkan nota.');
  }
}

document.addEventListener('DOMContentLoaded', () => loadSearchResults(''));
</script>
@endpush
