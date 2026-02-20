<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Manifest {{ $manifest->no_manifest }}</title>
<style>
  body { font-family: "Times New Roman", serif; font-size: 10px; margin: 0; padding: 16px; }
  table { width: 100%; border-collapse: collapse; }
  .line td { border-bottom: 1px solid #000; padding: 3px 0; }
  .box td, .box th { border: 1px solid #000; padding: 4px 5px; vertical-align: middle; }
  .center { text-align: center; }
  .right  { text-align: right; }
  .bold   { font-weight: bold; }
  .small  { font-size: 9px; }
  thead tr { background: #f0f0f0; }
</style>
</head>
<body>

{{-- ===== HEADER: logo kiri, judul center, no manifest kanan ===== --}}
<table>
  <tr>
    <td width="30%" valign="middle">
      @php $logoPath = public_path('logo.png'); @endphp
      @if(file_exists($logoPath))
        <img src="{{ $logoPath }}" width="75" alt="Logo" style="display:block; margin-bottom:4px;">
      @endif
      <strong style="font-size:11px;">Sungai Mas Trans</strong><br>
      Jl. Pesapen Selatan No.2/A<br>
      Telp. (031) 3550447<br>
      081330572008 / 082302004004
    </td>

    <td width="40%" class="center" valign="middle">
      <div style="font-size:16px; font-weight:bold; letter-spacing:1px;">MANIFEST PENGIRIMAN</div>
      <div style="font-size:10px; margin-top:3px;">Sungai Mas Trans</div>
    </td>

    <td width="30%" class="right" valign="middle">
      <strong style="font-size:13px;">{{ $manifest->no_manifest }}</strong><br>
      @if($manifest->manifest_ke)
        Ke-{{ $manifest->manifest_ke }}<br>
      @endif
      Tgl Muat: {{ $manifest->tanggal_muat ? \Carbon\Carbon::parse($manifest->tanggal_muat)->format('d/m/Y') : '-' }}
    </td>
  </tr>
</table>

<hr style="border: 1.5px solid #000; margin: 6px 0;">

{{-- ===== INFO MANIFEST ===== --}}
<table class="line" style="margin-bottom:8px;">
  <tr>
    <td width="12%"><strong>Sopir</strong></td>
    <td width="38%">: {{ $manifest->sopir ?: '-' }}</td>
    <td width="15%"><strong>Nama Kapal</strong></td>
    <td width="35%">: {{ $manifest->nama_kapal ?: '-' }}</td>
  </tr>
  <tr>
    <td><strong>No Polisi</strong></td>
    <td>: {{ $manifest->nopol ?: '-' }}</td>
    <td><strong>Keberangkatan</strong></td>
    <td>: {{ $manifest->keberangkatan ?: '-' }}</td>
  </tr>
</table>

{{-- ===== TABEL ITEMS (TANPA kolom koli dan tipe) ===== --}}
<table class="box">
  <thead>
    <tr class="center bold">
      <th width="4%">No</th>
      <th width="13%">Kode / Nota</th>
      <th width="20%">Pengirim</th>
      <th width="20%">Penerima</th>
      <th width="8%">Kg</th>
      <th width="20%">Jenis Barang</th>
      <th width="15%">Tujuan</th>
    </tr>
  </thead>
  <tbody>
    @php $no = 1; $totalKg = 0; @endphp
    @foreach($manifest->items as $item)
      @php $kg = (float)($item->kg ?? 0); $totalKg += $kg; @endphp
      <tr>
        <td class="center">{{ $no++ }}</td>
        <td class="center">{{ $item->kode ?? '-' }}</td>
        <td>{{ $item->pengirim ?? '-' }}</td>
        <td>{{ $item->penerima ?? '-' }}</td>
        <td class="center">{{ $kg ? number_format($kg, 1, '.', '') : '-' }}</td>
        <td style="font-size:9px;">{{ $item->jenis_barang ?? '-' }}</td>
        <td class="center">{{ $item->tujuan ?? '-' }}</td>
      </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr class="bold" style="background:#f0f0f0;">
      <td colspan="4" class="right" style="padding-right:8px;">TOTAL</td>
      <td class="center">{{ number_format($totalKg, 1, '.', '') }}</td>
      <td colspan="2"></td>
    </tr>
  </tfoot>
</table>

<br>

{{-- ===== TANDA TANGAN ===== --}}
<table>
  <tr>
    <td width="33%" class="center">
      Mengetahui,<br><br><br><br>
      <strong>___________________</strong><br>
      <span class="small">Pimpinan</span>
    </td>
    <td width="33%" class="center">
      Dikirim oleh,<br><br><br><br>
      <strong>___________________</strong><br>
      <span class="small">Sopir / Pengantar</span>
    </td>
    <td width="33%" class="center">
      Diterima oleh,<br><br><br><br>
      <strong>___________________</strong><br>
      <span class="small">Penerima</span>
    </td>
  </tr>
</table>

</body>
</html>