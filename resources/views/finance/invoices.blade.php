@extends('layouts.app')
@section('title','Finance - Buat Tagihan')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Buat Tagihan</div>
    <div class="text-muted">Pilih manifest → pilih nota → generate 1 PDF tagihan</div>
  </div>
  <div class="d-flex gap-2 mt-2 mt-md-0">
    <a href="{{ route('finance.index') }}" class="btn btn-outline-secondary">Kembali</a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Pilih Manifest</label>
        <select id="manifestSelect" class="form-select">
          <option value="">-- pilih manifest --</option>
          @foreach($manifests as $m)
            @php
              $st = $stats[$m->id] ?? null;
              $total = (int)($st->total ?? 0);
              $unpaid = (int)($st->unpaid ?? 0);
            @endphp
            <option value="{{ $m->id }}" {{ (int)$manifestId===(int)$m->id ? 'selected' : '' }}>
              {{ $m->no_manifest }} — {{ $unpaid }}/{{ $total }} belum lunas
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-6 text-end">
        <button class="btn btn-outline-secondary" id="btnReload">Muat Nota</button>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div>
        <div class="fw-semibold">Daftar Nota dalam Manifest</div>
        <div class="text-muted small" id="hint">Pilih manifest terlebih dahulu.</div>
      </div>

      <button class="btn btn-brand btn-sm" form="invoiceForm" type="submit" id="btnGenerate" disabled>
        Generate PDF Tagihan
      </button>
    </div>

    <form id="invoiceForm" method="POST" action="{{ route('finance.invoice.generate') }}">
      @csrf
      <input type="hidden" name="manifest_id" id="manifestIdField" value="{{ $manifestId ?: '' }}">

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
    </form>

  </div>
</div>

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

function renderRows(rows){
  const tb = document.getElementById('rows');
  if(!rows || rows.length === 0){
    tb.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada nota di manifest ini.</td></tr>`;
    document.getElementById('btnGenerate').disabled = true;
    document.getElementById('checkAll').disabled = true;
    return;
  }

  tb.innerHTML = rows.map(r => `
    <tr>
      <td class="text-center">
        <input type="checkbox" class="ck" name="shipment_ids[]" value="${r.id}">
      </td>
      <td class="text-center fw-bold">${r.no_nota}</td>
      <td>${r.penerima || '-'}</td>
      <td class="text-center">${r.tujuan || '-'}</td>
      <td class="text-center">${payBadge(r.status_pembayaran || 'BELUM_BAYAR')}</td>
      <td class="text-end">${rupiah(r.total)}</td>
    </tr>
  `).join('');

  document.getElementById('btnGenerate').disabled = false;
  document.getElementById('checkAll').disabled = false;
}

async function loadData(){
  const mid = document.getElementById('manifestSelect').value;
  document.getElementById('manifestIdField').value = mid;
  const hint = document.getElementById('hint');

  if(!mid){
    hint.textContent = 'Pilih manifest terlebih dahulu.';
    renderRows([]);
    return;
  }

  hint.textContent = 'Memuat data...';

  const res = await fetch(`{{ route('finance.invoices.data') }}?manifest_id=${encodeURIComponent(mid)}`, {
    headers: { 'Accept': 'application/json' }
  });
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
  document.getElementById('btnReload').addEventListener('click', (e)=>{
    e.preventDefault();
    loadData();
  });

  document.getElementById('manifestSelect').addEventListener('change', ()=>{
    loadData();
  });

  document.addEventListener('change', (e)=>{
    if(e.target && e.target.id === 'checkAll'){
      document.querySelectorAll('.ck').forEach(x => x.checked = e.target.checked);
    }
  });

  // auto load kalau manifestId sudah ada
  loadData();
});
</script>
@endsection
