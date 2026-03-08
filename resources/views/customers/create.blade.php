@extends('layouts.app')
@section('title', isset($customer) ? 'Edit Customer' : 'Tambah Customer')

@section('content')
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="page-title h4 mb-0">{{ isset($customer) ? 'Edit Customer' : 'Tambah Customer' }}</div>
  <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card shadow-sm" style="max-width:600px;">
  <div class="card-body">
    <form method="POST"
          action="{{ isset($customer) ? route('customers.update', $customer) : route('customers.store') }}">
      @csrf
      @if(isset($customer)) @method('PUT') @endif

      <div class="row g-3">

        <div class="col-12">
          <label class="form-label fw-semibold">Tipe <span class="text-danger">*</span></label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="tipe" id="tipePenerima" value="PENERIMA"
                {{ old('tipe', $customer->tipe ?? '') === 'PENERIMA' ? 'checked' : '' }}>
              <label class="form-check-label" for="tipePenerima">Penerima</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="tipe" id="tipePengirim" value="PENGIRIM"
                {{ old('tipe', $customer->tipe ?? '') === 'PENGIRIM' ? 'checked' : '' }}>
              <label class="form-check-label" for="tipePengirim">Pengirim</label>
            </div>
          </div>
          @error('tipe')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Nama <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                 value="{{ old('nama', $customer->nama ?? '') }}" placeholder="Nama lengkap customer" autofocus>
          @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">No Telp / WhatsApp</label>
          <input type="text" name="no_telp" class="form-control @error('no_telp') is-invalid @enderror"
                 value="{{ old('no_telp', $customer->no_telp ?? '') }}" placeholder="08xxx / 628xxx">
          <div class="text-muted small mt-1">Nomor ini akan dipakai otomatis saat kirim nota via WA.</div>
          @error('no_telp')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6 row-tujuan">
          <label class="form-label fw-semibold">Kota Tujuan</label>
          <input type="text" name="tujuan" class="form-control @error('tujuan') is-invalid @enderror"
                 value="{{ old('tujuan', $customer->tujuan ?? '') }}" placeholder="Bajawa / Mbay / Ende ...">
          @error('tujuan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Alamat</label>
          <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror"
                    rows="2" placeholder="Alamat lengkap (opsional)">{{ old('alamat', $customer->alamat ?? '') }}</textarea>
          @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Catatan</label>
          <textarea name="catatan" class="form-control @error('catatan') is-invalid @enderror"
                    rows="2" placeholder="Catatan tambahan (opsional)">{{ old('catatan', $customer->catatan ?? '') }}</textarea>
          @error('catatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

      </div>

      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-brand">{{ isset($customer) ? 'Simpan Perubahan' : 'Tambah Customer' }}</button>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<script>
// Sembunyikan field tujuan jika tipe PENGIRIM
function toggleTujuan() {
  const tipe = document.querySelector('input[name="tipe"]:checked')?.value;
  const rowTujuan = document.querySelector('.row-tujuan');
  if (rowTujuan) rowTujuan.style.display = tipe === 'PENGIRIM' ? 'none' : '';
}
document.querySelectorAll('input[name="tipe"]').forEach(r => r.addEventListener('change', toggleTujuan));
document.addEventListener('DOMContentLoaded', toggleTujuan);
</script>
@endsection