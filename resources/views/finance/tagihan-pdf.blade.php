<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Tagihan {{ $invoice->no_invoice }}</title>
<style>
  body{ font-family:"Times New Roman", serif; font-size:11px; }
  table{ width:100%; border-collapse:collapse; }
  .line td{ border-bottom:1px solid #000; }
  .box td,.box th{ border:1px solid #000; padding:4px; }
  .center{text-align:center;}
  .right{text-align:right;}
  .bold{font-weight:bold;}
  .small{font-size:10px;}
</style>
</head>
<body>

@php
  // Pakai SVG dulu (lebih aman di server tanpa GD)
  $svgPath = public_path('logo.svg');
  $pngPath = public_path('logo.png');

  $logoSvg = null;
  if (file_exists($svgPath)) {
      $logoSvg = file_get_contents($svgPath);
  }

  $hasPng = file_exists($pngPath);
@endphp

<!-- HEADER -->
<table>
  <tr>
    <td width="55%" valign="top">
      <table>
        <tr>
          <td width="28%" valign="top">
            {{-- LOGO --}}
            @if($logoSvg)
              {!! $logoSvg !!}
            @elseif($hasPng)
              {{-- PNG butuh GD di Railway --}}
              <img src="{{ $pngPath }}" width="90">
            @else
              <div class="bold">Sungai Mas Trans</div>
            @endif
          </td>
          <td width="72%" valign="top">
            <div class="bold" style="font-size:13px;">Sungai Mas Trans</div>
            Jl. Pesapen Selatan No.2/A<br>
            Sungai Mas - Indonesia 45311<br>
            Telp. (031) 3550447<br>
            081330572008 / 082302004004
          </td>
        </tr>
      </table>
    </td>

    <td width="45%" valign="top" class="right">
      <div class="bold" style="font-size:16px;">TAGIHAN</div>
      <div class="bold">{{ $invoice->no_invoice }}</div>
      <div class="small">Tanggal: {{ $invoice->tanggal->format('d F Y') }}</div>

      <table style="width:100%; margin-top:6px;">
        <tr>
          <td class="right bold" style="width:40%;">Customer</td>
          <td style="width:60%;">: {{ $invoice->customer ?: '-' }}</td>
        </tr>
        <tr>
          <td class="right bold">Catatan</td>
          <td>: {{ $invoice->catatan ?: '-' }}</td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<hr>

<!-- RINGKASAN -->
<table class="line">
  <tr>
    <td width="60%">
      <span class="bold">Rekap Nota:</span> {{ $invoice->items->count() }} nota
    </td>
    <td width="40%" class="right">
      <span class="bold">TOTAL TAGIHAN:</span>
      <span class="bold">Rp {{ number_format($invoice->total,0,',','.') }}</span>
    </td>
  </tr>
</table>

<br>

<!-- TABEL NOTA -->
<table class="box">
  <thead>
    <tr class="center bold">
      <th width="6%">No</th>
      <th width="18%">No Nota</th>
      <th width="26%">Penerima</th>
      <th width="14%">Tujuan</th>
      <th width="16%">Nilai</th>
      <th width="20%">Status Bayar</th>
    </tr>
  </thead>
  <tbody>
    @foreach($invoice->items as $i => $it)
      <tr>
        <td class="center">{{ $i+1 }}</td>
        <td class="center">{{ $it->no_nota }}</td>
        <td>{{ $it->penerima }}</td>
        <td class="center">{{ $it->tujuan }}</td>
        <td class="right">{{ number_format($it->nilai,0,',','.') }}</td>
        <td class="center">{{ $it->shipment?->status_pembayaran }}</td>
      </tr>
    @endforeach
    <tr>
      <td colspan="4" class="right bold">TOTAL</td>
      <td class="right bold">{{ number_format($invoice->total,0,',','.') }}</td>
      <td></td>
    </tr>
  </tbody>
</table>

<br>

<!-- FOOTER -->
<table>
  <tr>
    <td width="60%">
      <div class="bold">Transfer Bank:</div>
      BRI : 221601000224568<br>
      BNI : 0050385081<br>
      BCA : 8620008665<br>
      A/n Weenarto Trimaryono
    </td>
    <td width="40%" class="right">
      Hormat Kami,<br><br><br>
      <span class="bold">Sungai Mas Trans</span>
    </td>
  </tr>
</table>

</body>
</html>
