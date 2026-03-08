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
    .btn-simpan-nota{ transform: scale(0.85); transform-origin: center; }
    .remove-btn{ cursor:pointer; color:#dc3545; font-size:18px; line-height: 1; }

    /* Autocomplete */
    .ac-wrapper { position: relative; }
    .ac-dropdown {
        position: absolute;
        top: 100%; left: 0; right: 0;
        z-index: 1050;
        background: #fff;
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 6px 6px;
        max-height: 220px;
        overflow-y: auto;
        box-shadow: 0 4px 12px rgba(0,0,0,.12);
    }
    .ac-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.875rem;
    }
    .ac-item:hover, .ac-item.active { background: #f0f7ff; }
    .ac-item .ac-nama { font-weight: 600; }
    .ac-item .ac-meta { color: #6c757d; font-size: 0.78rem; }
    .ac-item.ac-add { color: #0d6efd; font-style: italic; }
    .ac-item.ac-add:hover { background: #e8f0fe; }

    /* Lock state */
    .field-locked { background: #f8f9fa !important; color: #495057; }
    .lock-badge {
        display: inline-flex; align-items: center; gap: 6px;
        background: #e8f5e9; color: #2e7d32;
        border: 1px solid #a5d6a7;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 0.78rem;
        font-weight: 600;
    }
    .unlock-btn {
        cursor: pointer; color: #6c757d; font-size: 0.75rem;
        text-decoration: underline; border: none; background: none; padding: 0;
    }
    .unlock-btn:hover { color: #dc3545; }

    /* Modal tambah customer */
    #modalTambahCustomer .modal-body .form-label { font-size: 0.875rem; }
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
                    <div class="ac-wrapper">
                        <input type="text" id="nama_pengirim" name="nama_pengirim" class="form-control" required autocomplete="off">
                        <div id="ac-pengirim" class="ac-dropdown" style="display:none;"></div>
                    </div>
                    <div id="lock-pengirim" class="mt-1" style="display:none;">
                        <span class="lock-badge">✓ Tersimpan di database
                            <button type="button" class="unlock-btn" onclick="unlockField('pengirim')">ganti</button>
                        </span>
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">No Telp Pengirim</label>
                    <input type="text" id="telp_pengirim" name="telp_pengirim" class="form-control">
                </div>
            </div>

            <!-- ===================== PENERIMA ===================== -->
            <div class="section-title">Data Penerima</div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nama Penerima</label>
                    <div class="ac-wrapper">
                        <input type="text" id="nama_penerima" name="nama_penerima" class="form-control" required autocomplete="off">
                        <div id="ac-penerima" class="ac-dropdown" style="display:none;"></div>
                    </div>
                    <div id="lock-penerima" class="mt-1" style="display:none;">
                        <span class="lock-badge">✓ Tersimpan di database
                            <button type="button" class="unlock-btn" onclick="unlockField('penerima')">ganti</button>
                        </span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">No Telp Penerima</label>
                    <input type="text" id="telp_penerima" name="telp_penerima" class="form-control" required>
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
                    <textarea id="alamat_penerima" name="alamat_penerima" class="form-control" rows="2" required></textarea>
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

{{-- ===================== MODAL TAMBAH CUSTOMER ===================== --}}
<div class="modal fade" id="modalTambahCustomer" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCustomerTitle">+ Tambah Customer Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <input type="hidden" id="mc_tipe">
                    <input type="hidden" id="mc_target"> {{-- pengirim / penerima --}}

                    <div class="col-12">
                        <label class="form-label fw-semibold">Tipe</label>
                        <input type="text" id="mc_tipe_label" class="form-control" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nama <span class="text-danger">*</span></label>
                        <input type="text" id="mc_nama" class="form-control" placeholder="Nama lengkap">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">No Telp / WA</label>
                        <input type="text" id="mc_telp" class="form-control" placeholder="08xxx">
                    </div>
                    <div class="col-md-6 mc-tujuan-row">
                        <label class="form-label fw-semibold">Kota Tujuan</label>
                        <input type="text" id="mc_tujuan" class="form-control" placeholder="Bajawa / Mbay / ...">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Alamat</label>
                        <textarea id="mc_alamat" class="form-control" rows="2"></textarea>
                    </div>
                    <div id="mc_error" class="col-12 text-danger small" style="display:none;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-brand" id="mc_save_btn" onclick="saveNewCustomer()">Simpan Customer</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ============ TUJUAN TOGGLE ============
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

// ============ LOCK / UNLOCK ============
const lockedFields = { pengirim: false, penerima: false };

function lockField(type) {
    lockedFields[type] = true;
    const nameInput = document.getElementById('nama_' + type);
    const telpInput = document.getElementById('telp_' + type);
    nameInput.readOnly = true;
    nameInput.classList.add('field-locked');
    if (telpInput) { telpInput.readOnly = true; telpInput.classList.add('field-locked'); }
    if (type === 'penerima') {
        const sel = document.getElementById('tujuan_select');
        // Tujuan tetap bisa diubah kalau "Lainnya" dipilih
        document.getElementById('alamat_penerima').readOnly = true;
        document.getElementById('alamat_penerima').classList.add('field-locked');
    }
    document.getElementById('lock-' + type).style.display = 'block';
}

function unlockField(type) {
    lockedFields[type] = false;
    const nameInput = document.getElementById('nama_' + type);
    const telpInput = document.getElementById('telp_' + type);
    nameInput.readOnly = false;
    nameInput.classList.remove('field-locked');
    nameInput.value = '';
    nameInput.focus();
    if (telpInput) { telpInput.readOnly = false; telpInput.classList.remove('field-locked'); telpInput.value = ''; }
    if (type === 'penerima') {
        document.getElementById('tujuan_select').value = '';
        document.getElementById('tujuan_input').value = '';
        toggleTujuan();
        document.getElementById('alamat_penerima').readOnly = false;
        document.getElementById('alamat_penerima').classList.remove('field-locked');
        document.getElementById('alamat_penerima').value = '';
    }
    document.getElementById('lock-' + type).style.display = 'none';
}

// ============ AUTOCOMPLETE ============
function initAC(inputId, dropdownId, tipe, onSelect) {
    const input    = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);
    if (!input || !dropdown) return;

    let timer = null;
    let results = [];
    let activeIdx = -1;
    const fieldType = tipe === 'PENGIRIM' ? 'pengirim' : 'penerima';

    input.addEventListener('input', () => {
        if (input.readOnly) return;
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 1) { dropdown.style.display = 'none'; return; }
        timer = setTimeout(async () => {
            try {
                const res = await fetch(`/api/customers/search?q=${encodeURIComponent(q)}&tipe=${tipe}`);
                results   = await res.json();
                activeIdx = -1;
                render(results, q, tipe);
            } catch(e) { dropdown.style.display = 'none'; }
        }, 200);
    });

    input.addEventListener('keydown', (e) => {
        if (input.readOnly) return;
        const items = dropdown.querySelectorAll('.ac-item:not(.ac-add)');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = Math.min(activeIdx + 1, items.length - 1);
            items.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = Math.max(activeIdx - 1, 0);
            items.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
        } else if (e.key === 'Enter' && activeIdx >= 0) {
            e.preventDefault();
            if (results[activeIdx]) pick(results[activeIdx]);
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
        }
    });

    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !dropdown.contains(e.target))
            dropdown.style.display = 'none';
    });

    function render(list, q, tipe) {
        dropdown.innerHTML = '';
        list.forEach((c) => {
            const div = document.createElement('div');
            div.className = 'ac-item';
            div.innerHTML = `<div class="ac-nama">${esc(c.nama)}</div>
                <div class="ac-meta">${c.no_telp ? '📞 ' + esc(c.no_telp) : ''}${c.tujuan ? ' · 📍 ' + esc(c.tujuan) : ''}</div>`;
            div.addEventListener('mousedown', (e) => { e.preventDefault(); pick(c); });
            dropdown.appendChild(div);
        });

        // Tombol tambah customer baru
        const addDiv = document.createElement('div');
        addDiv.className = 'ac-item ac-add';
        addDiv.innerHTML = `➕ Tambah "<strong>${esc(q)}</strong>" sebagai customer baru (${tipe})`;
        addDiv.addEventListener('mousedown', (e) => {
            e.preventDefault();
            dropdown.style.display = 'none';
            openAddCustomerModal(q, tipe, fieldType);
        });
        dropdown.appendChild(addDiv);

        dropdown.style.display = 'block';
    }

    function pick(c) {
        input.value = c.nama;
        dropdown.style.display = 'none';
        if (onSelect) onSelect(c);
        lockField(fieldType);
    }

    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
}

