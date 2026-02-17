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
  // controller baru mengirim tujuanOptions
  $tujuanOptions = $tujuanOptions ?? collect();
@endphp

{{-- FILTER BAR (mirip create manifest) --}}
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-lg-4">
        <label class="form-label fw-semibold mb-1">Search</label>
        <input id="f_q" class="form-control"
               placeholder="No Nota / Penerima / Tujuan">
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
        <input id="f_penerima" class="form-control"
               placeholder="Nama penerima">
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
      * Menampilkan nota yang <b>belum pernah masuk tagihan</b>.
      (Kalau kamu pakai aturan “harus sudah masuk manifest”, itu di controller)
    </div>
  </div>
</div>

<form id="invoiceForm" method="POST" action="{{ route('finance.invoices.store') }}">
  @csrf

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <div class="fw-semibold">Pilih Nota untuk Tagihan</div>
              <div class="text-muted small" id="hint">Silakan filter lalu klik Terapkan.</div>
            </div>
            <div class="text-muted small" id="rowsInfo">0 nota</div>
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
                  <td colspan="6" class="text-center text-muted py-4">Belum ada data.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="small text-muted" id="hintEmpty" style="display:none;">
            Tidak ada nota yang cocok / semua sudah masuk tagihan.
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="fw-semibold mb-2">Info Tagihan</div>

          <label class="form-label fw-semibold">Ditagihkan kepada (Nama / Toko / Perusahaan)</label>
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

          {{-- hidden input shipment_ids[] akan ditaruh di sini --}}
          <div id="selectedInputs"></div>

          <button class="btn btn-primary w-100 mt-3" type="submit" id="btnSave" disabled>
            Simpan Tagihan
          </button>

          <div class="small text-muted mt-2">
            Setelah dibuat, tagihan akan muncul di <b>Daftar Tagihan</b>.
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
function rupiah(n){
  n = Number(n || 0);
  return 'Rp ' + n.toLocaleString('id-ID');
}
function payBadge(status){
  const cls =
    status === 'LUNAS' ? 'text-bg-success' :
    status === 'PIUTANG' ? 'text-bg-warning' :
    status === 'BATAL' ? 'text-bg-danger' :
    'text-bg-secondary';
  return `<span class="badge ${cls}">${status}</span>`;
}

let selected = new Map(); // id -> total

function rebuildSelectedInputs(){
  const box = document.getElementById('selectedInputs');
  box.innerHTML = '';

  let total = 0;
  selected.forEach((v, id) => {
    total += Number(v || 0);

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'shipment_ids[]';
    input.value = id;
    box.appendChild(input);
  });

  document.getElementById('selCount').textContent = selected.size;
  document.getElementById('selTotal').textContent = rupiah(total);

  document.getElementById('btnSave').disabled = (selected.size < 1);
}

function updateSaveEnabled(){
  const anyChecked = !!document.querySelector('.ck:checked');
  document.getElementById('btnSave').disabled = !anyChecked;
}

function renderRows(rows){
  const tb = document.getElementById('rows');
  const hintEmpty = document.getElementById('hintEmpty');

  if(!rows || rows.length === 0){
    tb.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada nota.</td></tr>`;
    hintEmpty.style.display = 'block';
    document.getElementById('rowsInfo').textContent = '0 nota';
    document.getElementById('checkAll').disabled = true;
    selected.clear();
    rebuildSelectedInputs();
    return;
  }

  hintEmpty.style.display = 'none';
  document.getElementById('rowsInfo').textContent = `${rows.length} nota`;
  document.getElementById('checkAll').disabled = false;

  tb.innerHTML = rows.map(r => {
    const checked = selected.has(String(r.id)) ? 'checked' : '';
    return `
      <tr data-id="${r.id}" data-total="${r.total}">
        <td class="text-center">
          <input type="checkbox" class="ck" ${checked} value="${r.id}">
        </td>
        <td class="text-center fw-bold">${r.no_nota ?? '-'}</td>
        <td>${r.penerima || '-'}</td>
        <td class="text-center">${r.tujuan || '-'}</td>
        <td class="text-center">${payBadge(r.status_pembayaran || 'BELUM_BAYAR')}</td>
        <td class="text-end">${rupiah(r.total)}</td>
      </tr>
    `;
  }).join('');

  // bind checkbox events
  document.querySelectorAll('.ck').forEach(chk => {
    chk.addEventListener('change', (e) => {
      const tr = e.target.closest('tr');
      const id = String(tr.dataset.id);
      const total = Number(tr.dataset.total || 0);

      if(e.target.checked){
        selected.set(id, total);
      }else{
        selected.delete(id);
      }
      rebuildSelectedInputs();
    });
  });

  rebuildSelectedInputs();
}

async function loadData(){
  const q = document.getElementById('f_q').value.trim();
  const tujuan = document.getElementById('f_tujuan').value;
  const penerima = document.getElementById('f_penerima').value.trim();
  const sp = document.getElementById('f_sp').value;

  const hint = document.getElementById('hint');
  hint.textContent = 'Memuat data...';

  const url = new URL(`{{ route('finance.invoices.data') }}`, window.location.origin);
  if(q) url.searchParams.set('q', q);
  if(tujuan) url.searchParams.set('tujuan', tujuan);
  if(penerima) url.searchParams.set('penerima', penerima);
  if(sp) url.searchParams.set('status_pembayaran', sp);

  const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }});
  const data = await res.json().catch(()=>({ok:false}));

  if(!res.ok || !data.ok){
    hint.textContent = 'Gagal memuat data.';
    renderRows([]);
    return;
  }

  hint.textContent = `Menampilkan ${data.rows.length} nota.`;
  renderRows(data.rows);
}

document.addEventListener('DOMContentLoaded', ()=>{
  document.getElementById('btnApply').addEventListener('click', (e)=>{
    e.preventDefault();
    loadData();
  });

  document.getElementById('checkAll').addEventListener('change', (e)=>{
    const checked = e.target.checked;
    document.querySelectorAll('.ck').forEach(x => {
      x.checked = checked;
      const tr = x.closest('tr');
      const id = String(tr.dataset.id);
      const total = Number(tr.dataset.total || 0);
      if(checked) selected.set(id, total);
      else selected.delete(id);
    });
    rebuildSelectedInputs();
  });

  // load awal
  loadData();
});
</script>
@endsection
