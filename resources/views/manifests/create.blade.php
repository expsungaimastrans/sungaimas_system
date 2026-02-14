@extends('layouts.app')
@section('title','Buat Manifest')

@push('styles')
<style>
    .table-wrapper{ max-width:92%; margin:0 auto; }
    .section-note{ font-size:12px; color:#6c757d; }

    #itemsTable{ table-layout:fixed; }
    #itemsTable input, #itemsTable textarea{
        font-size:13px !important;
        padding:6px 8px !important;
    }
    #itemsTable td{ padding:6px !important; vertical-align:middle; }
    #itemsTable th{ padding:10px !important; font-size:13px !important; }

    .x-btn{ cursor:pointer; color:#dc3545; font-size:18px; line-height:1; }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <div class="page-title h4 mb-0">Buat Manifest</div>
        <div class="text-muted">Pilih nota → otomatis jadi daftar muatan (1 nota = 1 baris)</div>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="/manifests" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body content-card-body">
        <form method="POST" action="/manifests">
            @csrf

            <div class="section-title">Header Manifest</div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Sopir</label>
                    <input name="sopir" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nopol</label>
                    <input name="nopol" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tanggal Muat</label>
                    <input type="date" name="tanggal_muat" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nama Kapal</label>
                    <input name="nama_kapal" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Keberangkatan</label>
                    <input type="datetime-local" name="keberangkatan" class="form-control">
                </div>
            </div>

            {{-- ===================== PILIH NOTA (TABLE) ===================== --}}
            <div class="section-title mt-4">Pilih Nota</div>
            <div class="section-note mb-2">Ketik untuk mencari nota, lalu klik “Tambah”. Nota yang sudah ditambahkan akan terkunci.</div>

            <div class="row g-2 align-items-end mb-2">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Cari Nota (No Nota / Pengirim / Penerima / Tujuan)</label>
                    <input type="text" id="searchShipment" class="form-control" placeholder="contoh: SMF / Bajawa / Gallery">
                </div>
                <div class="col-md-4">
                    <div id="shipmentHint" class="text-muted small mt-4"></div>
                </div>
            </div>

            <div class="table-responsive mb-3">
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

            {{-- ===================== DETAIL MUATAN ===================== --}}
            <div class="section-title mt-4">Detail Muatan (Manifest Items)</div>
            <div class="text-muted small mb-2">
                Jika 1 nota punya beberapa barang, kolom “Jenis Barang” akan otomatis menampilkan daftar barang per baris.
            </div>

            <div class="table-wrapper">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:16%;">Kode / No Nota</th>
                                <th style="width:28%;">Jenis Barang (dari nota)</th>
                                <th style="width:18%;">Pengirim</th>
                                <th style="width:18%;">Penerima</th>
                                <th style="width:10%;" class="text-center">Tujuan</th>
                                <th style="width:7%;" class="text-center">Koli</th>
                                <th style="width:7%;" class="text-center">Kg</th>
                                <th style="width:12%;" class="text-end">Harga</th>
                                <th style="width:4%;" class="text-center">#</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="5" class="text-end fw-bold">TOTAL</td>
                                <td class="text-center fw-bold" id="sumKoli">0</td>
                                <td class="text-center fw-bold" id="sumKg">0,00</td>
                                <td class="text-end fw-bold" id="sumHarga">Rp 0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    <button type="submit" class="btn btn-brand" style="min-width:240px;">
                        💾 Simpan Manifest
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let searchTimer = null;
let idx = 0;

// anti double add pada halaman create
const addedShipments = new Set();

function esc(s){
  return String(s ?? '')
    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
    .replaceAll('"','&quot;').replaceAll("'","&#039;");
}

function moneyIDR(n){
  n = Number(n || 0);
  return 'Rp ' + n.toLocaleString('id-ID');
}

function recalcTotals(){
  let sumKoli = 0, sumKg = 0, sumHarga = 0;
  document.querySelectorAll('#itemsTable tbody tr').forEach(tr => {
    sumKoli += Number(tr.querySelector('.cell-koli')?.dataset.value || 0);
    sumKg   += Number(tr.querySelector('.cell-kg')?.dataset.value || 0);
    sumHarga+= Number(tr.querySelector('.cell-harga')?.dataset.value || 0);
  });
  document.getElementById('sumKoli').textContent = sumKoli.toLocaleString('id-ID');
  document.getElementById('sumKg').textContent = sumKg.toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
  document.getElementById('sumHarga').textContent = moneyIDR(sumHarga);
}

function removeRow(el){
  el.closest('tr').remove();
  recalcTotals();
}

function appendManifestRow(data){
  const tb = document.querySelector('#itemsTable tbody');
  const tr = document.createElement('tr');
  tr.setAttribute('data-shipment-id', data.shipment_id || '');

  tr.innerHTML = `
    <td>
      <input type="hidden" name="items[${idx}][shipment_id]" value="${data.shipment_id || ''}">
      <input name="items[${idx}][kode]" class="form-control" value="${esc(data.kode || '')}">
    </td>
    <td>
      <textarea name="items[${idx}][jenis_barang]" class="form-control" rows="2" required>${esc(data.jenis_barang || '')}</textarea>
    </td>
    <td><input name="items[${idx}][pengirim]" class="form-control" value="${esc(data.pengirim || '')}"></td>
    <td><input name="items[${idx}][penerima]" class="form-control" value="${esc(data.penerima || '')}"></td>
    <td><input name="items[${idx}][tujuan]" class="form-control text-center" value="${esc(data.tujuan || '')}"></td>

    <td class="text-center cell-koli" data-value="${Number(data.koli||0)}">
      <input type="number" name="items[${idx}][koli]" class="form-control text-center" value="${Number(data.koli||0)}" min="0"
        oninput="this.closest('td').dataset.value=this.value; recalcTotals();">
    </td>

    <td class="text-center cell-kg" data-value="${Number(data.kg||0)}">
      <input type="number" step="0.01" name="items[${idx}][kg]" class="form-control text-center" value="${Number(data.kg||0)}"
        oninput="this.closest('td').dataset.value=this.value; recalcTotals();">
    </td>

    <td class="text-end cell-harga" data-value="${Number(data.harga||0)}">
      <input type="number" name="items[${idx}][harga]" class="form-control text-end" value="${Number(data.harga||0)}"
        oninput="this.closest('td').dataset.value=this.value; recalcTotals();">
    </td>

    <td class="text-center"><span class="x-btn" onclick="removeRow(this)">✖</span></td>
  `;

  tb.prepend(tr);
  idx++;
  recalcTotals();
}

// ============ SEARCH TABLE ============
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
    const already = addedShipments.has(String(s.id));
    const btn = already
      ? `<button type="button" class="btn btn-sm btn-outline-secondary" disabled>Sudah</button>`
      : `<button type="button" class="btn btn-sm btn-brand" onclick="addFromShipment(${s.id}, this)">Tambah</button>`;

    tbody.innerHTML += `
      <tr id="sr-${s.id}">
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

async function addFromShipment(id, btnEl){
  if(addedShipments.has(String(id))) return;
  btnEl.disabled = true;
  btnEl.textContent = '...';

  try{
    const res = await fetch(`/api/shipments/${id}`);
    const s = await res.json();

    // 1 nota = 1 row; koli = akumulasi seluruh item
    let totalKoli = 0;
    let totalKg = 0;
    let barangLines = [];

    (s.items || []).forEach((it, i) => {
      totalKoli += Number(it.koli ?? it.jumlah ?? 0);
      totalKg += Number(it.berat_kg ?? 0);
      barangLines.push(`${i+1}) ${(it.nama_barang || '').toUpperCase()}`);
    });

    appendManifestRow({
      shipment_id: s.id,
      kode: s.no_nota,
      koli: totalKoli,
      kg: totalKg,
      jenis_barang: barangLines.join("\n"),
      pengirim: s.nama_pengirim,
      penerima: s.nama_penerima,
      tujuan: s.tujuan,
      harga: Number(s.harga_total || 0)
    });

    addedShipments.add(String(id));
    btnEl.className = 'btn btn-sm btn-outline-secondary';
    btnEl.textContent = 'Sudah';

  }catch(e){
    console.error(e);
    btnEl.disabled = false;
    btnEl.textContent = 'Tambah';
    alert('Gagal mengambil data nota.');
  }
}

document.addEventListener('DOMContentLoaded', () => loadSearchResults(''));
</script>
@endpush
