@extends('layouts.app')
@section('title','Buat Tagihan')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Buat Tagihan</div>
    <div class="text-muted">Pilih manifest → pilih nota → simpan tagihan</div>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ url('/finance') }}" class="btn btn-outline-secondary">Kembali</a>
    <a href="{{ route('finance.invoices.index') }}" class="btn btn-outline-primary">Daftar Tagihan</a>
  </div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body d-flex gap-3 align-items-end flex-wrap">
    <div style="min-width:320px; flex:1;">
      <label class="form-label fw-semibold">Pilih Manifest</label>
      <select id="manifestId" class="form-select">
        @foreach($manifests as $m)
          <option value="{{ $m->id }}">
            {{ $m->no_manifest }} — {{ $m->unpaid_count }}/{{ $m->total_count }} belum lunas
          </option>
        @endforeach
      </select>
    </div>

    <button type="button" class="btn btn-outline-secondary" id="btnLoad">Muat Nota</button>
  </div>
</div>

<form method="POST" action="{{ route('finance.invoices.store') }}" id="invoiceForm">
  @csrf

  <div class="card shadow-sm">
    <div class="card-body">

      <div class="mb-2 fw-semibold">Pilih Nota untuk Tagihan</div>
      <div class="text-muted small mb-3" id="loadingText">Silakan pilih manifest lalu klik “Muat Nota”.</div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Ditagihkan kepada (Nama / Toko / Perusahaan)</label>
        <input type="text" class="form-control" name="billed_to" required
               placeholder="Contoh: Toko Sinar Jaya / PT ABC">
      </div>

      <input type="hidden" name="manifest_id" id="manifestHidden">

      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead>
            <tr class="text-center">
              <th style="width:45px;">
                <input type="checkbox" id="checkAll">
              </th>
              <th style="width:170px;">No Nota</th>
              <th>Penerima</th>
              <th style="width:160px;">Tujuan</th>
              <th style="width:150px;">Status Bayar</th>
              <th style="width:160px;">Total</th>
            </tr>
          </thead>
          <tbody id="rows">
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Belum ada data.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted small" id="selectedInfo">0 nota dipilih</div>
        <button class="btn btn-brand" type="submit">Simpan Tagihan</button>
      </div>

    </div>
  </div>
</form>

<script>
const csrf = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const rowsEl = document.getElementById('rows');
const loadingText = document.getElementById('loadingText');
const manifestSel = document.getElementById('manifestId');
const manifestHidden = document.getElementById('manifestHidden');
const selectedInfo = document.getElementById('selectedInfo');

function rupiah(n){
  n = Number(n || 0);
  return 'Rp ' + n.toLocaleString('id-ID');
}

function badgeClass(status){
  status = (status || '').toUpperCase();
  if(status === 'LUNAS') return 'text-bg-success';
  if(status === 'PIUTANG') return 'text-bg-warning';
  if(status === 'BATAL') return 'text-bg-danger';
  return 'text-bg-secondary';
}

function renderRows(data){
  rowsEl.innerHTML = '';

  if(!data || data.length === 0){
    rowsEl.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada nota di manifest ini.</td></tr>`;
    selectedInfo.textContent = '0 nota dipilih';
    return;
  }

  data.forEach(s => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="text-center">
        <input type="checkbox" class="rowCheck" name="shipment_ids[]" value="${s.id}">
      </td>
      <td class="text-center fw-semibold">${s.no_nota ?? '-'}</td>
      <td>${s.nama_penerima ?? '-'}</td>
      <td class="text-center">${s.tujuan ?? '-'}</td>
      <td class="text-center">
        <span class="badge ${badgeClass(s.status_pembayaran)}">${s.status_pembayaran ?? 'UNKNOWN'}</span>
      </td>
      <td class="text-end">${rupiah(s.harga_total)}</td>
    `;
    rowsEl.appendChild(tr);
  });

  bindCheckEvents();
}

function bindCheckEvents(){
  const checks = document.querySelectorAll('.rowCheck');
  checks.forEach(ch => ch.addEventListener('change', updateSelectedInfo));

  const checkAll = document.getElementById('checkAll');
  checkAll.checked = false;
  checkAll.onchange = () => {
    checks.forEach(ch => ch.checked = checkAll.checked);
    updateSelectedInfo();
  };

  updateSelectedInfo();
}

function updateSelectedInfo(){
  const checks = document.querySelectorAll('.rowCheck');
  const picked = Array.from(checks).filter(x => x.checked).length;
  selectedInfo.textContent = `${picked} nota dipilih`;
}

async function loadShipments(){
  const manifestId = manifestSel.value;
  manifestHidden.value = manifestId;

  loadingText.textContent = 'Memuat data...';
  rowsEl.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Memuat...</td></tr>`;

  const url = `{{ url('/finance/manifest') }}/${encodeURIComponent(manifestId)}/shipments`;


  const res = await fetch(url, {
    method: 'GET',
    headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrf()
    }
  });

  let json = null;
  try { json = await res.json(); } catch(e){}

  if(!res.ok || !json || !json.ok){
    loadingText.textContent = `Gagal memuat (HTTP ${res.status}).`;
    rowsEl.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Gagal memuat nota.</td></tr>`;
    return;
  }

  loadingText.textContent = `Menampilkan ${json.count} nota dari manifest ini.`;
  renderRows(json.data);
}

document.getElementById('btnLoad').addEventListener('click', loadShipments);

// Auto load pertama kali
document.addEventListener('DOMContentLoaded', () => {
  manifestHidden.value = manifestSel.value;
  loadShipments();
});
</script>
@endsection
