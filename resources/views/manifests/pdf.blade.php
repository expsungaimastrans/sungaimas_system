<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manifest - {{ $manifest->no_manifest }}</title>

    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        body {
            margin: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            padding: 4px 0;
        }

        .manifest-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .manifest-sub {
            font-size: 13px;
            text-align: center;
            margin-bottom: 15px;
        }

        th {
            background: #eaeaea;
            border: 1px solid #000;
            padding: 5px;
            font-weight: bold;
            text-align: center;
        }

        td {
            border: 1px solid #000;
            padding: 4px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }

        .summary-row td {
            font-weight: bold;
            background: #f3f3f3;
        }
    </style>
</head>
<body>

    <div class="manifest-title">MANIFEST BARANG</div>
    <div class="manifest-sub">No: {{ $manifest->no_manifest }}</div>

    <table class="header-table" style="margin-bottom: 12px;">
        <tr>
            <td><b>Tanggal Muat</b></td>
            <td>: {{ \Carbon\Carbon::parse($manifest->tanggal_muat)->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <td><b>Manifest Ke</b></td>
            <td>: {{ $manifest->manifest_ke }}</td>
        </tr>
        <tr>
            <td><b>Sopir</b></td>
            <td>: {{ $manifest->sopir }}</td>
        </tr>
        <tr>
            <td><b>Nopol</b></td>
            <td>: {{ $manifest->nopol }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 140px;">No Nota</th>
                <th>Pengirim</th>
                <th>Penerima</th>
                <th style="width: 90px;">Telp</th>
                <th style="width: 90px;">Tujuan</th>
                <th style="width: 90px;">Total</th>
                <th style="width: 120px;">Keterangan</th>
            </tr>
        </thead>

        <tbody>
            @php $no = 1; @endphp

            @foreach($shipments as $s)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td class="text-center">{{ $s->no_nota }}</td>
                <td>{{ $s->nama_pengirim }}</td>
                <td>{{ $s->nama_penerima }}</td>
                <td class="text-center">{{ $s->telp_penerima }}</td>
                <td class="text-center">{{ $s->tujuan }}</td>
                <td class="text-center">{{ $s->koli }}</td>
                <td class="text-right">Rp {{ number_format($s->harga_total, 0, ',', '.') }}</td>
                <td>{{ $s->keterangan }}</td>
            </tr>
            @endforeach

            <tr class="summary-row">
                <td colspan="6" class="text-right">TOTAL</td>
                <td class="text-center">{{ $totalKoli }}</td>
                <td class="text-right">Rp {{ number_format($totalHarga, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

</body>
</html>