@extends('layouts.app')
@section('title','Finance')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Finance</div>
    <div class="text-muted">Kelola pembayaran berdasarkan manifest</div>
  </div>
  <div class="d-flex gap-2 mt-2 mt-md-0">
    <a href="/manifests" class="btn btn-outline-secondary">Daftar Manifest</a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead>
          <tr class="text-center">
            <th style="width:160px;">No Manifest</th>
            <th style="width:120px;">Manifest Ke</th>
            <th>Sopir / Nopol</th>
            <th style="width:140px;">Tanggal Muat</th>
            <th style="width:160px;">Status Manifest</th>
            <th style="width:200px;">Status Pembayaran</th>
            <th style="width:140px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
        @forelse($manifests as $m)
          @php
            $stat   = $unpaidMap[$m->id] ?? null;
            $total  = (int)($stat->total  ?? 0);
            $unpaid = (int)($stat->unpaid ?? 0);

            $statusManifest = $m->status ?? 'PERSIAPAN';
            [$statusIcon, $statusClass, $statusLabel] = match($statusManifest) {
                'DALAM_PERJALANAN' => ['🚚', 'text-bg-primary',   'Dalam Perjalanan'],
                'SELESAI'          => ['✅', 'text-bg-success',   'Selesai'],
                default            => ['⏳', 'text-bg-secondary', 'Persiapan'],
            };
          @endphp
          <tr>
            <td class="text-center fw-bold">{{ $m->no_manifest }}</td>
            <td class="text-center">{{ $m->manifest_ke }}</td>
            <td>
              <div class="fw-semibold">{{ $m->sopir ?: '-' }}</div>
              <div class="text-muted small">{{ $m->nopol ?: '-' }}</div>
            </td>
            <td class="text-center">
              {{ $m->keberangkatan ? \Carbon\Carbon::parse($m->keberangkatan)->format('d/m/Y') : '-' }}
            </td>
            <td class="text-center">
              <span class="badge {{ $statusClass }}">{{ $statusIcon }} {{ $statusLabel }}</span>
            </td>
            <td class="text-center">
              <span class="badge {{ $unpaid>0 ? 'text-bg-warning' : 'text-bg-success' }}">
                {{ $unpaid }}/{{ $total }} nota belum lunas
              </span>
            </td>
            <td class="text-center">
              <a href="{{ route('finance.manifest', $m->id) }}" class="btn btn-sm btn-brand">
                Kelola
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">Belum ada manifest.</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $manifests->links() }}
    </div>
  </div>
</div>
@endsection