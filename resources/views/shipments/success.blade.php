@extends('layouts.app')
@section('title','Nota Berhasil')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <div class="page-title h4 mb-0">Nota Berhasil Dibuat</div>
        <div class="text-muted">No Nota: <strong>{{ $shipment->no_nota }}</strong></div>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="/shipments" class="btn btn-outline-secondary">Kembali</a>
        <a href="/shipments/{{ $shipment->id }}/pdf" target="_blank" class="btn btn-primary">Lihat PDF</a>
        <a href="/shipments/{{ $shipment->id }}/pdf" download class="btn btn-success">⬇ Download PDF</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body content-card-body">

        {{-- ===== WA ACTIONS ===== --}}
        <div class="row g-3 mb-4">

            {{-- PENERIMA --}}
            <div class="col-md-6">
                <div class="fw-bold mb-1">Kirim WA ke Penerima</div>
                <div class="text-muted small mb-2">{{ $shipment->nama_penerima }} — {{ $shipment->telp_penerima ?: '-' }}</div>

                @if(!empty($shipment->telp_penerima))
                    {{-- Status indikator --}}
                    <div id="status-penerima" class="mb-2">
                        @if($shipment->wa_penerima_sent_at)
                            <span class="badge text-bg-success">
                                ✓ Terkirim {{ \Carbon\Carbon::parse($shipment->wa_penerima_sent_at)->format('d/m/Y H:i') }}
                            </span>
                        @else
                            <span class="badge text-bg-secondary">Belum dikirim</span>
                        @endif
                    </div>

                    <button class="btn btn-success w-100"
                            onclick="kirimWa('penerima', this)"
                            id="btn-penerima">
                        <span class="icon">📲</span>
                        {{ $shipment->wa_penerima_sent_at ? 'Kirim Ulang ke Penerima' : 'Kirim Nota ke Penerima' }}
                    </button>
                @else
                    <div class="alert alert-warning mb-0">Nomor WA penerima belum diisi.</div>
                @endif
            </div>

            {{-- PENGIRIM --}}
            <div class="col-md-6">
                <div class="fw-bold mb-1">Kirim WA ke Pengirim</div>
                <div class="text-muted small mb-2">{{ $shipment->nama_pengirim }} — {{ $shipment->telp_pengirim ?: '-' }}</div>

                @if(!empty($shipment->telp_pengirim))
                    {{-- Status indikator --}}
                    <div id="status-pengirim" class="mb-2">
                        @if($shipment->wa_pengirim_sent_at)
                            <span class="badge text-bg-success">
                                ✓ Terkirim {{ \Carbon\Carbon::parse($shipment->wa_pengirim_sent_at)->format('d/m/Y H:i') }}
                            </span>
                        @else
                            <span class="badge text-bg-secondary">Belum dikirim</span>
                        @endif
                    </div>

                    <button class="btn btn-success w-100"
                            onclick="kirimWa('pengirim', this)"
                            id="btn-pengirim">
                        <span class="icon">📲</span>
                        {{ $shipment->wa_pengirim_sent_at ? 'Kirim Ulang ke Pengirim' : 'Kirim Nota ke Pengirim' }}
                    </button>
                @else
                    <div class="alert alert-warning mb-0">Nomor WA pengirim belum diisi.</div>
                @endif
            </div>

        </div>

        <hr class="my-4">

        {{-- ===== INFO ===== --}}
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="text-muted small">Tanggal</div>
                <div class="fw-bold">{{ \Carbon\Carbon::parse($shipment->tanggal)->format('d-m-Y') }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Tujuan</div>
                <div class="fw-bold">{{ $shipment->tujuan }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Total</div>
                <div class="fw-bold">Rp {{ number_format((float)$shipment->harga_total,0,',','.') }}</div>
            </div>

            <div class="col-md-6">
                <div class="text-muted small">Pengirim</div>
                <div class="fw-bold">{{ $shipment->nama_pengirim }}</div>
                <div class="text-muted">{{ $shipment->telp_pengirim }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Penerima</div>
                <div class="fw-bold">{{ $shipment->nama_penerima }}</div>
                <div class="text-muted">{{ $shipment->telp_penerima }}</div>
            </div>

            <div class="col-12">
                <div class="text-muted small">Alamat Penerima</div>
                <div class="fw-semibold">{{ $shipment->alamat_penerima }}</div>
            </div>
        </div>

        <hr class="my-4">

        {{-- ===== TABEL BARANG ===== --}}
        <div class="h5 mb-2">Detail Barang</div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr class="text-center">
                        <th style="width:32%;">Barang</th>
                        <th style="width:8%;">Koli</th>
                        <th style="width:10%;">Kg</th>
                        <th style="width:10%;">Kubik (m³)</th>
                        <th style="width:10%;">Tarif</th>
                        <th style="width:15%;">Harga Satuan</th>
                        <th style="width:15%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($shipment->items as $it)
                    @php $tarif = $it->satuan_tarif ?? 'unit'; @endphp
                    <tr>
                        <td class="fw-semibold">{{ $it->nama_barang }}</td>
                        <td class="text-center">{{ (int)($it->koli ?? 0) }}</td>
                        <td class="text-center">{{ number_format((float)($it->berat_kg ?? 0), 2, ',', '.') }}</td>
                        <td class="text-center">{{ number_format((float)($it->kubikasi_m3 ?? 0), 3, ',', '.') }}</td>
                        <td class="text-center">
                            <span class="badge text-bg-success">{{ strtoupper($tarif) }}</span>
                        </td>
                        <td class="text-end">Rp {{ number_format((float)$it->harga_satuan, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float)$it->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($shipment->keterangan)
            <div class="mt-3">
                <div class="text-muted small">Keterangan</div>
                <div class="fw-semibold">{{ $shipment->keterangan }}</div>
            </div>
        @endif

    </div>
</div>

<script>
const SHIPMENT_ID = {{ $shipment->id }};
const CSRF        = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

async function kirimWa(target, btn) {
    const label = target === 'penerima' ? 'Penerima' : 'Pengirim';

    if (!confirm(`Kirim nota PDF ke WhatsApp ${label}?`)) return;

    // Loading state
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengirim...';

    try {
        const res  = await fetch(`/shipments/${SHIPMENT_ID}/send-wa`, {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  CSRF,
                'Accept':        'application/json',
            },
            body: JSON.stringify({ target }),
        });

        const data = await res.json();

        if (data.ok) {
            // Update badge status
            const statusEl = document.getElementById(`status-${target}`);
            statusEl.innerHTML = `<span class="badge text-bg-success">✓ Terkirim ${data.sent_at}</span>`;

            // Update tombol
            btn.disabled  = false;
            btn.innerHTML = `<span class="icon">📲</span> Kirim Ulang ke ${label}`;

            showToast(`✅ Nota berhasil dikirim ke WhatsApp ${label}!`, 'success');
        } else {
            btn.disabled  = false;
            btn.innerHTML = `<span class="icon">📲</span> Kirim Nota ke ${label}`;
            showToast(`❌ Gagal: ${data.message}`, 'danger');
        }

    } catch (e) {
        btn.disabled  = false;
        btn.innerHTML = `<span class="icon">📲</span> Kirim Nota ke ${label}`;
        showToast('❌ Terjadi kesalahan koneksii.', 'danger');
    }
}

function showToast(msg, type = 'success') {
    // Buat toast container jika belum ada
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:280px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `alert alert-${type} shadow`;
    toast.style.cssText = 'margin-bottom:8px;';
    toast.textContent = msg;
    container.appendChild(toast);

    setTimeout(() => toast.remove(), 4000);
}
</script>
@endsection