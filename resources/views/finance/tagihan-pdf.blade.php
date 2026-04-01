<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Tagihan {{ $invoice->invoice_no }}</title>
<style>
  * { font-family: "Times New Roman", serif; font-size: 11px; margin: 0; padding: 0; box-sizing: border-box; }
  body { padding: 20px; }
  table { width: 100%; border-collapse: collapse; }

  .company-name { font-size: 13px; font-weight: bold; }
  .doc-title    { font-size: 15px; font-weight: bold; text-align: center; letter-spacing: 1px; }

  .info-table td { padding: 2px 4px; vertical-align: top; }
  .info-table .lbl { font-weight: bold; white-space: nowrap; width: 110px; }

  /* Tabel data mirip screenshot — bold headers, border minimal */
  .data-table th {
    border: 1px solid #333;
    padding: 5px 4px;
    text-align: center;
    font-weight: bold;
    background: #fff;
    line-height: 1.3;
  }
  .data-table td {
    border: 1px solid #555;
    padding: 5px 5px;
    vertical-align: middle;
  }
  .data-table .total-row td {
    border-top: 2px solid #333;
    border-bottom: 2px solid #333;
    font-weight: bold;
    padding: 5px;
  }

  .text-center { text-align: center; }
  .text-right  { text-align: right; }
  .text-bold   { font-weight: bold; }
</style>
</head>
<body>

{{-- ===== HEADER ===== --}}
<table style="margin-bottom:10px;">
  <tr>
    <td style="width:45%; vertical-align:middle;">
      <table>
        <tr>
          <td style="width:58px; vertical-align:middle; padding-right:8px;">
            @php $logoPath = public_path('logo.png'); @endphp
            @if(file_exists($logoPath))
              <img src="{{ $logoPath }}" width="55" alt="Logo">
            @endif
          </td>
          <td style="vertical-align:middle; line-height:1.6;">
            <span class="company-name">Sungai Mas Trans</span><br>
            Jl. Pesapen Selatan No.2/A, Surabaya<br>
            Sungai Mas Trans<br>
            Telp. (031) 3550447 / 085353744425
          </td>
        </tr>
      </table>
    </td>
    <td style="width:55%; vertical-align:top; text-align:right; line-height:1.8;">
      <div class="doc-title">TAGIHAN / INVOICE</div>
      <div style="margin-top:5px; font-size:11px;">
        <strong>No. Tagihan :</strong> {{ $invoice->invoice_no }}<br>
        <strong>Tanggal &nbsp;&nbsp;&nbsp;&nbsp;:</strong> {{ \Carbon\Carbon::parse($invoice->created_at)->format('d F Y') }}<br>
        <strong>Ditagihkan :</strong> {{ $invoice->billed_to }}
      </div>
    </td>
  </tr>
</table>

<hr style="border:0; border-top:1px solid #333; margin-bottom:10px;">

{{-- ===== TABEL NOTA ===== --}}
<table class="data-table">
  <thead>
    <tr>
      <th style="width:8%;">Tanggal<br>Terima</th>
      <th style="width:9%;">Kode</th>
      <th style="width:5%;">(∅)</th>
      <th style="width:20%;">Jenis<br>Barang</th>
      <th style="width:14%;">Pengirim</th>
      <th style="width:18%;">Penerima</th>
      <th style="width:8%;">Tujuan</th>
      <th style="width:10%;">Harga</th>
      <th style="width:8%;">NO<br>MANIFEST</th>
    </tr>
  </thead>
  <tbody>
    @foreach($shipments as $s)
      @if($s)
      @php
        // Ambil semua jenis barang dari items
        $jenisBarang = $s->items
            ? $s->items->pluck('nama_barang')->filter()->map(fn($b) => strtoupper($b))->implode(', ')
            : '-';

        // Total koli dari items
        $totalKoli = $s->items
            ? $s->items->sum(fn($i) => (int)($i->koli ?? 0))
            : 0;

        // No manifest
        $noManifest = '-';
        if ($s->manifest_id) {
            $manifest = $s->manifest ?? \App\Models\Manifest::find($s->manifest_id);
            $noManifest = $manifest ? ($manifest->manifest_ke ?? $manifest->no_manifest ?? '-') : '-';
        }
      @endphp
      <tr>
        <td class="text-center">
          {{ $s->tanggal ? \Carbon\Carbon::parse($s->tanggal)->format('d/m/y') : '-' }}
        </td>
        <td class="text-center text-bold">{{ $s->no_nota ?? '-' }}</td>
        <td class="text-center">{{ $totalKoli ?: '-' }}</td>
        <td>{{ $jenisBarang ?: '-' }}</td>
        <td>{{ strtoupper($s->nama_pengirim ?? '') }}</td>
        <td class="text-bold">{{ strtoupper($s->nama_penerima ?? '-') }}</td>
        <td class="text-center">{{ strtoupper($s->tujuan ?? '-') }}</td>
        <td class="text-right">{{ number_format((float)($s->harga_total ?? 0), 0, ',', '.') }}</td>
        <td class="text-center text-bold">{{ $noManifest }}</td>
      </tr>
      @endif
    @endforeach

    {{-- TOTAL ROW --}}
    <tr class="total-row">
      <td colspan="6"></td>
      <td class="text-right text-bold">TOTAL</td>
      <td class="text-right text-bold">{{ number_format($grandTotal, 0, ',', '.') }}</td>
      <td></td>
    </tr>
  </tbody>
</table>

<br>

{{-- ===== FOOTER ===== --}}
<table>
  <tr>
    <td style="width:50%; vertical-align:top; line-height:1.7;">
      <strong>Transfer Bank:</strong><br>
      BRI : 221601000224568<br>
      BNI : 0050385081<br>
      BCA : 8620008665<br>
      A/n Weenarto Trimaryono
    </td>
    <td style="width:50%; text-align:right; vertical-align:bottom;">
      Hormat Kami,<br><br><br>
      <strong>Sungai Mas Trans</strong>
    </td>
  </tr>
</table>

</body>
</html>