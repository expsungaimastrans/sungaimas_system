<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Nota {{ $shipment->no_nota }}</title>
<style>
  body {
    font-family: "Times New Roman", serif;
    font-size: 14px;
    margin: 0;
    padding: 0;
  }
  table { width: 100%; border-collapse: collapse; }
  .line td { border-bottom: 1px solid #000; padding: 3px 0; }
  .box td, .box th { border: 1px solid #000; padding: 3px 4px; }
  .center { text-align: center; }
  .right  { text-align: right; }
  .bold   { font-weight: bold; }
  .small  { font-size: 10px; }
  .nowrap { white-space: nowrap; }
</style>
</head>
<body>

{{-- ===== HEADER ===== --}}
<table>
  <tr>
    <td width="42%" valign="top">
      <table>
        <tr>
          <td width="22%" valign="top">
            @php $logoPath = public_path('logo.png'); @endphp
            @if(file_exists($logoPath))
              <img src="{{ $logoPath }}" width="55" alt="Logo">
            @endif
          </td>
          <td width="78%" valign="top" style="line-height:1.5;">
            <strong>Sungai Mas Trans</strong><br>
            Jl. Pesapen Selatan No.2/A<br>
            Telp. (031) 3550447<br>
            087788406221
          </td>
        </tr>
      </table>
    </td>

    <td width="33%" class="center" valign="middle">
      <strong style="font-size:13px;">NOTA PENGIRIMAN</strong>
    </td>

    <td width="25%" class="right" valign="top" style="line-height:1.6; text-align:right;">
      <span style="font-size:26px; font-weight:900; display:block; margin-bottom:4px;">
          {{ $shipment->no_nota }}
      </span>
  
      <span style="font-size:12px;">
          {{ \Carbon\Carbon::parse($shipment->tanggal)->format('d F Y') }}
      </span>
  </td>
  </tr>
</table>

<hr style="border:1px solid #000; margin:4px 0;">

{{-- ===== PENGIRIM / PENERIMA ===== --}}
<table class="line">
  <tr>
    <td width="50%">
      <strong>PENERIMA :</strong> {{ $shipment->nama_penerima }} ({{ $shipment->telp_penerima }})<br>
      <strong>TUJUAN &nbsp;&nbsp; :</strong> {{ $shipment->tujuan }}
    </td>
    <td width="50%">
      <strong>PENGIRIM :</strong> {{ $shipment->nama_pengirim }} ({{ $shipment->telp_pengirim }})<br>
      <strong>DARI &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; :</strong> SURABAYA
    </td>
  </tr>
</table>

<br>

{{-- ===== TABEL BARANG ===== --}}
<table class="box">
  <thead>
    <tr class="center bold">
      <th width="7%">KOLI</th>
      <th width="8%">KG</th>
      <th width="8%">M³</th>
      <th width="8%">TARIF</th>
      <th width="37%">BARANG</th>
      <th width="16%">HARGA</th>
      <th width="16%">SUB TOTAL</th>
    </tr>
  </thead>
  <tbody>
    @foreach($shipment->items as $item)
      @php
        $koli  = (float)($item->koli        ?? 0);
        $kg    = (float)($item->berat_kg    ?? 0);
        $m3    = (float)($item->kubikasi_m3 ?? 0);
        $tarif = strtoupper($item->satuan_tarif ?? 'UNIT');
      @endphp
      <tr>
        <td class="center">{{ $koli ? number_format($koli, 0, '.', '') : '-' }}</td>
        <td class="center">{{ $kg   ? number_format($kg,   1, '.', '') : '-' }}</td>
        <td class="center">{{ $m3   ? number_format($m3,   1, '.', '') : '-' }}</td>
        <td class="center nowrap">{{ $tarif }}</td>
        <td>{{ strtoupper($item->nama_barang ?? '') }}</td>
        <td class="right">{{ number_format($item->harga_satuan ?? 0, 0, ',', '.') }}</td>
        <td class="right">{{ number_format($item->subtotal     ?? 0, 0, ',', '.') }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<br>

<table>
  <tr>
    <td width="65%"></td>
    <td width="35%" class="right bold">
      TOTAL &nbsp;&nbsp; Rp {{ number_format($shipment->harga_total, 0, ',', '.') }}
    </td>
  </tr>
</table>

<hr style="border:1px solid #000; margin:4px 0;">

{{-- ===== SYARAT ===== --}}
<div class="small">
  <ol style="margin:2px 0; padding-left:16px;">
    <li>Barang cairan dan mudah pecah karena pengepakan tidak sempurna menjadi tanggung jawab pengirim.</li>
    <li>Dilarang mengirim barang yang mudah meledak, terbakar dan membahayakan keselamatan umum.</li>
    <li>Ganti rugi maksimal 10x biaya pengiriman.</li>
    <li>Kerusakan akibat bencana alam / kecelakaan di luar tanggung jawab perusahaan.</li>
  </ol>
</div>

<br>

{{-- ===== FOOTER ===== --}}
<table style="width:100%; margin-top:4px; border-collapse:collapse;">
  <tr>
    {{-- Kolom Transfer Bank, dirapatkan line-height --}}
    <td style="width:50%; vertical-align:top; font-size:10px; line-height:1.2;">
      <strong>Transfer Bank:</strong><br>
      BRI : 221601000224568<br>
      BNI : 0050385081<br>
      BCA : 8620008665<br>
      A/n Weenarto Trimaryono
    </td>

    {{-- Kolom tanda tangan perusahaan --}}
    <td style="width:25%; vertical-align:top; text-align:center; font-size:10px;">
      Hormat Kami,<br>
      <strong>Sungai Mas Trans</strong>
      {{-- kalau mau ada space tanda tangan internal, bisa tambahkan margin-top di sini --}}
    </td>

    {{-- Kolom tanda tangan penerima --}}
    <td style="width:25%; vertical-align:top; text-align:center; font-size:10px;">
      Penerima,<br><br><br>
      <span style="display:inline-block; margin-top:18px; border-top:1px solid #000; padding-top:2px; min-width:130px;">
        &nbsp;
      </span>
    </td>
  </tr>
</table>

</body>
</html>