@extends('layouts.app')
@section('title','Edit Tagihan - ' . $invoice->invoice_no)

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Edit Tagihan</div>
    <div class="text-muted">
      {{ $invoice->invoice_no }}
      <span class="ms-2 badge {{ $invoice->status === 'LUNAS' ? 'text-bg-success' : 'text-bg-secondary' }}">
        {{ $invoice->status }}
      </span>
    </div>
  </div>
  <div class="d-flex gap-2 flex-wrap mt-2 mt-md-0">
    <a href="{{ route('finance.invoices.list') }}"      class="btn btn-outline-secondary">Kembali</a>
    <a href="{{ route('finance.invoices.pdf', $invoice) }}" class="btn btn-outline-secondary" target="_blank">PDF</a>
  </div>
</div>

@if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

{{-- INFO TAGIHAN --}}
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-4">
        <div class="text-muted small">Ditagihkan Kepada</div>
        <div class="fw-semibold">{{ $invoice->billed_to }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Total Tagihan</div>
        <div class="fw-semibold h5 mb-0" id="grandTotalDisplay">
          Rp {{ number_format($invoice->total, 0, ',', '.') }}
        </div>
      </div>
      <div class="col-md-2">
        <div class="text-muted small">Jumlah Nota</div>
        <div class="fw-semibold" id="itemCountDisplay">{{ $invoice->items->count() }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Dibuat</div>
        <div class="fw-semibold">{{ $invoice->created_at->format('d/m/Y H:i') }}</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">

  {{-- KIRI: NOTA DALAM TAGIHAN --}}
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="fw-semibold">Nota dalam Tagihan</div>
          <div class="text-muted small" id="itemCountLabel">{{ $invoice->items->count() }} nota</div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle" id="itemsTable">
            <thead>
              <tr class="text-center">
                <th style="width:150px;">No Nota</th>
                <th>Penerima</th>
                <th style="width:130px;">Tujuan</th>
                <th style="width:130px;">Total</th>
                <th style="width:90px;">Hapus</th>
              </tr>
            </thead>
            <tbody id="itemsBody">
              @foreach($invoice->items as $item)
                <tr id="row-{{ $item->shipment_id }}" data-total="{{ $item->nilai ?? 0 }}">
                  <td class="text-center fw-semibold">{{ $item->no_nota ?? ($item->shipment->no_nota ?? '-') }}</td>
                  <td>{{ $item->penerima ?? ($item->shipment->nama_penerima ?? '-') }}</td>
                  <td class="text-center">{{ $item->tujuan ?? ($item->shipment->tujuan ?? '-') }}</td>
                  <td class="text-end">Rp {{ number_format($item->nilai ?? 0, 0, ',', '.') }}</td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="removeItem({{ $item->shipment_id }}, this)">
                      &times;
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div id="emptyMsg" class="text-center text-muted py-3" style="{{ $invoice->items->count() > 0 ? 'display:none' : '' }}">
          Belum ada nota dalam tagihan ini.
        </div>
      </div>
    </div>
  </div>

  {{-- KANAN: CARI & TAMBAH NOTA --}}
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="fw-semibold mb-2">Tambah Nota</div>
        <div class="text-muted small mb-3">
          Cari nota yang sudah masuk manifest dan belum ada di tagihan manapun.
        </div>

        <input id="searchInput" class="form-control mb-2"
               placeholder="Cari No Nota / Penerima / Tujuan..."
               autocomplete="off">

        <div id="searchResults" class="border rounded" style="max-height:380px; overflow-y:auto; display:none;">
          {{-- hasil pencarian diisi JS --}}
        </div>

        <div id="searchEmpty" class="text-center text-muted small py-3" style="display:none;">
          Tidak ada nota yang tersedia.
        </div>

        <div id="searchLoading" class="text-center text-muted small py-3" style="display:none;">
          <div class="spinner-border spinner-border-sm me-1"></div> Mencari...
        </div>
      </div>
    </div>
  </div>

</div>

<script>
const INVOICE_ID   = {{ $invoice->id }};
const ADD_URL      = `/finance/invoices/${INVOICE_ID}/add-shipment`;
const REMOVE_URL   = `/finance/invoices/${INVOICE_ID}/remove-shipment`;
const SEARCH_URL   = `/finance/invoices/available-shipments`;

function csrf() {
  return document.querySelector('meta[name="csrf-token"]').content;
}

function rupiah(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

// ── HITUNG ULANG TOTAL & COUNT ──────────────────────────────
function recalcTotal() {
  let total = 0;
  let count = 0;
  document.querySelectorAll('#itemsBody tr').forEach(tr => {
    total += Number(tr.dataset.total || 0);
    count++;
  });
  document.getElementById('grandTotalDisplay').textContent = rupiah(total);
  document.getElementById('itemCountDisplay').textContent  = count;
  document.getElementById('itemCountLabel').textContent    = count + ' nota';
  document.getElementById('emptyMsg').style.display       = count === 0 ? '' : 'none';
}

// ── HAPUS NOTA DARI TAGIHAN ─────────────────────────────────
async function removeItem(shipmentId, btn) {
  if (!confirm('Hapus nota ini dari tagihan?')) return;

  btn.disabled = true;
  btn.textContent = '...';

  try {
    const res = await fetch(`${REMOVE_URL}/${shipmentId}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
    });
    const json = await res.json();

    if (!res.ok || !json.ok) throw new Error(json.message ?? 'Gagal menghapus.');

    const row = document.getElementById(`row-${shipmentId}`);
    if (row) row.remove();
    recalcTotal();

  } catch (e) {
    alert(e.message);
    btn.disabled = false;
    btn.textContent = '×';
  }
}

// ── TAMBAH NOTA KE TAGIHAN ──────────────────────────────────
async function addItem(shipment) {
  const existing = document.getElementById(`row-${shipment.id}`);
  if (existing) { alert('Nota ini sudah ada dalam tagihan.'); return; }

  try {
    const res = await fetch(`${ADD_URL}/${shipment.id}`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json',
                 'Content-Type': 'application/json' }
    });
    const json = await res.json();
    if (!res.ok || !json.ok) throw new Error(json.message ?? 'Gagal menambah.');

    const nilai = json.nilai ?? shipment.total ?? 0;

    const tr = document.createElement('tr');
    tr.id = `row-${shipment.id}`;
    tr.dataset.total = nilai;
    tr.innerHTML = `
      <td class="text-center fw-semibold">${shipment.no_nota}</td>
      <td>${shipment.penerima}</td>
      <td class="text-center">${shipment.tujuan}</td>
      <td class="text-end">${rupiah(nilai)}</td>
      <td class="text-center">
        <button class="btn btn-sm btn-outline-danger"
                onclick="removeItem(${shipment.id}, this)">×</button>
      </td>
    `;
    document.getElementById('itemsBody').appendChild(tr);
    recalcTotal();

    // Hapus dari hasil pencarian
    const card = document.getElementById(`search-${shipment.id}`);
    if (card) card.remove();

  } catch (e) {
    alert(e.message);
  }
}

// ── SEARCH NOTA TERSEDIA ────────────────────────────────────
let searchTimer = null;

document.getElementById('searchInput').addEventListener('input', function () {
  clearTimeout(searchTimer);
  const q = this.value.trim();

  if (q.length < 1) {
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('searchEmpty').style.display   = 'none';
    return;
  }

  document.getElementById('searchLoading').style.display  = '';
  document.getElementById('searchResults').style.display  = 'none';
  document.getElementById('searchEmpty').style.display    = 'none';

  searchTimer = setTimeout(async () => {
    try {
      const res  = await fetch(`${SEARCH_URL}?q=${encodeURIComponent(q)}&invoice_id=${INVOICE_ID}`,
        { headers: { 'Accept': 'application/json' } });
      const json = await res.json();

      document.getElementById('searchLoading').style.display = 'none';

      const rows = json.rows ?? [];

      if (rows.length === 0) {
        document.getElementById('searchEmpty').style.display = '';
        document.getElementById('searchResults').style.display = 'none';
        return;
      }

      const container = document.getElementById('searchResults');
      container.innerHTML = '';

      rows.forEach(s => {
        // skip yang sudah ada di tabel
        if (document.getElementById(`row-${s.id}`)) return;

        const payClass =
          s.status_pembayaran === 'LUNAS'   ? 'text-bg-success' :
          s.status_pembayaran === 'PIUTANG' ? 'text-bg-warning'  :
          s.status_pembayaran === 'BATAL'   ? 'text-bg-danger'   :
          'text-bg-secondary';

        const el = document.createElement('div');
        el.id = `search-${s.id}`;
        el.className = 'p-2 border-bottom d-flex justify-content-between align-items-start';
        el.style.cursor = 'default';
        el.innerHTML = `
          <div>
            <div class="fw-semibold small">${s.no_nota}</div>
            <div class="text-muted small">${s.penerima} · ${s.tujuan}</div>
            <div class="small">
              ${rupiah(s.total)}
              <span class="badge ${payClass} ms-1">${s.status_pembayaran}</span>
            </div>
          </div>
          <button class="btn btn-sm btn-brand ms-2 flex-shrink-0"
                  onclick='addItem(${JSON.stringify(s)})'>+ Tambah</button>
        `;
        container.appendChild(el);
      });

      container.style.display = container.children.length > 0 ? '' : 'none';
      if (container.children.length === 0) {
        document.getElementById('searchEmpty').style.display = '';
      }

    } catch (e) {
      document.getElementById('searchLoading').style.display = 'none';
      alert('Gagal mencari nota: ' + e.message);
    }
  }, 350);
});
</script>
@endsection