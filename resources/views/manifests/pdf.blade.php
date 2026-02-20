<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Manifest - {{ $manifest->no_manifest }}</title>
<style>
  * { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
  body { margin: 20px; }
  table { width: 100%; border-collapse: collapse; }
  .no-border td { border: none; padding: 2px 4px; }
  .manifest-title { font-size: 17px; font-weight: bold; text-align: center; text-transform: uppercase; margin-bottom: 3px; }
  .manifest-sub   { font-size: 12px; text-align: center; }
  th { background: #eaeaea; border: 1px solid #000; padding: 5px; font-weight: bold; text-align: center; }
  td { border: 1px solid #000; padding: 4px; }
  .text-center { text-align: center; }
  .text-right  { text-align: right; }
  .summary-row td { font-weight: bold; background: #f3f3f3; }
</style>
</head>
<body>

{{-- ===== HEADER: logo kiri, judul center, no manifest kanan ===== --}}
<table class="no-border" style="margin-bottom:10px;">
  <tr>
    <td width="35%" valign="top">
      <table class="no-border">
        <tr>
          <td width="28%" valign="top">
            <img src="{{ public_path('logo.png') }}" width="65" alt="Logo">
          </td>
          <td valign="top">
            <strong>Sungai Mas Trans</strong><br>
            Jl. Pesapen Selatan No.2/A<br>
            Sungai Mas - Indonesia 45311<br>
            Telp. (031) 3550447<br>
            081330572008 / 082302004004
          </td>
        </tr>
      </table>
    </td>

    <td width="35%" valign="middle">
      <div class="manifest-title">MANIFEST BARANG</div>
      <div class="manifest-sub">No: {{ $manifest->no_manifest }}</div>
    </td>

    <td width="30%" valign="top" style="text-align:right;">
      Manifest Ke-{{ $manifest->manifest_ke }}<br>
      Tgl Muat: {{ \Carbon\Carbon::parse($manifest->tanggal_muat)->format('d/m/Y') }}
    </td>
  </tr>
</table>

<hr style="border: 1px solid #000; margin-bottom: 8px;">

{{-- ===== INFO ===== --}}
<table class="no-border" style="margin-bottom:12px; width:70%;">
  <tr>
    <td style="width:110px;"><b>Tanggal Muat</b></td>
    <td>: {{ \Carbon\Carbon::parse($manifest->tanggal_muat)->format('d-m-Y') }}</td>
    <td style="width:70px;"><b>Sopir</b></td>
    <td>: {{ $manifest->sopir }}</td>
  </tr>
  <tr>
    <td><b>Manifest Ke</b></td>
    <td>: {{ $manifest->manifest_ke }}</td>
    <td><b>Nopol</b></td>
    <td>: {{ $manifest->nopol }}</td>
  </tr>
</table>

{{-- ===== TABEL — pakai $manifest->items (ManifestItem), tanpa kolom Koli & Tipe ===== --}}
@php $no = 1; $totalHarga = 0; @endphp
<table>
  <thead>
    <tr>
      <th style="width:30px;">No</th>
      <th style="width:100px;">Kode/Nota</th>
      <th>Pengirim</th>
      <th>Penerima</th>
      <th style="width:55px;">Kg</th>
      <th style="width:75px;">Tujuan</th>
      <th style="width:110px;">Total</th>
      <th style="width:100px;">Keterangan</th>
    </tr>
  </thead>
  <tbody>
    @foreach($manifest->items as $item)
      @php $totalHarga += (float)($item->harga ?? 0); @endphp
      <tr>
        <td class="text-center">{{ $no++ }}</td>
        <td class="text-center">{{ $item->kode ?? '-' }}</td>
        <td>{{ $item->pengirim ?? '-' }}</td>
        <td>{{ $item->penerima ?? '-' }}</td>
        <td class="text-center">{{ $item->kg ? number_format((float)$item->kg, 1, '.', '') : '-' }}</td>
        <td class="text-center">{{ $item->tujuan ?? '-' }}</td>
        <td class="text-right">Rp {{ number_format((float)($item->harga ?? 0), 0, ',', '.') }}</td>
        <td>{{ $item->keterangan ?? '' }}</td>
      </tr>
    @endforeach
    <tr class="summary-row">
      <td colspan="6" class="text-right">TOTAL</td>
      <td class="text-right">Rp {{ number_format($totalHarga, 0, ',', '.') }}</td>
      <td></td>
    </tr>
  </tbody>
</table>

</body>
</html>