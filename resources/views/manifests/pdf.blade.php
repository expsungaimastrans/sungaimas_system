<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Manifest - {{ $manifest->no_manifest }}</title>
<style>
  * { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
  body { margin: 20px; }
  table { width: 100%; border-collapse: collapse; }

  /* Header */
  .no-border td { border: none; padding: 2px 4px; }
  .manifest-title {
    font-size: 20px;
    font-weight: bold;
    text-align: center;
    text-transform: uppercase;
    margin-bottom: 2px;
    letter-spacing: 1px;
  }
  .manifest-sub { font-size: 12px; text-align: center; color: #333; }

  /* Info bar */
  .info-bar { width: 100%; margin-bottom: 10px; }
  .info-bar td { border: none; padding: 2px 6px; }
  .info-bar .label { font-weight: bold; width: 95px; }

  /* Tabel data */
  th {
    background: #ddd;
    border: 1px solid #555;
    padding: 5px 4px;
    font-weight: bold;
    text-align: center;
    font-size: 11px;
  }
  td {
    border: 1px solid #555;
    padding: 4px 5px;
    vertical-align: middle;
  }
  .text-center { text-align: center; }
  .text-right  { text-align: right; }
  .summary-row td {
    font-weight: bold;
    background: #efefef;
    border: 1px solid #555;
  }
</style>
</head>
<body>

{{-- ===== HEADER ===== --}}
<table class="no-border" style="margin-bottom:8px;">
  <tr>
    {{-- Kiri: logo + alamat --}}
    <td width="30%" valign="middle">
      <table class="no-border">
        <tr>
          <td style="border:none; width:55px; vertical-align:middle;">
            <img src="{{ public_path('logo.png') }}" width="55" alt="Logo">
          </td>
          <td style="border:none; vertical-align:middle; padding-left:6px; line-height:1.5;">
            <strong style="font-size:12px;">Sungai Mas Trans</strong><br>
            Jl. Pesapen Selatan No.2/A<br>
            Sungai Mas - Indonesia 45311<br>
            Telp. (031) 3550447<br>
            081330572008 / 082302004004
          </td>
        </tr>
      </table>
    </td>

    {{-- Tengah: judul (digeser sedikit ke kanan dengan padding-left) --}}
    <td width="45%" valign="middle" style="text-align:center; padding-left:30px;">
      <div class="manifest-title">MANIFEST BARANG</div>
      <div class="manifest-sub">No: <strong>{{ $manifest->no_manifest }}</strong></div>
    </td>

    {{-- Kanan: info singkat --}}
    <td width="25%" valign="middle" style="text-align:right; padding-right:4px; line-height:1.7;">
      <strong>Manifest Ke-{{ $manifest->manifest_ke }}</strong><br>
      Tgl Muat: {{ \Carbon\Carbon::parse($manifest->tanggal_muat)->format('d/m/Y') }}
    </td>
  </tr>
</table>

<hr style="border:0; border-top:2px solid #333; margin:6px 0 10px 0;">

{{-- ===== INFO BAR 2 kolom ===== --}}
<table class="info-bar" style="margin-bottom:12px;">
  <tr>
    <td class="label">Tanggal Muat</td>
    <td>: {{ \Carbon\Carbon::parse($manifest->tanggal_muat)->format('d-m-Y') }}</td>
    <td class="label">Sopir</td>
    <td>: {{ $manifest->sopir }}</td>
  </tr>
  <tr>
    <td class="label">Manifest Ke</td>
    <td>: {{ $manifest->manifest_ke }}</td>
    <td class="label">Nopol</td>
    <td>: {{ $manifest->nopol }}</td>
  </tr>
</table>

@php
  $jalurOrder = [
    'labuan bajo' => 1,  'labuanbajo'  => 1,
    'lembor'      => 2,
    'ruteng'      => 3,
    'borong'      => 4,
    'aimere'      => 5,
    'cancar'      => 6,
    'bajawa'      => 7,
    'soa'         => 8,
    'bowae'       => 9,
    'mbay'        => 10, 'nagekeo'     => 10,
    'ende'        => 11,
  ];

  $sorted = $manifest->items->sortBy(function($item) use ($jalurOrder) {
    $tujuan = strtolower(trim($item->tujuan ?? ''));
    foreach ($jalurOrder as $key => $order) {
      if (str_contains($tujuan, $key)) return $order;
    }
    return 99;
  });

  $no = 1;
  $totalHarga = 0;
@endphp

{{-- ===== TABEL ===== --}}
<table>
  <thead>
    <tr>
      <th style="width:3%;">No</th>
      <th style="width:10%;">Kode/Nota</th>
      <th style="width:4%;">Koli</th>
      <th style="width:14%;">Jenis Barang</th>
      <th style="width:16%;">Pengirim</th>
      <th style="width:16%;">Penerima</th>
      <th style="width:5%;">Kg</th>
      <th style="width:8%;">Tujuan</th>
      <th style="width:12%;">Total</th>
      <th style="width:12%;">Keterangan</th>
    </tr>
  </thead>
  <tbody>
    @foreach($sorted as $item)
      @php $totalHarga += (float)($item->harga ?? 0); @endphp
      <tr>
        <td class="text-center">{{ $no++ }}</td>
        <td class="text-center" style="font-size:10px;">{{ $item->kode ?? '-' }}</td>
        <td class="text-center">{{ $item->koli ?? '-' }}</td>
        <td style="font-size:10px;">{{ $item->jenis_barang ?? '-' }}</td>
        <td style="font-size:10px;">{{ $item->pengirim ?? '-' }}</td>
        <td style="font-size:10px;">{{ $item->penerima ?? '-' }}</td>
        <td class="text-center">{{ $item->kg ? number_format((float)$item->kg, 1, '.', '') : '-' }}</td>
        <td class="text-center" style="font-size:10px;">{{ $item->tujuan ?? '-' }}</td>
        <td class="text-right">Rp {{ number_format((float)($item->harga ?? 0), 0, ',', '.') }}</td>
        <td style="font-size:10px;">{{ $item->keterangan ?? '' }}</td>
      </tr>
    @endforeach
    <tr class="summary-row">
      <td colspan="8" class="text-right">TOTAL</td>
      <td class="text-right">Rp {{ number_format($totalHarga, 0, ',', '.') }}</td>
      <td></td>
    </tr>
  </tbody>
</table>

<br>

</body>
</html>