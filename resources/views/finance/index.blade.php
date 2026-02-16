@extends('layouts.app')
@section('title','Finance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Finance</div>
    <div class="text-muted">Pilih manifest → update pembayaran & upload bukti bayar (COT)</div>
  </div>
  <div class="d-flex gap-2">
    <a href="/finance/tagihan/create" class="btn btn-brand">+ Buat Tagihan (PDF)</a>
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
        <thead class="text-center">
          <tr>
            <th>No Manifest</th>
            <th>Tanggal Muat</th>
            <th>Sopir / Nopol</th>
            <th>Progress Lunas</th>
            <th style="width:160px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($manifests as $m)
            @php
              $badge = ($m->unpaid > 0) ? 'text-bg-warning' : 'text-bg-success';
              $text = "{$m->unpaid}/{$m->total} nota belum lunas";
            @endphp
            <tr>
              <td class="text-center fw-bold">{{ $m->no_manifest }}</td>
              <td class="text-center">{{ $m->tanggal_muat }}</td>
              <td>{{ $m->sopir }} / {{ $m->nopol }}</td>
              <td class="text-center">
                <span class="badge {{ $badge }}">{{ $text }}</span>
              </td>
              <td class="text-center">
                <a href="{{ route('finance.manifest', $m->id) }}" class="btn btn-sm btn-primary">
                    Kelola
                </a>
                
                <a class="btn btn-sm btn-outline-secondary" href="/manifests/{{ $m->id }}/pdf">PDF Manifest</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada manifest.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
