@extends('layouts.app')
@section('title','Daftar Manifest')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <div class="page-title h4 mb-0">Daftar Manifest</div>
        <div class="text-muted">Riwayat manifest muat truck</div>
    </div>

    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('manifests.create') }}" class="btn btn-brand">+ Buat Manifest</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card shadow-sm">
    <div class="card-body content-card-body">

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>No Manifest</th>
                        <th>Manifest Ke</th>
                        <th>Tanggal Keberangkatan</th>
                        <th>Sopir</th>
                        <th>Nopol</th>
                        <th>Jumlah Nota</th>
                        <th>Status Manifest</th>
                        <th>Total Harga</th>
                        <th style="width:220px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($manifests as $mn)
                    @php
                        $notaCount = $mn->items()->count();
                        $totalHarga = $mn->items()->sum('harga');

                        $statusManifest = $mn->status ?? 'PERSIAPAN';
                        [$statusIcon, $statusClass, $statusLabel] = match($statusManifest) {
                            'DALAM_PERJALANAN' => ['🚚', 'text-bg-primary',   'Dalam Perjalanan'],
                            'SELESAI'          => ['✅', 'text-bg-success',   'Selesai'],
                            default            => ['⏳', 'text-bg-secondary', 'Persiapan'],
                        };
                    @endphp

                    <tr>
                        <td class="text-center fw-bold">{{ $mn->no_manifest }}</td>
                        <td class="text-center">{{ $mn->manifest_ke }}</td>
                        <td class="text-center">
                            {{ $mn->keberangkatan ? \Carbon\Carbon::parse($mn->keberangkatan)->format('d-m-Y') : '-' }}
                        </td>
                        <td>{{ $mn->sopir ?? '-' }}</td>
                        <td class="text-center">{{ $mn->nopol ?? '-' }}</td>
                        <td class="text-center fw-semibold">{{ $notaCount }}</td>
                        <td class="text-center">
                            <span class="badge {{ $statusClass }}">
                                {{ $statusIcon }} {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="text-end fw-semibold">Rp {{ number_format($totalHarga,0,',','.') }}</td>

                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                <a href="/manifests/{{ $mn->id }}" class="btn btn-sm btn-info text-white">
                                    Detail
                                </a>
                                <a href="/manifests/{{ $mn->id }}/edit" class="btn btn-sm btn-warning">
                                    Edit
                                </a>
                                <a href="/manifests/{{ $mn->id }}/pdf" target="_blank" class="btn btn-sm btn-primary">
                                    Cetak
                                </a>
                            </div>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Belum ada data manifest.</td>
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