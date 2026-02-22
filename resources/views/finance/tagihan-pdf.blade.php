<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Tagihan {{ $invoice->invoice_no }}</title>
<style>
  body { font-family:"Times New Roman", serif; font-size: 11px; }
  table { width:100%; border-collapse: collapse; }
  .line td { border-bottom:1px solid #000; }
  .box td, .box th { border:1px solid #000; padding:4px; }
  .center { text-align:center; }
  .right { text-align:right; }
  .bold { font-weight:bold; }
  .small { font-size:10px; }
</style>
</head>
<body>

{{-- HEADER --}}
<table>
  <tr>
    <td width="45%">
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

    <td width="35%" class="center">
      <div style="font-size:16px; font-weight:bold;">TAGIHAN</div>
      <div class="small">Rekap beberapa nota pengiriman</div>
    </td>

    <td width="20%" class="right">
      <strong>{{ $invoice->invoice_no }}</strong><br>
      Tanggal: {{ $invoice->created_at?->format('d F Y') ?? now()->format('d F Y') }}
    </td>
  </tr>
</table>

<hr>

<table class="line">
  <tr>
    <td width="70%">
      <strong>Ditagihkan Kepada:</strong> {{ $invoice->billed_to }}<br>
      <strong>Perihal:</strong> Tagihan Pengiriman (Rekap Nota)<br>
      <strong>Jumlah Nota:</strong> {{ $shipments->count() }} Nota
    </td>
    <td width="30%" class="right">
      <strong>TOTAL TAGIHAN</strong><br>
      <span style="font-size:14px; font-weight:bold;">
        Rp {{ number_format($grandTotal,0,',','.') }}
      </span>
    </td>
  </tr>
</table>

<br>

{{-- TABEL REKAP (dengan kolom tambahan) --}}
<table class="box">
  <thead>
    <tr class="center bold">
      <th width="4%">No</th>
      <th width="10%">No Nota</th>
      <th width="12%">Penerima</th>
      <th width="12%">Pengirim</th>
      <th width="10%">Tujuan</th>
      <th width="10%">No Manifest</th>
      <th width="6%">Koli</th>
      <th width="6%">Kg</th>
      <th width="15%">Detail Barang</th>
      <th width="7%">Status</th>
      <th width="8%">Total</th>
    </tr>
  </thead>
  <tbody>
    @foreach($shipments as $i => $s)
      @php
        // total koli & kg dari item
        $totalKoli = $s->items->sum(function($it){
          return (float)($it->koli ?? $it->jumlah ?? 0);
        });
        $totalKg   = (float)$s->items->sum('berat_kg');

        // daftar nama barang digabung
        $barangList = $s->items->pluck('nama_barang')
                        ->filter()
                        ->implode(', ');

        $manifestNo = optional($s->manifest)->no_manifest ?? $s->manifest_id;
      @endphp
      <tr>
        <td class="center">{{ $i+1 }}</td>
        <td class="center">{{ $s->no_nota }}</td>
        <td>{{ $s->nama_penerima }}</td>
        <td>{{ $s->nama_pengirim }}</td>
        <td class="center">{{ $s->tujuan }}</td>
        <td class="center">{{ $manifestNo }}</td>
        <td class="center">{{ $totalKoli }}</td>
        <td class="center">{{ $totalKg }}</td>
        <td>{{ $barangList }}</td>
        <td class="center">{{ $s->status_pembayaran }}</td>
        <td class="right">Rp {{ number_format($s->harga_total,0,',','.') }}</td>
      </tr>
    @endforeach
    <tr>
      <td colspan="10" class="right bold">GRAND TOTAL</td>
      <td class="right bold">Rp {{ number_format($grandTotal,0,',','.') }}</td>
    </tr>
  </tbody>
</table>

<br>

<hr>

<div class="small">
  <b>Catatan:</b> Mohon lakukan pembayaran sesuai total tagihan di atas. Terima kasih atas kerja samanya.
</div>

<br>

<table>
  <tr>
    <td width="55%">
      Transfer Bank:<br>
      BRI : 221601000224568<br>
      BNI : 0050385081<br>
      BCA : 8620008665<br>
      A/n Weenarto Trimaryono
    </td>
    <td width="45%" class="right">
      Hormat Kami,<br><br><br>
      <strong>Sungai Mas Trans</strong>
    </td>
  </tr>
</table>

</body>
</html>