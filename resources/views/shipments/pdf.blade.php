<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Nota {{ $shipment->no_nota }}</title>
<style>
     @page {
        margin-top: -3mm;   /* negatif = geser ke ATAS, positif = ke BAWAH */
        margin-left: -3mm;  /* negatif = geser ke KIRI, positif = ke KANAN */
        margin-right: 0mm;
        margin-bottom: 0mm;
    }
  body { font-family: "Times New Roman", serif; font-size: 12px; }
  table { font-size: 12px; width: 100%; border-collapse: collapse; }
  .line td { border-bottom: 1px solid #000; padding: 1px 0; }
  .box td, .box th { border: 1px solid #000; padding: 1px; }
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
    <td width="40%" valign="top">
      <table>
        <tr>
          <td width="30%" valign="top">
            @php $logoPath = public_path('logo.png'); @endphp
            @if(file_exists($logoPath))
              <img src="{{ $logoPath }}" width="80" alt="Logo">
            @endif
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

    <td width="40%" class="center" valign="middle">
      <strong style="font-size:14px;">NOTA PENGIRIMAN</strong>
    </td>

    <td width="20%" class="right" valign="top">
      <strong>{{ $shipment->no_nota }}</strong><br>
      {{ \Carbon\Carbon::parse($shipment->tanggal)->format('d F Y') }}
    </td>
  </tr>
</table>

<hr style="border:1px solid #000; margin:6px 0;">

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

{{-- ===== TABEL BARANG (kg 1 desimal, m³ 1 desimal) ===== --}}
<table class="box">
  <thead>
    <tr class="center bold">
      <th width="6%">KOLI</th>
      <th width="8%">KG</th>
      <th width="8%">M³</th>
      <th width="8%">TARIF</th>
      <th width="36%">BARANG</th>
      <th width="16%">HARGA</th>
      <th width="18%">SUB TOTAL</th>
    </tr>
  </thead>
  <tbody>
    @foreach($shipment->items as $item)
      @php
        $koli = (float)($item->koli       ?? 0);
        $kg   = (float)($item->berat_kg   ?? 0);
        $m3   = (float)($item->kubikasi_m3 ?? 0);
        $tarif = strtoupper($item->satuan_tarif ?? 'UNIT');
      @endphp
      <tr>
        <td class="center">{{ $koli ? number_format($koli, 0, '.', '') : '-' }}</td>
        <td class="center">{{ $kg ? number_format($kg, 1, '.', '') : '-' }}</td>
        <td class="center">{{ $m3 ? number_format($m3, 1, '.', '') : '-' }}</td>
        <td class="center nowrap">{{ $tarif }}</td>
        <td>{{ strtoupper($item->nama_barang ?? '') }}</td>
        <td class="right">{{ number_format($item->harga_satuan ?? 0, 0, ',', '.') }}</td>
        <td class="right">{{ number_format($item->subtotal ?? 0, 0, ',', '.') }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<br>

<table>
  <tr>
    <td width="70%"></td>
    <td width="30%" class="right bold">
      TOTAL &nbsp;&nbsp; Rp {{ number_format($shipment->harga_total, 0, ',', '.') }}
    </td>
  </tr>
</table>

<hr style="border:1px solid #000; margin:6px 0;">

{{-- ===== SYARAT ===== --}}
<div class="small">
  <ol>
    <li>Barang cairan dan mudah pecah karena pengepakan tidak sempurna menjadi tanggung jawab pengirim.</li>
    <li>Dilarang mengirim barang yang mudah meledak, terbakar dan membahayakan keselamatan umum.</li>
    <li>Ganti rugi maksimal 10x biaya pengiriman.</li>
    <li>Kerusakan akibat bencana alam / kecelakaan di luar tanggung jawab perusahaan.</li>
  </ol>
</div>

<br>

{{-- ===== FOOTER ===== --}}
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

<style>
    * { font-family: 'Courier New', monospace; }
    body { margin: 0; padding: 10px; }

    .page-break { page-break-inside: avoid; }
</style>

</body>
</html>