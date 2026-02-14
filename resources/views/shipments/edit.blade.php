@extends('layouts.app')
@section('title','Edit Nota')

@push('styles')
<style>
    .table-wrapper{ max-width: 92%; margin: 0 auto; }
    .detail-barang-header{
        width: 92%;
        margin: 0 auto 10px;
        display:flex;
        justify-content:space-between;
        align-items:center;
    }

    #barangTable{ table-layout: fixed; margin-bottom: 10px; }
    #barangTable input, #barangTable select{
        height: 32px !important;
        padding: 4px 8px !important;
        font-size: 13px !important;
    }
    #barangTable td{ padding: 4px !important; }
    #barangTable th{ padding: 8px !important; font-size: 13px !important; }

    #totalHarga{
        height: 38px !important;
        font-size: 14px !important;
        padding: 6px 10px !important;
        border-radius: 12px;
    }

    .remove-btn{
        cursor:pointer;
        color:#dc3545;
        font-size:18px;
        line-height: 1;
    }

    /* Timeline cantik */
    .timeline{ position:relative; margin:0; padding:.25rem 0; }
    .timeline:before{
        content:""; position:absolute; left:12px; top:0; bottom:0;
        width:2px; background:rgba(0,0,0,.12);
    }
    .t-item{ position:relative; padding-left:44px; margin-bottom:14px; }
    .t-dot{
        position:absolute; left:4px; top:6px; width:18px; height:18px; border-radius:999px;
        border:2px solid #fff; box-shadow:0 0 0 2px rgba(0,0,0,.08);
    }
    .t-card{
        border:1px solid rgba(0,0,0,.08); border-radius:14px;
        padding:10px 12px; background:#fff;
    }
    .t-head{ display:flex; justify-content:space-between; gap:10px; align-items:flex-start; }
    .t-title{ font-weight:700; letter-spacing:.2px; }
    .t-time{ font-size:12px; color:#6c757d; white-space:nowrap; }
    .t-desc{ margin-top:4px; color:#495057; }
    .t-meta{
        margin-top:8px; background:#f8f9fa; border-radius:12px; padding:8px 10px;
        font-size:12px; color:#495057; white-space:pre-wrap;
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <div class="page-title h4 mb-0">Edit Nota</div>
        <div class="text-muted">{{ $shipment->no_nota }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="/shipments" class="btn btn-outline-secondary">Kembali</a>
        <a href="/shipments/{{ $shipment->id }}/pdf" class="btn btn-outline-secondary">PDF</a>
        <a href="/shipments/{{ $shipment->id }}/success" class="btn btn-brand">WA/Download</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body content-card-body">
        <form method="POST" action="/shipments/{{ $shipment->id }}">
            @csrf
            @method('PUT')

            <div class="section-title">Data Pengirim</div>
            <div class="row g-3 mb-3">
                <div class="col-md-7">
                    <label class="form-label fw-semibold">Nama Pengirim / Toko</label>
                    <input type="text" name="nama_pengirim" class="form-control" required
                           value="{{ old('nama_pengirim', $shipment->nama_pengirim) }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">No Telp Pengirim</label>
                    <input type="text" name="telp_pengirim" class="form-control"
                           value="{{ old('telp_pengirim', $shipment->telp_pengirim) }}">
                </div>
            </div>

            <div class="section-title">Data Penerima</div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nama Penerima</label>
                    <input type="text" name="nama_penerima" class="form-control" required
                           value="{{ old('nama_penerima', $shipment->nama_penerima) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Telp Penerima</label>
                    <input type="text" name="telp_penerima" class="form-control" required
                           value="{{ old('telp_penerima', $shipment->telp_penerima) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tujuan Pengiriman</label>

                    @php
                        $tujuanList = [
                            'Labuan Bajo','Lembor','Ruteng','Aimere','Wailengga','Cancar',
                            'Bajawa','Soa','Bowae','Mbay','Ende'
                        ];
                        $currentTujuan = old('tujuan', $shipment->tujuan);
                        $isLainnya = $currentTujuan && !in_array($currentTujuan, $tujuanList);
                    @endphp

                    <select class="form-select" id="tujuan_select" onchange="toggleTujuan()" required>
                        <option value="">-- Pilih Tujuan --</option>
                        @foreach($tujuanList as $t)
                            <option value="{{ $t }}" {{ $currentTujuan===$t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                        <option value="lainnya" {{ $isLainnya ? 'selected' : '' }}>Lainnya...</option>
                    </select>

                    <input type="text" name="tujuan" id="tujuan_input"
                           class="form-control mt-2 {{ $isLainnya ? '' : 'd-none' }}"
                           placeholder="Masukkan tujuan lain"
                           value="{{ $isLainnya ? $currentTujuan : $currentTujuan }}"
                           {{ $isLainnya ? 'required' : '' }}>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Alamat Penerima</label>
                    <textarea name="alamat_penerima" class="form-control" rows="2" required>{{ old('alamat_penerima', $shipment->alamat_penerima) }}</textarea>
                </div>
            </div>

            <div class="detail-barang-header">
                <div class="section-title mb-0">Detail Barang</div>
                <button type="button" class="btn btn-brand btn-sm" onclick="addRow()">+ Tambah Barang</button>
            </div>

            <div class="table-wrapper">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="barangTable">
                        <thead>
                            <tr class="text-center">
                                <th style="width:34%">Nama Barang</th>
                                <th style="width:7%">Koli</th>
                                <th style="width:7%">Kg</th>
                                <th style="width:7%">m³</th>
                                <th style="width:10%">Tarif</th>
                                <th style="width:12%">Harga</th>
                                <th style="width:12%">Subtotal</th>
                                <th style="width:5%">#</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($shipment->items as $i => $it)
                            @php
                                $tarif = old("barang.$i.satuan_tarif", $it->satuan_tarif ?? 'unit');
                                $koli  = old("barang.$i.koli", (float)($it->koli ?? 0));
                                $kg    = old("barang.$i.berat_kg", (float)($it->berat_kg ?? 0));
                                $m3    = old("barang.$i.kubikasi_m3", (float)($it->kubikasi_m3 ?? 0));
                                $harga = old("barang.$i.harga", (float)($it->harga_satuan ?? 0));
                            @endphp
                            <tr class="item-row">
                                <td>
                                    <input type="text" name="barang[{{ $i }}][nama]" class="form-control" required
                                           value="{{ old("barang.$i.nama", $it->nama_barang) }}">
                                </td>
                                <td><input type="number" step="1" min="0" name="barang[{{ $i }}][koli]" class="form-control koli text-center" value="{{ $koli }}" oninput="hitung()"></td>
                                <td><input type="number" step="0.01" min="0" name="barang[{{ $i }}][berat_kg]" class="form-control kg text-center" value="{{ $kg }}" oninput="hitung()"></td>
                                <td><input type="number" step="0.001" min="0" name="barang[{{ $i }}][kubikasi_m3]" class="form-control m3 text-center" value="{{ $m3 }}" oninput="hitung()"></td>

                                <td>
                                    <select name="barang[{{ $i }}][satuan_tarif]" class="form-select tarif text-center" onchange="hitung()">
                                        <option value="unit" {{ $tarif==='unit' ? 'selected' : '' }}>Unit</option>
                                        <option value="kg" {{ $tarif==='kg' ? 'selected' : '' }}>Kg</option>
                                        <option value="kubik" {{ $tarif==='kubik' ? 'selected' : '' }}>Kubik</option>
                                    </select>
                                </td>

                                <td><input type="number" step="1" min="0" name="barang[{{ $i }}][harga]" class="form-control harga text-end" value="{{ $harga }}" oninput="hitung()" required></td>
                                <td><input type="number" class="form-control subtotal text-end" readonly></td>
                                <td class="text-center"><span class="remove-btn" onclick="removeRow(this)">✖</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end mt-2">
                    <div class="col-md-4">
                        <label class="form-label fw-bold mb-1">TOTAL HARGA (Rp)</label>
                        <input type="number" name="harga_total" id="totalHarga" class="form-control" readonly>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label fw-semibold">Keterangan</label>
                <textarea name="keterangan" class="form-control" rows="2">{{ old('keterangan', $shipment->keterangan) }}</textarea>
            </div>

            <div class="d-flex justify-content-center mt-4">
                <button type="submit" class="btn btn-brand" style="min-width:260px;">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- TIMELINE --}}
<div class="card shadow-sm mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="section-title mb-0">Timeline Nota</div>
            <div class="text-muted small">Aktivitas otomatis sistem</div>
        </div>

        @php
            $color = function($action){
                return match($action){
                    'CREATED' => '#198754',
                    'UPDATED' => '#0d6efd',
                    'PAYMENT_UPDATED' => '#ffc107',
                    'MANIFEST_ADDED' => '#0dcaf0',
                    'MANIFEST_REMOVED' => '#dc3545',
                    default => '#6c757d',
                };
            };
            $label = function($action){
                return match($action){
                    'CREATED' => 'Nota dibuat',
                    'UPDATED' => 'Nota diubah',
                    'PAYMENT_UPDATED' => 'Pembayaran',
                    'MANIFEST_ADDED' => 'Masuk Manifest',
                    'MANIFEST_REMOVED' => 'Keluar Manifest',
                    default => $action,
                };
            };
        @endphp

        @if(($shipment->logs ?? collect())->count() === 0)
            <div class="text-muted">Belum ada aktivitas.</div>
        @else
            <div class="timeline">
                @foreach($shipment->logs as $log)
                    <div class="t-item">
                        <div class="t-dot" style="background: {{ $color($log->action) }}"></div>
                        <div class="t-card">
                            <div class="t-head">
                                <div>
                                    <div class="t-title">
                                        {{ $label($log->action) }}
                                        <span class="badge ms-2" style="background: rgba(0,0,0,.06); color:#212529; border-radius:999px;">
                                            {{ $log->action }}
                                        </span>
                                    </div>
                                    @if($log->description)
                                        <div class="t-desc">{{ $log->description }}</div>
                                    @endif
                                </div>
                                <div class="t-time">{{ $log->logged_at?->format('d-m-Y H:i') }}</div>
                            </div>

                            @if($log->meta)
                                <div class="t-meta">{{ json_encode($log->meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleTujuan() {
    const select = document.getElementById('tujuan_select');
    const input  = document.getElementById('tujuan_input');
    if (!select || !input) return;

    if (select.value === 'lainnya') {
        input.classList.remove('d-none');
        input.required = true;
        if (input.value === select.value) input.value = '';
    } else {
        input.classList.add('d-none');
        input.required = false;
        input.value = select.value; // simpan tujuan
    }
}

let index = {{ $shipment->items->count() ?: 1 }};

function addRow(){
    const tb = document.querySelector('#barangTable tbody');
    const tr = document.createElement('tr');
    tr.classList.add('item-row');
    tr.innerHTML = `
        <td><input type="text" name="barang[${index}][nama]" class="form-control" required></td>
        <td><input type="number" step="1" min="0" name="barang[${index}][koli]" class="form-control koli text-center" value="0" oninput="hitung()"></td>
        <td><input type="number" step="0.01" min="0" name="barang[${index}][berat_kg]" class="form-control kg text-center" value="0" oninput="hitung()"></td>
        <td><input type="number" step="0.001" min="0" name="barang[${index}][kubikasi_m3]" class="form-control m3 text-center" value="0" oninput="hitung()"></td>
        <td>
            <select name="barang[${index}][satuan_tarif]" class="form-select tarif text-center" onchange="hitung()">
                <option value="unit">Unit</option>
                <option value="kg">Kg</option>
                <option value="kubik">Kubik</option>
            </select>
        </td>
        <td><input type="number" step="1" min="0" name="barang[${index}][harga]" class="form-control harga text-end" value="0" oninput="hitung()" required></td>
        <td><input type="number" class="form-control subtotal text-end" readonly></td>
        <td class="text-center"><span class="remove-btn" onclick="removeRow(this)">✖</span></td>
    `;
    tb.appendChild(tr);
    index++;
    hitung();
}

function removeRow(el){
    const tbody = document.querySelector('#barangTable tbody');
    if (tbody.querySelectorAll('tr').length <= 1) return;
    el.closest('tr').remove();
    hitung();
}

function hitung(){
    let total = 0;

    document.querySelectorAll('#barangTable tbody tr').forEach(row => {
        const koli  = parseFloat(row.querySelector('.koli')?.value || 0);
        const kg    = parseFloat(row.querySelector('.kg')?.value || 0);
        const m3    = parseFloat(row.querySelector('.m3')?.value || 0);
        const tarif = (row.querySelector('.tarif')?.value || 'unit');
        const harga = parseFloat(row.querySelector('.harga')?.value || 0);

        // UX: kubikasi hanya aktif jika tarif = kubik
        const m3Input = row.querySelector('.m3');
        if (m3Input) {
            if (tarif === 'kubik') {
                m3Input.disabled = false;
                m3Input.classList.remove('bg-light');
            } else {
                m3Input.disabled = true;
                m3Input.classList.add('bg-light');
            }
        }

        let qty = koli;
        if (tarif === 'kg') qty = kg;
        if (tarif === 'kubik') qty = m3;

        const subtotal = qty * harga;
        row.querySelector('.subtotal').value = isFinite(subtotal) ? subtotal : 0;

        total += (isFinite(subtotal) ? subtotal : 0);
    });

    document.getElementById('totalHarga').value = total;
}

document.addEventListener('DOMContentLoaded', () => {
    toggleTujuan();
    hitung();
});
</script>
@endpush