// ============ MODAL TAMBAH CUSTOMER BARU ============
function openAddCustomerModal(nama, tipe, fieldType) {
    document.getElementById('mc_nama').value      = nama;
    document.getElementById('mc_tipe').value      = tipe;
    document.getElementById('mc_target').value    = fieldType;
    document.getElementById('mc_tipe_label').value= tipe;
    document.getElementById('mc_telp').value      = '';
    document.getElementById('mc_tujuan').value    = '';
    document.getElementById('mc_alamat').value    = '';
    document.getElementById('mc_error').style.display = 'none';

    // Sembunyikan field tujuan untuk pengirim
    document.querySelector('.mc-tujuan-row').style.display = tipe === 'PENGIRIM' ? 'none' : '';

    new bootstrap.Modal(document.getElementById('modalTambahCustomer')).show();
}

async function saveNewCustomer() {
    const btn    = document.getElementById('mc_save_btn');
    const errDiv = document.getElementById('mc_error');
    const nama   = document.getElementById('mc_nama').value.trim();
    const tipe   = document.getElementById('mc_tipe').value;
    const target = document.getElementById('mc_target').value;
    const telp   = document.getElementById('mc_telp').value.trim();
    const tujuan = document.getElementById('mc_tujuan').value.trim();
    const alamat = document.getElementById('mc_alamat').value.trim();

    if (!nama) { errDiv.textContent = 'Nama wajib diisi.'; errDiv.style.display = 'block'; return; }

    btn.disabled = true;
    btn.textContent = 'Menyimpan...';
    errDiv.style.display = 'none';

    try {
        const res = await fetch('/customers', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ nama, tipe, no_telp: telp || null, tujuan: tujuan || null, alamat: alamat || null }),
        });

        const data = await res.json();

        if (!res.ok) {
            errDiv.textContent = data.message ?? 'Gagal menyimpan.';
            errDiv.style.display = 'block';
            btn.disabled = false; btn.textContent = 'Simpan Customer';
            return;
        }

        // Tutup modal & isi form
        bootstrap.Modal.getInstance(document.getElementById('modalTambahCustomer')).hide();

        document.getElementById('nama_' + target).value = nama;
        if (telp)   document.getElementById('telp_' + target).value = telp;
        if (target === 'penerima') {
            if (tujuan) {
                const sel = document.getElementById('tujuan_select');
                let matched = false;
                for (let opt of sel.options) {
                    if (opt.value.toLowerCase() === tujuan.toLowerCase()) {
                        sel.value = opt.value; matched = true; break;
                    }
                }
                if (!matched) { sel.value = 'lainnya'; document.getElementById('tujuan_input').value = tujuan; }
                toggleTujuan();
            }
            if (alamat) document.getElementById('alamat_penerima').value = alamat;
        }

        lockField(target);

    } catch(e) {
        errDiv.textContent = 'Error: ' + e.message;
        errDiv.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Simpan Customer';
    }
}

