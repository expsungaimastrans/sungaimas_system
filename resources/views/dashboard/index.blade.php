@extends('layouts.app')
@section('title','Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <div>
    <div class="page-title h4 mb-0">Dashboard</div>
    <div class="text-muted">Pantau nota, omzet, pengiriman & pembayaran</div>
  </div>

  <div class="d-flex gap-2 mt-2 mt-md-0 align-items-center">
    <label class="text-muted small mb-0">Range chart</label>
    <select id="rangeDays" class="form-select form-select-sm" style="width:150px;">
      @foreach([7,14,30,60,90,180] as $d)
        <option value="{{ $d }}" {{ (int)$days===$d ? 'selected' : '' }}>{{ $d }} hari</option>
      @endforeach
    </select>
    <a href="/shipments" class="btn btn-outline-secondary btn-sm">Daftar Nota</a>
    <a href="/manifests" class="btn btn-outline-secondary btn-sm">Daftar Manifest</a>
  </div>
</div>

@php
  $rupiah = fn($n) => 'Rp '.number_format((float)$n,0,',','.');
@endphp

<div class="row g-3">
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Nota Hari Ini</div>
        <div class="d-flex justify-content-between align-items-end">
          <div class="h3 mb-0">{{ $kToday['count'] }}</div>
          <div class="text-muted small">{{ $rupiah($kToday['sum']) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Nota Minggu Ini</div>
        <div class="d-flex justify-content-between align-items-end">
          <div class="h3 mb-0">{{ $kWeek['count'] }}</div>
          <div class="text-muted small">{{ $rupiah($kWeek['sum']) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Nota Bulan Ini</div>
        <div class="d-flex justify-content-between align-items-end">
          <div class="h3 mb-0">{{ $kMonth['count'] }}</div>
          <div class="text-muted small">{{ $rupiah($kMonth['sum']) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Nota Tahun Ini</div>
        <div class="d-flex justify-content-between align-items-end">
          <div class="h3 mb-0">{{ $kYear['count'] }}</div>
          <div class="text-muted small">{{ $rupiah($kYear['sum']) }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Dalam Pengiriman</div>
            <div class="h3 mb-0">{{ $countDalamPengiriman }}</div>
          </div>
          <a href="/shipments" class="btn btn-outline-primary btn-sm">Lihat</a>
        </div>
        <div class="text-muted small mt-2">Otomatis berubah saat nota masuk manifest.</div>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Belum Masuk Manifest</div>
            <div class="h3 mb-0">{{ $countBelumManifest }}</div>
          </div>
          <a href="/manifests/create" class="btn btn-brand btn-sm">Buat Manifest</a>
        </div>
        <div class="text-muted small mt-2">Ini adalah nota yang masih menunggu dimuat.</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <div class="fw-semibold">Tren Nota & Omzet</div>
            <div class="text-muted small">Harian (range bisa diganti)</div>
          </div>
          <div class="text-muted small" id="trendRangeText"></div>
        </div>

        <canvas id="trendChart" height="120"></canvas>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <div class="fw-semibold">Status Pembayaran</div>
        <div class="text-muted small mb-2">Komposisi keseluruhan</div>
        <canvas id="payChart" height="160"></canvas>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="fw-semibold">Top Tujuan</div>
        <!-- di HTML card tujuan, ubah canvas height agar lega -->
<canvas id="tujuanChart" height="220"></canvas>
<div class="text-muted small mt-2" id="tujuanHint"></div>

      </div>
    </div>
  </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
const initialData = @json($chart);

let trendChart, payChart, tujuanChart;

function rupiah(n){
  n = Number(n || 0);
  return 'Rp ' + n.toLocaleString('id-ID');
}

function buildCharts(payload){
  const trend = payload.trend;
  const pay = payload.payment;
  const tujuan = payload.tujuan;

  document.getElementById('trendRangeText').textContent =
    `${payload.range.start} → ${payload.range.end}`;

  // Trend chart (line): counts + sums (2 axis)
  const ctx1 = document.getElementById('trendChart');

  if(trendChart) trendChart.destroy();
  trendChart = new Chart(ctx1, {
    type: 'line',
    data: {
      labels: trend.labels,
      datasets: [
        {
          label: 'Jumlah Nota',
          data: trend.counts,
          yAxisID: 'y',
          tension: 0.25,
        },
        {
          label: 'Omzet',
          data: trend.sums,
          yAxisID: 'y1',
          tension: 0.25,
        }
      ]
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        tooltip: {
          callbacks: {
            label: (ctx) => {
              if(ctx.dataset.label === 'Omzet'){
                return `Omzet: ${rupiah(ctx.parsed.y)}`;
              }
              return `Nota: ${ctx.parsed.y}`;
            }
          }
        },
        legend: { display: true }
      },
      scales: {
        y: {
          beginAtZero: true,
          title: { display: true, text: 'Jumlah Nota' }
        },
        y1: {
          beginAtZero: true,
          position: 'right',
          grid: { drawOnChartArea: false },
          ticks: {
            callback: (v)=> rupiah(v)
          },
          title: { display: true, text: 'Omzet' }
        }
      }
    }
  });

  // Payment doughnut
  const ctx2 = document.getElementById('payChart');
  if(payChart) payChart.destroy();
  payChart = new Chart(ctx2, {
    type: 'doughnut',
    data: {
      labels: pay.labels,
      datasets: [{
        data: pay.counts,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        tooltip: {
          callbacks: {
            label: (ctx)=> `${ctx.label}: ${ctx.parsed} nota`
          }
        },
        legend: { position: 'bottom' }
      }
    }
  });

  // Tujuan bar
    // Tujuan horizontal bar (lebih kebaca)
    const ctx3 = document.getElementById('tujuanChart');
  if(tujuanChart) tujuanChart.destroy();

  // buat label jadi "1. LABUAN BAJO" dll
  const rankedLabels = tujuan.labels.map((x, i) => `${i+1}. ${String(x || '-').toUpperCase()}`);

  // hint ringkas
  const totalTop = tujuan.counts.reduce((a,b)=>a+Number(b||0),0);
  document.getElementById('tujuanHint').textContent =
    `Total ${totalTop} nota dari ${tujuan.labels.length} tujuan teratas pada range ini.`;

  tujuanChart = new Chart(ctx3, {
    type: 'bar',
    data: {
      labels: rankedLabels,
      datasets: [{
        label: 'Jumlah Nota',
        data: tujuan.counts,
        borderWidth: 1,
        borderRadius: 10,
        barThickness: 16,
      }]
    },
    options: {
      responsive: true,
      indexAxis: 'y', // ✅ horizontal
      plugins: {
        tooltip: {
          callbacks: {
            label: (ctx)=> ` ${ctx.parsed.x} nota`
          }
        },
        legend: { display: false }
      },
      scales: {
        x: { beginAtZero: true, ticks: { precision: 0 } },
        y: {
          ticks: {
            autoSkip: false, // biar semua label tampil
          }
        }
      }
    }
  });

}

async function refreshCharts(days){
  const res = await fetch(`/dashboard/data?days=${encodeURIComponent(days)}`);
  const data = await res.json();
  buildCharts(data);
}

document.addEventListener('DOMContentLoaded', () => {
  buildCharts(initialData);

  const sel = document.getElementById('rangeDays');
  sel.addEventListener('change', () => refreshCharts(sel.value));
});
</script>
@endsection
