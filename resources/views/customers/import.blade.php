@extends('layouts.app')
@section('title','Import Customer CSV')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="page-title h4 mb-0">Import Customer CSV</div>
  <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <form method="POST" action="{{ route('customers.import') }}" enctype="multipart/form-data">
          @csrf
          <div class="mb-3">
            <label class="form-label fw-semibold">File CSV</label>
            <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
            @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
          <button class="btn btn-brand">📥 Import</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="fw-semibold mb-2">Format CSV</div>
        <div class="text-muted small mb-2">Header baris pertama harus persis:</div>
        <code class="d-block p-2 bg-light rounded small mb-3">
          nama,tipe,no_telp,tujuan,alamat,catatan
        </code>
        <div class="text-muted small mb-1">Nilai kolom <strong>tipe</strong> harus:</div>
        <ul class="small mb-2">
          <li><code>PENERIMA</code> — untuk data penerima</li>
          <li><code>PENGIRIM</code> — untuk data pengirim</li>
        </ul>
        <div class="text-muted small">
          Baris dengan nama + tipe yang sudah ada akan dilewati (tidak duplikat).
        </div>
        <div class="mt-3">
          <a href="{{ route('customers.export.csv') }}" class="btn btn-sm btn-outline-success">
            📤 Download template / export existing
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection