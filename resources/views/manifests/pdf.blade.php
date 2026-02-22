<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manifest {{ $manifest->id }}</title>
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: "Times New Roman", serif;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo {
            width: 70px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
        }
        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .small { font-size: 10px; }

        .grid th,
        .grid td {
            border: 1px solid #000;
            padding: 3px 4px;
        }
        .grid th {
            text-align: center;
        }
    </style>
</head>
<body>

{{-- HEADER --}}
<table class="header-table">
    <tr>
        <td width="50%">
            <table>
                <tr>
                    <td width="25%">
                        @if(file_exists(public_path('logo.png')))
                            <img src="{{ public_path('logo.png') }}" class="logo">
                        @endif
                    </td>
                    <td width="75%">
                        <div class="bold">Sungai Mas Trans</div>
                        Jl. Pesapen Selatan No.2/A<br>
                        Sungai Mas - Indonesia 45311<br>
                        Telp. (031) 3550447<br>
                        081330572008 / 082302004004
                    </td>
                </tr>
            </table>
        </td>
        <td width="50%" class="right">
            <div class="title">MANIFEST {{ $manifest->id }}</div>
            <table class="small" style="margin-top:4px;">
                <tr>
                    <td class="bold">Sopir/Nopol</td>
                    <td>: {{ $manifest->sopir ?? '-' }} /{{ $manifest->nopol ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="bold">Tanggal Muat</td>
                    <td>: {{ $manifest->tanggal_muat ? \Carbon\Carbon::parse($manifest->tanggal_muat)->format('d/m/Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="bold">Nama Kapal</td>
                    <td>: {{ $manifest->nama_kapal ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="bold">Keberangkatan</td>
                    <td>: {{ $manifest->jadwal_berangkat ?? '-' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<hr>

@php
    $totalKoli  = 0;
    $totalHarga = 0;
@endphp

<table class="grid">
    <thead>
    <tr>
        <th style="width:25px;">No.</th>
        <th style="width:60px;">Tgl Nota</th>   {{-- ✅ kolom tanggal di sebelah kiri No Nota/Kode --}}
        <th style="width:90px;">Kode</th>
        <th style="width:45px;">Koli</th>
        <th style="width:140px;">Jenis Barang</th>
        <th style="width:110px;">Pengirim</th>
        <th style="width:45px;">Kg</th>
        <th style="width:110px;">Penerima</th>
        <th style="width:90px;">Tujuan</th>
        <th style="width:80px;">Harga</th>
        <th style="width:90px;">Keterangan</th>
    </tr>
    </thead>
    <tbody>
    @foreach($manifest->items as $idx => $item)
        @php
            $s = $item->shipment; // pastikan relasi 'shipment' sudah didefinisikan di model ManifestItem
            $totalKoli  += (int)($item->koli ?? 0);
            $totalHarga += (float)($item->harga ?? 0);
            $tglNota = $s?->tanggal ?? $s?->created_at;
        @endphp
        <tr>
            <td class="center">{{ $idx + 1 }}.</td>
            <td class="center">
                {{ $tglNota ? \Carbon\Carbon::parse($tglNota)->format('d/m/Y') : '-' }}
            </td>
            <td class="center">{{ $s->no_nota ?? '-' }}</td>
            <td class="center">{{ (int)($item->koli ?? 0) }}</td>
            <td>{{ $item->jenis_barang ?? '-' }}</td>
            <td>{{ $s->nama_pengirim ?? '-' }}</td>
            <td class="center">{{ (float)($item->kg ?? 0) }}</td>
            <td>{{ $s->nama_penerima ?? '-' }}</td>
            <td class="center">{{ $s->tujuan ?? '-' }}</td>
            <td class="right">
                {{ number_format((float)($item->harga ?? 0),0,',','.') }}
            </td>
            <td>{{ $item->keterangan ?? '' }}</td>
        </tr>
    @endforeach

    {{-- ✅ baris total: hanya total HARGA, tanpa total koli --}}
    <tr>
        <td colspan="9" class="right bold">Total</td>
        <td class="right bold">{{ number_format($totalHarga,0,',','.') }}</td>
        <td></td>
    </tr>
    </tbody>
</table>

<br>

<div class="small">
    No Manifest: {{ $manifest->no_manifest }}
</div>

</body>
</html>