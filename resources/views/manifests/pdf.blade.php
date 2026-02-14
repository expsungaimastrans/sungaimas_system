<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
  body{ font-family: "Times New Roman", serif; font-size:11px; }
  table{ width:100%; border-collapse:collapse; }
  .head td{ vertical-align:top; }
  .t td,.t th{ border:1px solid #000; padding:4px; }
  .t th{ background:#000; color:#fff; font-weight:bold; text-align:center; }
  .right{text-align:right;} .center{text-align:center;}
</style>
</head>
<body>

<table class="head">
  <tr>
    <td width="60%">
      <b>Sungai Mas Trans</b><br>
      Jl. Pesapen Selatan No.2/A<br>
      Sungai Mas - Indonesia 45311<br>
      Telp. (031) 3550447<br>
      081330572008 / 082302004004
    </td>
    <td width="40%" class="right">
      <div style="font-size:18px; font-weight:bold;">MANIFEST {{ $manifest->manifest_ke }}</div>
      <table style="width:100%; margin-top:6px;">
        <tr><td class="right"><b>Sopir/Nopol</b></td><td>: {{ $manifest->sopir }} / {{ $manifest->nopol }}</td></tr>
        <tr><td class="right"><b>Tanggal Muat</b></td><td>: {{ \Carbon\Carbon::parse($manifest->tanggal_muat)->format('d/m/Y') }}</td></tr>
        <tr><td class="right"><b>Nama Kapal</b></td><td>: {{ $manifest->nama_kapal }}</td></tr>
        <tr><td class="right"><b>Keberangkatan</b></td><td>: {{ $manifest->keberangkatan ? \Carbon\Carbon::parse($manifest->keberangkatan)->format('d/m/Y H:i:s') : '' }}</td></tr>
      </table>
    </td>
  </tr>
</table>

<br>

<table class="t">
  <thead>
    <tr>
      <th style="width:4%;">No.</th>
      <th style="width:10%;">Kode</th>
      <th style="width:6%;">Koli<br>(Ø)</th>
      <th style="width:18%;">Jenis Barang</th>
      <th style="width:12%;">Pengirim</th>
      <th style="width:6%;">Kg</th>
      <th style="width:14%;">Penerima</th>
      <th style="width:7%;">Tipe</th>
      <th style="width:10%;">Tujuan</th>
      <th style="width:9%;">Harga</th>
      <th style="width:14%;">Keterangan</th>
    </tr>
  </thead>
  <tbody>
    @php
      $totalHarga = 0;
      $totalKoli = 0;
      $totalKg = 0;
    @endphp

    @foreach($manifest->items as $i => $row)
      @php
        $totalHarga += (float)($row->harga ?? 0);
        $totalKoli  += (float)($row->koli ?? 0);
        $totalKg    += (float)($row->kg ?? 0);
      @endphp
      <tr>
        <td class="center">{{ $i+1 }}</td>
        <td class="center">{{ $row->kode }}</td>
        <td class="center">{{ $row->koli }}</td>
        <td>{!! nl2br(e($row->jenis_barang)) !!}</td>
        <td>{{ $row->pengirim }}</td>
        <td class="center">{{ $row->kg }}</td>
        <td>{{ $row->penerima }}</td>
        <td class="center">{{ $row->tipe }}</td>
        <td class="center">{{ $row->tujuan }}</td>
        <td class="right">{{ number_format($row->harga,0,',','.') }}</td>
        <td>{{ $row->keterangan }}</td>
      </tr>
    @endforeach

    <tr>
      <td colspan="2" class="right"><b>TOTAL</b></td>
      <td class="center"><b>{{ number_format($totalKoli,0,',','.') }}</b></td>
      <td colspan="2"></td>
      <td class="center"><b>{{ number_format($totalKg,2,',','.') }}</b></td>
      <td colspan="3"></td>
      <td class="right"><b>{{ number_format($totalHarga,0,',','.') }}</b></td>
      <td></td>
    </tr>
  </tbody>
</table>

<script type="text/php">
if (isset($pdf)) {
  $pdf->page_text(40, 820, "No Manifest: {{ $manifest->no_manifest }}", null, 10, array(0,0,0));
  $pdf->page_text(450, 820, "Halaman {PAGE_NUM} dari {PAGE_COUNT}", null, 10, array(0,0,0));
}
</script>

</body>
</html>
