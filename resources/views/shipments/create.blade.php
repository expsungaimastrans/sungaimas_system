@extends('layouts.app')
@section('title','Buat Nota')

@push('styles')
<style>
    .table-wrapper{
        max-width: 92%;
        margin: 0 auto;
    }
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
    #barangTable th{ padding: 7px !important; font-size: 12px !important; }

    #totalHarga{
        height: 38px !important;
        font-size: 14px !important;
        padding: 6px 10px !important;
        border-radius: 12px;
    }

    .btn-simpan-nota{
        transform: scale(0.85);
        transform-origin: center;
    }

    .remove-btn{
        cursor:pointer;
        color:#dc3545;
        font-size:18px;
        line-height: 1;
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <div class="page-title h4 mb-0">Buat Nota</div>
        <div class="text-muted">Form input nota pengiriman</div>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="/shipments" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body content-card-body">

        <form method="POST" action="/shipments">
            @csrf

            <!-- ===================== PENGIRIM ===================== -->
            <div class="section-title">Data Pengirim</div>
            <div class="row g-3 mb-3">
                <div class="col-md-7">
                    <label class="form-label fw-semibold">Nama Pengirim / Toko</label>
                    <input type="text" name="nama_pengirim" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">No Telp Pengirim</label>
                    <input type="text" name="telp_pengirim" class="form-control">
                </div>
            </div>

            <!-- ===================== PENERIMA ===================== -->
            <div class="section-title">Data Penerima</div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nama Penerima</label>
                    <input type="text" name="nama_penerima" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Telp Penerima</label>
                    <input type="text" name="telp_penerima" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tujuan Pengiriman</label>
                    <select class="form-select" id="tujuan_select" onchange="toggleTujuan()" required>
                        <option value="">-- Pilih Tujuan --</option>
                        <option>Labuan Bajo</option>
                        <option>Lembor</option>
                        <option>Ruteng</option>
                        <option>Aimere</option>
                        <option>Wailengga</option>
                        <option>Cancar</option>
                        <option>Bajawa</option>
                        <option>Soa</option>
                        <option>Bowae</option>
                        <option>Mbay</option>
                        <option>Ende</option>
                        <option value="lainnya">Lainnya...</option>
                    </select>

                    <input type="text" name="tujuan" id="tujuan_input"
                           class="form-control mt-2 d-none"
                           placeholder="Masukkan tujuan lain">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Alamat Penerima</label>
                    <textarea name="alamat_penerima" class="form-control" rows="2" required></textarea>
                </div>
            </div>

            <!-- ===================== BARANG ===================== -->
            <div class="detail-barang-header">
                <div class="section-title mb-0">Detail Barang</div>
                <button type="button" class="btn btn-brand btn-sm" onclick="addRow()">+ Tambah Barang</button>
            </div>

            <div class="table-wrapper">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="barangTable">
                        <thead>
                            <tr class="center">
                                <th>Nama Barang</th>
                                <th style="width:80px;">Koli</th>
                                <th style="width:80px;">Kg</th>
                                <th style="width:80px;">m³</th>
                                <th style="width:120px;">Tarif</th>
                                <th style="width:140px;">Harga</th>
                                <th style="width:140px;">Subtotal</th>
                                <th style="width:40px;">#</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr class="item-row">
                                <td><input type="text" name="barang[0][nama]" class="form-control" required></td>

                                <td><input type="number" name="barang[0][koli]" class="form-control koli text-center" min="0" step="1" value="0"></td>

                                <td><input type="number" name="barang[0][berat_kg]" class="form-control berat text-center" min="0" step="0.01" value="0"></td>

                                <td><input type="number" name="barang[0][kubikasi_m3]" class="form-control kubik text-center" min="0" step="0.001" value="0" disabled></td>

                                <td>
                                    <select name="barang[0][satuan_tarif]" class="form-select satuanTarif">
                                        <option value="unit">Unit</option>
                                        <option value="kg">Kg</option>
                                        <option value="kubik">Kubik</option>
                                    </select>
                                </td>

                                <td><input type="number" name="barang[0][harga]" class="form-control harga text-end" min="0" step="1" value="0"></td>

                                <td><input type="number" class="form-control subtotal text-end" readonly></td>

                                <td class="text-center"><span class="remove-btn" onclick="removeRow(this)">✖</span></td>
                            </tr>
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

            <!-- ===================== KETERANGAN ===================== -->
            <div class="mt-3">
                <label class="form-label fw-semibold">Keterangan</label>
                <textarea name="keterangan" class="form-control" rows="2"></textarea>
            </div>

            <div class="d-flex justify-content-center mt-4">
                <button type="submit" class="btn btn-brand w-100 btn-simpan-nota" style="max-width:520px;">
                    💾 Simpan Nota
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleTujuan() {
    const select = document.getElementById('tujuan_select');
    const input  = document.getElementById('tujuan_input');

    if (select.value === 'lainnya') {
        input.classList.remove('d-none');
        input.required = true;
        input.value = '';
    } else {
        input.classList.add('d-none');
        input.required = false;
        input.value = select.value;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    toggleTujuan();
    recalcAll();
});

let index = 1;

function addRow(){
    const tbody = document.querySelector('#barangTable tbody');

    const tr = document.createElement('tr');
    tr.classList.add('item-row');

    tr.innerHTML = `
        <td><input type="text" name="barang[${index}][nama]" class="form-control" required></td>
        <td><input type="number" name="barang[${index}][koli]" class="form-control koli text-center" min="0" step="1" value="0"></td>
        <td><input type="number" name="barang[${index}][berat_kg]" class="form-control berat text-center" min="0" step="0.01" value="0"></td>
        <td><input type="number" name="barang[${index}][kubikasi_m3]" class="form-control kubik text-center" min="0" step="0.001" value="0" disabled></td>
        <td>
            <select name="barang[${index}][satuan_tarif]" class="form-select satuanTarif">
                <option value="unit">Unit</option>
                <option value="kg">Kg</option>
                <option value="kubik">Kubik</option>
            </select>
        </td>
        <td><input type="number" name="barang[${index}][harga]" class="form-control harga text-end" min="0" value="0"></td>
        <td><input type="number" class="form-control subtotal text-end" readonly></td>
        <td class="text-center"><span class="remove-btn" onclick="removeRow(this)">✖</span></td>
    `;
    tbody.appendChild(tr);
    index++;
}

function removeRow(el){
    const tbody = document.querySelector('#barangTable tbody');
    if(tbody.querySelectorAll('tr').length <= 1) return;
    el.closest('tr').remove();
    recalcAll();
}

// ============== HITUNG OTOMATIS ==============
function recalcRow(row){
    const koli   = parseFloat(row.querySelector('.koli')?.value || 0);
    const berat  = parseFloat(row.querySelector('.berat')?.value || 0);
    const kubik  = parseFloat(row.querySelector('.kubik')?.value || 0);
    const tarif  = row.querySelector('.satuanTarif')?.value || 'unit';
    const harga  = parseFloat(row.querySelector('.harga')?.value || 0);

    let qty = koli;
    if(tarif === 'kg') qty = berat;
    if(tarif === 'kubik') qty = kubik;

    row.querySelector('.kubik').disabled = (tarif !== 'kubik');

    const subtotal = qty * harga;
    row.querySelector('.subtotal').value = subtotal;
}

function recalcAll(){
    let total = 0;
    document.querySelectorAll('#barangTable tbody tr').forEach(tr=>{
        recalcRow(tr);
        total += parseFloat(tr.querySelector('.subtotal').value || 0);
    });
    document.getElementById('totalHarga').value = total;
}

document.addEventListener('input', e=>{
    if(e.target.closest('#barangTable')) recalcAll();
});
document.addEventListener('change', e=>{
    if(e.target.classList.contains('satuanTarif')) recalcAll();
});
</script>
@endpush