// ============ INIT AUTOCOMPLETE ============
document.addEventListener('DOMContentLoaded', () => {
    toggleTujuan();
    recalcAll();

    initAC('nama_pengirim', 'ac-pengirim', 'PENGIRIM', (c) => {
        if (c.no_telp) document.getElementById('telp_pengirim').value = c.no_telp;
    });

    initAC('nama_penerima', 'ac-penerima', 'PENERIMA', (c) => {
        if (c.no_telp) document.getElementById('telp_penerima').value = c.no_telp;
        if (c.tujuan) {
            const sel = document.getElementById('tujuan_select');
            let matched = false;
            for (let opt of sel.options) {
                if (opt.value.toLowerCase() === c.tujuan.toLowerCase()) {
                    sel.value = opt.value; matched = true; break;
                }
            }
            if (!matched) { sel.value = 'lainnya'; document.getElementById('tujuan_input').value = c.tujuan; }
            toggleTujuan();
        }
        if (c.alamat) document.getElementById('alamat_penerima').value = c.alamat;
    });
});

// ============ BARANG ============
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

function recalcRow(row){
    const koli  = parseFloat(row.querySelector('.koli')?.value || 0);
    const berat = parseFloat(row.querySelector('.berat')?.value || 0);
    const kubik = parseFloat(row.querySelector('.kubik')?.value || 0);
    const tarif = row.querySelector('.satuanTarif')?.value || 'unit';
    const harga = parseFloat(row.querySelector('.harga')?.value || 0);
    let qty = koli;
    if(tarif === 'kg') qty = berat;
    if(tarif === 'kubik') qty = kubik;
    row.querySelector('.kubik').disabled = (tarif !== 'kubik');
    row.querySelector('.subtotal').value = qty * harga;
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