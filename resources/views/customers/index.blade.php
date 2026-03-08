@extends('layouts.app')
@section('title','Data Customer')

@section('content')
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <div class="page-title h4 mb-0">Data Customer</div>
  <div class="d-flex gap-2 flex-wrap mt-2 mt-md-0">
    <a href="{{ route('customers.import.form') }}" class="btn btn-outline-secondary">📥 Import CSV</a>
    <a href="{{ route('customers.export.csv', request()->only('tipe')) }}" class="btn btn-outline-success">📤 Export CSV</a>
    <a href="{{ route('customers.create') }}" class="btn btn-brand">+ Tambah Customer</a>
  </div>
</div>

{{-- Filter --}}
<div class="card shadow-sm mb-3">
  <div class="card-body py-2">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
      <div>
        <label class="form-label small mb-1">Cari</label>
        <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Nama / tujuan / telp" style="width:220px;">
      </div>
      <div>
        <label class="form-label small mb-1">Tipe</label>
        <select name="tipe" class="form-select form-select-sm" style="width:140px;">
          <option value="">Semua</option>
          <option value="PENERIMA" {{ $tipe==='PENERIMA'?'selected':'' }}>Penerima</option>
          <option value="PENGIRIM" {{ $tipe==='PENGIRIM'?'selected':'' }}>Pengirim</option>
        </select>
      </div>
      <button class="btn btn-sm btn-secondary">Cari</button>
      <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-bordered align-middle mb-0">
        <thead class="text-center">
          <tr>
            <th style="width:50px;">#</th>
            <th>Nama</th>
            <th style="width:100px;">Tipe</th>
            <th style="width:150px;">No Telp</th>
            <th style="width:120px;">Tujuan</th>
            <th>Alamat</th>
            <th style="width:140px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
        @forelse($customers as $c)
          <tr>
            <td class="text-center text-muted small">{{ $customers->firstItem() + $loop->index }}</td>
            <td>
              <a href="{{ route('customers.show', $c) }}" class="fw-semibold text-decoration-none">
                {{ $c->nama }}
              </a>
              @if($c->catatan)
                <div class="text-muted small">{{ Str::limit($c->catatan, 60) }}</div>
              @endif
            </td>
            <td class="text-center">
              <span class="badge {{ $c->tipe === 'PENERIMA' ? 'text-bg-info' : 'text-bg-warning' }}">
                {{ $c->tipe }}
              </span>
            </td>
            <td class="text-center">{{ $c->no_telp ?: '-' }}</td>
            <td class="text-center">{{ $c->tujuan ?: '-' }}</td>
            <td class="small text-muted">{{ $c->alamat ?: '-' }}</td>
            <td class="text-center">
              <div class="d-flex gap-1 justify-content-center">
                <a href="{{ route('customers.show', $c) }}" class="btn btn-sm btn-outline-secondary">Riwayat</a>
                <a href="{{ route('customers.edit', $c) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                <form method="POST" action="{{ route('customers.destroy', $c) }}"
                      onsubmit="return confirm('Hapus customer {{ addslashes($c->nama) }}?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">Belum ada data customer.</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@if($customers->hasPages())
  <div class="mt-3">{{ $customers->links() }}</div>
@endif

@endsection