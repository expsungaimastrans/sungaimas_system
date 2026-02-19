<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota {{ $shipment->no_nota }}</title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            font-size: 11px;
        }
        table { width: 100%; border-collapse: collapse; }
        .line td { border-bottom: 1px solid #000; }
        .box td, .box th { border: 1px solid #000; padding: 4px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .small { font-size: 10px; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>

<!-- HEADER -->
<table>
    <tr>
        <td width="40%">
            <table>
                <tr>
                    <td width="30%" valign="top">
                        <img src="{{ public_path('logo.png') }}" width="80">
                    </td>
                    <td width="70%" valign="top">
                        <strong>Sungai Mas Trans</strong><br>
                        Jl. Pesapen Selatan No.2/A<br>
                        Sungai Mas - Indonesia 45311<br>
                        Telp. (031) 3550447<br>
                        081330572008 / 082302004004
                    </td>
                </tr>
            </table>
        </td>

        <td width="40%" class="center">
            <strong>NOTA PENGIRIMAN</strong>
        </td>

        <td width="20%" class="right">
            <strong>{{ $shipment->no_nota }}</strong><br>
            Nomor Nota: {{ str_replace('SMF-', '', $shipment->no_nota) }}<br>
            {{ \Carbon\Carbon::parse($shipment->tanggal)->format('d F Y') }}
        </td>
    </tr>
</table>

<hr>

<!-- DATA PENGIRIM / PENERIMA -->
<table class="line">
    <tr>
        <td width="50%">
            <strong>PENERIMA :</strong> {{ $shipment->nama_penerima }} ({{ $shipment->telp_penerima }})<br>
            <strong>TUJUAN :</strong> {{ $shipment->tujuan }}
        </td>
        <td width="50%">
            <strong>PENGIRIM :</strong> {{ $shipment->nama_pengirim }} ({{ $shipment->telp_pengirim }})<br>
            <strong>DARI :</strong> SURABAYA
        </td>
    </tr>
</table>

<br>

<!-- TABEL BARANG -->
<table class="box">
    <thead>
        <tr class="center bold">
            <th width="6%">KOLI</th>
            <th width="6%">KG</th>
            <th width="7%">M³</th>
            <th width="8%">TARIF</th>
            <th width="38%">BARANG</th>
            <th width="15%">HARGA</th>
            <th width="20%">SUB TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($shipment->items as $item)
            @php
                $tarif = $item->satuan_tarif ?? 'unit';
                $tarifLabel = strtoupper($tarif);

                $koli = $item->koli ?? 0;
                $kg = $item->berat_kg ?? 0;
                $m3 = $item->kubikasi_m3 ?? 0;
            @endphp
            <tr>
                <td class="center">{{ $koli ? number_format($koli,0,',','.') : '-' }}</td>
                <td class="center">{{ $kg ? number_format($kg,2,',','.') : '-' }}</td>
                <td class="center">{{ $m3 ? number_format($m3,3,',','.') : '-' }}</td>
                <td class="center nowrap">{{ $tarifLabel }}</td>
                <td>{{ strtoupper($item->nama_barang) }}</td>
                <td class="right">{{ number_format($item->harga_satuan,0,',','.') }}</td>
                <td class="right">{{ number_format($item->subtotal,0,',','.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<br>

<table>
    <tr>
        <td width="70%"></td>
        <td width="30%" class="right bold">
            TOTAL &nbsp;&nbsp; Rp {{ number_format($shipment->harga_total,0,',','.') }}
        </td>
    </tr>
</table>

<hr>

<!-- SYARAT -->
<div class="small">
    <ol>
        <li>Barang cairan dan mudah pecah karena pengepakan tidak sempurna menjadi tanggung jawab pengirim.</li>
        <li>Dilarang mengirim barang yang mudah meledak, terbakar dan membahayakan keselamatan umum.</li>
        <li>Ganti rugi maksimal 10x biaya pengiriman.</li>
        <li>Kerusakan akibat bencana alam / kecelakaan di luar tanggung jawab perusahaan.</li>
    </ol>
</div>

<br>

<!-- FOOTER -->
<table>
    <tr>
        <td width="50%">
            Transfer Bank:<br>
            BRI : 221601000224568<br>
            BNI : 0050385081<br>
            BCA : 8620008665<br>
            A/n Weenarto Trimaryono
        </td>
        <td width="50%" class="right">
            Hormat Kami,<br><br><br>
            <strong>Sungai Mas Trans</strong>
        </td>
    </tr>
</table>

</body>
</html>
