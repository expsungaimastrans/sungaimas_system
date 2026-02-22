<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Manifest - {{ $manifest->no_manifest }}</title>
<style>
  * { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
  body { margin: 20px; }
  table { width: 100%; border-collapse: collapse; }

  .company-name { font-size: 13px; font-weight: bold; }
  .manifest-number { font-size: 22px; font-weight: bold; text-align: right; padding-bottom: 6px; }

  .info-grid { width: 100%; border-collapse: collapse; }
  .info-grid td { border: none; padding: 2px 4px; font-size: 11px; }
  .info-grid .lbl { font-weight: bold; width: 110px; }

  .data-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
  .data-table th {
    background: #f0f0f0;
    border: 1px solid #333;
    padding: 6px 4px;
    font-weight: bold;
    text-align: center;
    font-size: 11px;
  }
  .data-table td {
    border-top: 1px dashed #999;
    border-bottom: 1px dashed #999;
    border-left: 1px solid #333;
    border-right: 1px solid #333;
    padding: 4px 5px;
    vertical-align: middle;
  }
  .data-table .summary td {
    border-top: 2px solid #333;
    border-bottom: 1px solid #333;
    font-weight: bold;
    background: #f5f5f5;
    padding: 5px;
  }
  /* Baris jeda antar kota */
  .data-table .spacer td {
    border: none;
    padding: 3px 0;
    background: #fff;
  }

  .text-center { text-align: center; }
  .text-right  { text-align: right; }
</style>
</head>
<body>

{{-- ===== HEADER ===== --}}
<table style="margin-bottom:10px; border-collapse:collapse;">
  <tr>
    <td style="width:40%; vertical-align:middle;">
      <table style="border-collapse:collapse;">
        <tr>
          <td style="border:none; width:60px; vertical-align:middle; padding-right:8px;">
            <img src="{{ public_path('logo.png') }}" width="58" alt="Logo">
          </td>
          <td style="border:none; vertical-align:middle; line-height:1.55;">
            <span class="company-name">Sungai Mas Trans</span><br>
            Jl. Pesapen Selatan No.2/A<br>
            Sungai Mas - Indonesia 45311<br>
            Telp. (031) 3550447<br>
            081330572008 / 082302004004
          </td>
        </tr>
      </table>
    </td>
    <td style="width:60%; vertical-align:top; padding-left:20px;">
      <div class="manifest-number">MANIFEST {{ $manifest->manifest_ke }}</div>
      <table class="info-grid">
        <tr>
          <td class="lbl">Sopir/Nopol</td>
          <td>: {{ $manifest->sopir }} /{{ $manifest->nopol }}</td>
        </tr>
        <tr>
          <td class="lbl">Tanggal Muat</td>
          <td>: {{ \Carbon\Carbon::parse($manifest->tanggal_muat)->format('d/m/Y') }}</td>
        </tr>
        <tr>
          <td class="lbl">Nama Kapal</td>
          <td>: {{ $manifest->nama_kapal ?: '-' }}</td>
        </tr>
        <tr>
          <td class="lbl">Keberangkatan</td>
          <td>: {{ $manifest->keberangkatan ?: '-' }}</td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<hr style="border:0; border-top:1px solid #333; margin:4px 0 0 0;">

@php
  $jalurOrder = [
    'labuan bajo' => 1, 'labuanbajo' => 1,
    'lembor'      => 2,
    'ruteng'      => 3,
    'borong'      => 4,
    'aimere'      => 5, 'aimire' => 5,
    'cancar'      => 6,
    'bajawa'      => 7,
    'soa'         => 8,
    'bowae'       => 9,
    'mbay'        => 10, 'nagekeo' => 10,
    'ende'        => 11,
  ];

  // Fungsi untuk ambil order kota
  $getOrder = function($tujuan) use ($jalurOrder) {
    $t = strtolower(trim($tujuan ?? ''));
    foreach ($jalurOrder as $key => $order) {
      if (str_contains($t, $key)) return $order;
    }
    return 99;
  };

  $sorted = $manifest->items->sortBy(fn($item) => $getOrder($item->tujuan));

  $no          = 1;
  $totalHarga  = 0;
  $totalKoli   = 0;
  $prevTujuan  = null; // track kota sebelumnya
@endphp

<table class="data-table">
  <thead>
    <tr>
      <th style="width:3%;">No.</th>
      <th style="width:60px;">Tgl Nota</th>
      <th style="width:9%;">Kode</th>
      <th style="width:4%;">Koli</th>
      <th style="width:15%;">Jenis Barang</th>
      <th style="width:13%;">Pengirim</th>
      <th style="width:5%;">Kg</th>
      <th style="width:13%;">Penerima</th>
      <th style="width:8%;">Tujuan</th>
      <th style="width:11%;">Harga</th>
      <th style="width:19%;">Keterangan</th>
    </tr>
  </thead>
  <tbody>
    @foreach($sorted as $item)
        @php
          $harga      = (float)($item->harga ?? 0);
  
          // Ambil tanggal nota dari kolom di $item (hasil join ke shipments)
          $tglNotaRaw = $item->tanggal ?? $item->created_at ?? null;
          $tglNota    = $tglNotaRaw ? \Carbon\Carbon::parse($tglNotaRaw)->format('d/m/Y') : '-';
  
          $koli       = (int)($item->koli ?? 0);
          $tujuanNow  = strtolower(trim($item->tujuan ?? ''));
          $totalHarga += $harga;
        @endphp

      {{-- Baris jeda jika kota berubah (bukan baris pertama) --}}
      @if($prevTujuan !== null && $tujuanNow !== $prevTujuan)
        <tr class="spacer">
          <td colspan="10"></td>
        </tr>
      @endif
      @php $prevTujuan = $tujuanNow; @endphp

      {{-- Baris data item --}}

      <tr>
        <td class="text-center">{{ $no++ }}.</td>
        <td class="text-center" style="font-size:10px;">{{ $tglNota }}</td>
        <td class="text-center" style="font-size:10px;">{{ $item->kode ?? '-' }}</td>
        <td class="text-center">{{ $koli ?: '-' }}</td>
        <td style="font-size:10px;">{{ $item->jenis_barang ?? '-' }}</td>
        <td style="font-size:10px;">{{ $item->pengirim ?? '' }}</td>
        <td class="text-center">{{ $item->kg ? number_format((float)$item->kg, 1, '.', '') : 0 }}</td>
        <td style="font-size:10px;">{{ $item->penerima ?? '-' }}</td>
        <td class="text-center" style="font-size:10px;">{{ $item->tujuan ?? '-' }}</td>
        <td class="text-right">{{ number_format($harga, 0, ',', '.') }}</td>
        <td style="font-size:10px;">{{ $item->keterangan ?? '' }}</td>
      </tr>
    @endforeach

    <tr class="summary">
      
      <td colspan="2" class="text-right">Total</td>
      <td class="text-right">{{ number_format($totalHarga, 0, ',', '.') }}</td>
      <td></td>
    </tr>
  </tbody>
</table>

<table style="margin-top:8px; border-collapse:collapse;">
  <tr>
    <td style="border:none; font-weight:bold;">
      No Manifest: {{ $manifest->no_manifest }}
    </td>
  </tr>
</table>

</body>
</html>