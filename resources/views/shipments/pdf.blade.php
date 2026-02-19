<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Nota {{ $shipment->no_nota }}</title>
<style>
  @page {
      size: 241.3mm 279.4mm; /* 9.5" x 11" */
      margin: 5mm 8mm 5mm 18mm; /* kiri lebih besar: lubang tractor */
  }

  html, body{
      padding:0;
      margin:0;
  }

  body{
      font-family: "Courier New", monospace;
      font-size: 10px;
      line-height: 1.15;
  }

  table{ width:100%; border-collapse:collapse; }
  td, th{ padding:2px 3px; }

  .border, .border td, .border th{
      border:1px solid #000;
  }

  .center{ text-align:center; }
  .right{ text-align:right; }
  .bold{ font-weight:bold; }
  .small{ font-size:9px; }
  .mt-1{ margin-top:2mm; }
  .mt-2{ margin-top:4mm; }
  .mt-3{ margin-top:6mm; }
  .underline{ border-bottom:1px solid #000; display:inline-block; min-width:40mm; }

  /* jangan ada page-break di template supaya tetap 1 halaman */
</style>
</head>
<body>

{{-- ================================== --}}
{{-- HEADER --}}
{{-- ================================== --}}
<table>
  <tr>
    <td width="60%">
      <table>
        <tr>
          <td width="18%" valign="top">
            @if(file_exists(public_path('logo.png')))
              <img src="{{ public_path('logo.png') }}" width="55">
            @endif
          </td>
          <td width="82%" valign="top">
            <span class="bold" style="font-size:11px;">Sungai Mas Trans</span><br>
            Jl. Pesapen Selatan No.2/A<br>
            Sungai Mas - Indonesia 45311<br>
            Telp. (031) 3550447, 081330572008, 082302004004
          </td>
        </tr>
      </table>
    </td>

    <td width="40%" class="right" valign="top">
      <span class="bold" style="font-size:12px;">NOTA PENGIRIMAN</span><br>
      No: <span class="bold">{{ $shipment->no_nota }}</span><br>
      Tgl: {{ optional($shipment->tanggal)->format('d-m-Y') ?? now()->format('d-m-Y') }}
    </td>
  </tr>
</table>

<hr style="margin:2mm 0;">

{{-- ================================== --}}
{{-- DATA PENERIMA / PENGIRIM --}}
{{-- ================================== --}}
<table class="small">
  <tr>
    <td width="16%">PENERIMA</td>
    <td width="2%">:</td>
    <td width="42%">
      <span class="bold">{{ $shipment->nama_penerima }}</span>
      @if($shipment->telp_penerima)
        ({{ $shipment->telp_penerima }})
      @endif
    </td>

    <td width="16%">PENGIRIM</td>
    <td width="2%">:</td>
    <td width="22%">
      <span class="bold">{{ $shipment->nama_pengirim }}</span>
    </td>
  </tr>
  <tr>
    <td>TUJUAN</td><td>:</td>
    <td class="bold">{{ strtoupper($shipment->tujuan ?? '-') }}</td>

    <td>DARI</td><td>:</td>
    <td class="bold">{{ strtoupper($shipment->asal ?? 'SURABAYA') }}</td>
  </tr>
</table>

{{-- ================================== --}}
{{-- TABEL BARANG --}}
{{-- ================================== --}}
<div class="mt-2">
  <table class="border small">
    <thead>
      <tr class="center bold">
        <th style="width:8%;">KOLI</th>
        <th style="width:8%;">KG</th>
        <th style="width:10%;">M³</th>
        <th style="width:10%;">TARIF</th>
        <th>BARANG</th>
        <th style="width:18%;">HARGA</th>
      </tr>
    </thead>
    <tbody>
    @php
      $maxRows = 7; // batasi supaya muat 1 halaman
      $rows = 0;
      $total = 0;
    @endphp
    @foreach($shipment->items as $it)
      @php
        $rows++;
        $sub = (float)($it->subtotal ?? 0);
        $total += $sub;
      @endphp
      @if($rows <= $maxRows)
        <tr>
          <td class="center">{{ (float)($it->koli ?? $it->jumlah ?? 0) }}</td>
          <td class="center">{{ (float)($it->berat_kg ?? 0) }}</td>
          <td class="center">{{ (float)($it->volume_m3 ?? 0) }}</td>
          <td class="right">{{ number_format((float)($it->tarif ?? 0),0,',','.') }}</td>
          <td>{{ strtoupper($it->nama_barang ?? '-') }}</td>
          <td class="right">{{ number_format($sub,0,',','.') }}</td>
        </tr>
      @endif
    @endforeach

    {{-- jika baris sedikit, isi dengan baris kosong --}}
    @for($i = $rows+1; $i <= $maxRows; $i++)
      <tr>
        <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td>
      </tr>
    @endfor

      <tr class="bold">
        <td colspan="5" class="right">TOTAL   Rp</td>
        <td class="right">{{ number_format($shipment->harga_total ?? $total,0,',','.') }}</td>
      </tr>
    </tbody>
  </table>
</div>

{{-- ================================== --}}
{{-- CATATAN (DIPADATKAN) --}}
{{-- ================================== --}}
<div class="mt-2 small">
  <ol style="padding-left:14px; margin:0;">
    <li>Barang cairan dan mudah pecah karena pengemasan tidak sempurna menjadi tanggung jawab pengirim.</li>
    <li>DPW, hilangnya barang atau mudah meleleh, wartel dan lainnya tidak diasuransikan.</li>
    <li>Ganti rugi maksimal 10× biaya pengiriman.</li>
    <li>Kerusakan akibat bencana alam / kecelakaan di luar tanggung jawab perusahaan.</li>
  </ol>
</div>

{{-- ================================== --}}
{{-- FOOTER: REKENING & TTD --}}
{{-- ================================== --}}
<div class="mt-3 small">
  <table>
    <tr>
      <td width="60%" valign="top">
        Transfer Bank:<br>
        BRI : 221601000224568<br>
        BNI : 0050385081<br>
        BCA : 8620008665<br>
        A/n Weenarto Trimaryono
      </td>
      <td width="40%" class="center" valign="top">
        Hormat Kami,<br><br><br><br>
        <span class="bold">Sungai Mas Trans</span>
      </td>
    </tr>
  </table>
</div>

</body>
</html>
