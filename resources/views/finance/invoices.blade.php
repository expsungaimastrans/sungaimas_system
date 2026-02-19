@extends('layouts.app')
@section('title','Finance - Buat Tagihan')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Buat Tagihan</div>
    <div class="text-muted">Filter nota → centang nota → simpan tagihan</div>
  </div>
  <div class="d-flex gap-2 mt-2 mt-md-0">
    <a href="{{ route('finance.index') }}" class="btn btn-outline-secondary">Kembali</a>
    <a href="{{ route('finance.invoices.list') }}" class="btn btn-outline-primary">Daftar Tagihan</a>
  </div>
</div>

@if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

@php
  $tujuanOptions = $tujuanOptions ?? collect();
@endphp

{{-- FILTER BAR --}}
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-lg-4">
        <label class="form-label fw-semibold mb-1">Search</label>
        <input id="f_q" class="form-control" placeholder="No Nota / Penerima / Tujuan">
      </div>

      <div class="col-lg-2">
        <label class="form-label fw-semibold mb-1">Tujuan</label>
        <select id="f_tujuan" class="form-select">
          <option value="">Semua</option>
          @foreach($tujuanOptions as $t)
            <option value="{{ $t }}">{{ $t }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-lg-2">
        <label class="form-label fw-semibold mb-1">Penerima</label>
        <input id="f_penerima" class="form-control" placeholder="Nama penerima">
      </div>

      <div class="col-lg-2">
        <label class="form-label fw-semibold mb-1">Status Bayar</label>
        <select id="f_sp" class="form-select">
          <option value="">Semua</option>
          @foreach(['BELUM_BAYAR','PIUTANG','LUNAS','BATAL'] as $x)
            <option value="{{ $x }}">{{ $x }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-lg-2 d-flex gap-2 justify-content-end">
        <button class="btn btn-brand w-100" id="btnApply">Terapkan</button>
      </div>
    </div>
    <div class="text-muted small mt-2">
      * Menampilkan nota yang sudah masuk manifest dan <b>belum pernah masuk tagihan</b>.
    </div>
  </div>
</div>

<form id="invoiceForm" method="POST" action="{{ route('finance.invoices.store') }}">
  @csrf

  <div class="row g-3">
    {{-- TABEL NOTA --}}
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <div class="fw-semibold">Pilih Nota untuk Tagihan</div>
              <div class="text-muted small" id="hint">Klik "Terapkan" untuk memuat nota.</div>
            </div>
            <div class="text-muted small" id="rowsInfo"></div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead>
                <tr class="text-center">
                  <th style="width:40px;">
                    <input type="checkbox" id="checkAll" disabled>
                  </th>
                  <th style="width:150px;">No Nota</th>
                  <th>Penerima</th>
                  <th style="width:140px;">Tujuan</th>
                  <th style="width:150px;">Status Bayar</th>
                  <th style="width:140px;">Total</th>
                </tr>
              </thead>
              <tbody id="rows">
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    Gunakan filter di atas lalu klik Terapkan.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- PANEL KANAN --}}
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-semibold mb-2">Info Tagihan</div>

          <label class="form-label fw-semibold">Ditagihkan kepada</label>
          <input type="text" name="billed_to" class="form-control" required
                 placeholder="Contoh: Toko Sinar Jaya / PT ABC">

          <hr>

          <div class="d-flex justify-content-between">
            <div class="text-muted">Dipilih</div>
            <div class="fw-semibold" id="selCount">0</div>
          </div>
          <div class="d-flex justify-content-between mt-1">
            <div class="text-muted">Grand Total</div>
            <div class="fw-semibold" id="selTotal">Rp 0</div>
          </div>

          {{-- hidden inputs diisi JS --}}
          <div id="selectedInputs"></div>

          <button class="btn btn-primary w-100 mt-3" type="submit" id="btnSave" disabled>
            Simpan Tagihan
          </button>

          <div class="small text-muted mt-2">
            Setelah dibuat, tagihan muncul di <b>Daftar Tagihan</b>.
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
// ============================================================
// HELPERS
// ============================================================
function rupiah(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

function payBadge(status) {
  const cls =
    status === 'LUNAS'      ? 'text-bg-success' :
    status === 'PIUTANG'    ? 'text-bg-warning'  :
    status === 'BATAL'      ? 'text-bg-danger'   :
    'text-bg-secondary';
  return `<span class="badge ${cls}">${status ?? 'UNKNOWN'}</span>`;
}

// ============================================================
// STATE
// ============================================================
const selected = new Map(); // shipment_id -> harga_total

function rebuildSelectedInputs() {
  const box = document.getElementById('selectedInputs');
  box.innerHTML = '';
  let total = 0;

  selected.forEach((val, id) => {
    total += Number(val || 0);
    const inp = document.createElement('input');
    inp.type  = 'hidden';
    inp.name  = 'shipment_ids[]';
    inp.value = id;
    box.appendChild(inp);
  });

  document.getElementById('selCount').textContent = selected.size;
  document.getElementById('selTotal').textContent = rupiah(total);
  document.getElementById('btnSave').disabled = selected.size < 1;
}

// ============================================================
// RENDER ROWS
// ============================================================
function renderRows(rows) {
  const tbody = document.getElementById('rows');
  tbody.innerHTML = '';

  const checkAll = document.getElementById('checkAll');

  if (!rows || rows.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">
      Tidak ada nota yang tersedia / semua sudah masuk tagihan.
    </td></tr>`;
    checkAll.disabled = true;
    document.getElementById('rowsInfo').textContent = '0 nota';
    return;
  }

  rows.forEach(s => {
    // controller invoiceData mengirim field: id, no_nota, penerima, tujuan, status_pembayaran, total
    const id     = s.id;
    const noNota = s.no_nota  ?? '-';
    const penerima = s.penerima ?? '-';   // ← field dari controller
    const tujuan = s.tujuan   ?? '-';
    const status = s.status_pembayaran ?? 'BELUM_BAYAR';
    const total  = s.total    ?? 0;       // ← field dari controller

    const isChecked = selected.has(String(id));

    const tr = document.createElement('tr');
    tr.dataset.id    = id;
    tr.dataset.total = total;

    tr.innerHTML = `
      <td class="text-center">
        <input type="checkbox" class="rowCheck" data-id="${id}" data-total="${total}"
               ${isChecked ? 'checked' : ''}>
      </td>
      <td class="text-center fw-semibold">${noNota}</td>
      <td>${penerima}</td>
      <td class="text-center">${tujuan}</td>
      <td class="text-center">${payBadge(status)}</td>
      <td class="text-end">${rupiah(total)}</td>
    `;

    tbody.appendChild(tr);
  });

  // bind checkbox events
  tbody.querySelectorAll('.rowCheck').forEach(cb => {
    cb.addEventListener('change', () => {
      const id    = String(cb.dataset.id);
      const total = Number(cb.dataset.total || 0);
      if (cb.checked) selected.set(id, total);
      else             selected.delete(id);
      rebuildSelectedInputs();

      // update checkAll state
      const allChecked = tbody.querySelectorAll('.rowCheck:not(:checked)').length === 0;
      document.getElementById('checkAll').checked = allChecked;
    });
  });

  checkAll.disabled = false;
  document.getElementById('rowsInfo').textContent = `${rows.length} nota`;
}

// ============================================================
// LOAD DATA dari /finance/invoices/data
// ============================================================
async function loadData() {
  const q        = document.getElementById('f_q').value.trim();
  const tujuan   = document.getElementById('f_tujuan').value;
  const penerima = document.getElementById('f_penerima').value.trim();
  const sp       = document.getElementById('f_sp').value;

  const params = new URLSearchParams();
  if (q)        params.set('q', q);
  if (tujuan)   params.set('tujuan', tujuan);
  if (penerima) params.set('penerima', penerima);
  if (sp)       params.set('status_pembayaran', sp);

  const hint = document.getElementById('hint');
  const rows = document.getElementById('rows');

  hint.textContent = 'Memuat data...';
  rows.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">
    <div class="spinner-border spinner-border-sm me-2"></div>Memuat nota...
  </td></tr>`;

  document.getElementById('checkAll').disabled = true;

  let json = null;
  try {
    const res = await fetch(`/finance/invoices/data?${params.toString()}`, {
      headers: { 'Accept': 'application/json' }
    });
    json = await res.json();

    if (!res.ok || !json.ok) {
      throw new Error(json.message ?? `HTTP ${res.status}`);
    }
  } catch (err) {
    hint.textContent = 'Gagal memuat data.';
    rows.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3">
      Error: ${err.message}
    </td></tr>`;
    return;
  }

  const data = json.rows ?? [];
  hint.textContent = data.length > 0
    ? `Menampilkan ${data.length} nota.`
    : 'Tidak ada nota yang tersedia.';

  renderRows(data);
}

// ============================================================
// EVENT LISTENERS
// ============================================================
document.addEventListener('DOMContentLoaded', () => {

  // Tombol Terapkan
  document.getElementById('btnApply').addEventListener('click', e => {
    e.preventDefault();
    loadData();
  });

  // Enter di filter fields
  ['f_q','f_penerima'].forEach(id => {
    document.getElementById(id).addEventListener('keydown', e => {
      if (e.key === 'Enter') { e.preventDefault(); loadData(); }
    });
  });

  // Check All
  document.getElementById('checkAll').addEventListener('change', e => {
    const checked = e.target.checked;
    document.querySelectorAll('.rowCheck').forEach(cb => {
      cb.checked = checked;
      const id    = String(cb.dataset.id);
      const total = Number(cb.dataset.total || 0);
      if (checked) selected.set(id, total);
      else          selected.delete(id);
    });
    rebuildSelectedInputs();
  });

  // Load awal saat halaman dibuka
  loadData();
});
</script>
@endsection