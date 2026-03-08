@extends('layouts.app')
@section('title','Riwayat - '.$customer->nama)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">{{ $customer->nama }}</div>
    <span class="badge {{ $customer->tipe === 'PENERIMA' ? 'text-bg-info' : 'text-bg-warning' }}">
      {{ $customer->tipe }}
    </span>
    @if($customer->tujuan)
      <span class="ms-1 text-muted small">📍 {{ $customer->tujuan }}</span>
    @endif
    @if($customer->no_telp)
      <span class="ms-2 text-muted small">📞 {{ $customer->no_telp }}</span>
    @endif
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-primary btn-sm">Edit</a>
    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
  </div>
</div>

{{-- Statistik --}}
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card shadow-sm text-center">
      <div class="card-body py-3">
        <div class="text-muted small">Total Nota</div>
        <div class="fw-bold fs-4">{{ number_format($stats->total_nota ?? 0) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm text-center">
      <div class="card-body py-3">
        <div class="text-muted small">Total Nilai</div>
        <div class="fw-bold fs-5">Rp {{ number_format($stats->total_nilai ?? 0, 0, ',', '.') }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-sm text-center">
      <div class="card-body py-3">
        <div class="text-muted small">Rata-rata per Nota</div>
        @php $avg = ($stats->total_nota ?? 0) > 0 ? ($stats->total_nilai / $stats->total_nota) : 0; @endphp
        <div class="fw-bold fs-5">Rp {{ number_format($avg, 0, ',', '.') }}</div>
      </div>
    </div>
  </div>
</div>

@if($customer->alamat || $customer->catatan)
<div class="card shadow-sm mb-3">
  <div class="card-body py-2">
    <div class="row g-2">
      @if($customer->alamat)
      <div class="col-md-6">
        <div class="text-muted small">Alamat</div>
        <div>{{ $customer->alamat }}</div>
      </div>
      @endif
      @if($customer->catatan)
      <div class="col-md-6">
        <div class="text-muted small">Catatan</div>
        <div>{{ $customer->catatan }}</div>
      </div>
      @endif
    </div>
  </div>
</div>
@endif

{{-- Riwayat Nota --}}
<div class="card shadow-sm">
  <div class="card-body">
    <div class="fw-semibold mb-2">Riwayat Nota</div>
    <div class="table-responsive">
      <table class="table table-bordered align-middle small">
        <thead class="text-center">
          <tr>
            <th>Tanggal</th>
            <th>No Nota</th>
            @if($customer->tipe === 'PENERIMA')
              <th>Pengirim</th>
            @else
              <th>Penerima</th>
            @endif
            <th>Tujuan</th>
            <th>Total</th>
            <th>Status Bayar</th>
            <th>Status Kirim</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        @forelse($shipments as $s)
          <tr>
            <td class="text-center">{{ $s->tanggal ? \Carbon\Carbon::parse($s->tanggal)->format('d/m/Y') : '-' }}</td>
            <td class="text-center fw-semibold">{{ $s->no_nota }}</td>
            @if($customer->tipe === 'PENERIMA')
              <td>{{ $s->nama_pengirim }}</td>
            @else
              <td>{{ $s->nama_penerima }}</td>
            @endif
            <td class="text-center">{{ $s->tujuan }}</td>
            <td class="text-end">Rp {{ number_format($s->harga_total, 0, ',', '.') }}</td>
            <td class="text-center">
              @php
                $pc = match($s->status_pembayaran ?? '') {
                  'LUNAS' => 'text-bg-success', 'PIUTANG' => 'text-bg-warning',
                  'BATAL' => 'text-bg-danger', default => 'text-bg-secondary'
                };
              @endphp
              <span class="badge {{ $pc }}">{{ $s->status_pembayaran ?? '-' }}</span>
            </td>
            <td class="text-center">
              @php
                $kc = match($s->status_pengiriman ?? '') {
                  'DITERIMA' => 'text-bg-secondary', 'DALAM_PENGIRIMAN' => 'text-bg-primary',
                  'SELESAI' => 'text-bg-success', default => 'text-bg-light'
                };
              @endphp
              <span class="badge {{ $kc }}">{{ $s->status_pengiriman ?? '-' }}</span>
            </td>
            <td class="text-center">
              <a href="{{ route('shipments.success', $s->id) }}" class="btn btn-sm btn-outline-secondary">
                Detail
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center text-muted py-3">Belum ada nota.</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
    @if($shipments->hasPages())
      <div class="mt-2">{{ $shipments->links() }}</div>
    @endif
  </div>
</div>
@endsection