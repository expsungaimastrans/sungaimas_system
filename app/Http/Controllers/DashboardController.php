<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $days = (int)($request->query('days', 30));
        if ($days < 7) $days = 7;
        if ($days > 180) $days = 180;

        $now = now();

        // Range
        $todayStart = $now->copy()->startOfDay();
        $todayEnd   = $now->copy()->endOfDay();

        $weekStart  = $now->copy()->startOfWeek(); // default Monday on Laravel config, ok
        $weekEnd    = $now->copy()->endOfWeek();

        $monthStart = $now->copy()->startOfMonth();
        $monthEnd   = $now->copy()->endOfMonth();

        $yearStart  = $now->copy()->startOfYear();
        $yearEnd    = $now->copy()->endOfYear();

        // KPI helper
        $kpi = function($start, $end){
            $q = Shipment::whereBetween('created_at', [$start, $end]);
            return [
                'count' => (clone $q)->count(),
                'sum'   => (float)(clone $q)->sum('harga_total'),
            ];
        };

        $kToday = $kpi($todayStart, $todayEnd);
        $kWeek  = $kpi($weekStart, $weekEnd);
        $kMonth = $kpi($monthStart, $monthEnd);
        $kYear  = $kpi($yearStart, $yearEnd);

        // Status counts
        $countDalamPengiriman = Shipment::where('status_pengiriman', 'DALAM_PENGIRIMAN')->count();
        $countBelumManifest   = Shipment::whereNull('manifest_id')->count();

        // Default chart data (last N days)
        $chart = $this->buildChartData($days);

        return view('dashboard.index', [
            'days' => $days,
            'kToday' => $kToday,
            'kWeek'  => $kWeek,
            'kMonth' => $kMonth,
            'kYear'  => $kYear,
            'countDalamPengiriman' => $countDalamPengiriman,
            'countBelumManifest'   => $countBelumManifest,
            'chart' => $chart,
        ]);
    }

    public function data(Request $request)
    {
        $days = (int)($request->query('days', 30));
        if ($days < 7) $days = 7;
        if ($days > 180) $days = 180;

        return response()->json($this->buildChartData($days));
    }

    private function buildChartData(int $days): array
    {
        $end = now()->endOfDay();
        $start = now()->copy()->subDays($days - 1)->startOfDay();

        // 1) Tren harian: count nota & omzet
        $daily = Shipment::selectRaw("DATE(created_at) as d, COUNT(*) as c, COALESCE(SUM(harga_total),0) as s")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        // build full date series
        $labels = [];
        $mapCount = [];
        $mapSum = [];
        foreach ($daily as $row) {
            $mapCount[$row->d] = (int)$row->c;
            $mapSum[$row->d]   = (float)$row->s;
        }

        $cursor = $start->copy();
        while ($cursor <= $end) {
            $d = $cursor->toDateString();
            $labels[] = $cursor->format('d M');
            $counts[] = $mapCount[$d] ?? 0;
            $sums[]   = $mapSum[$d] ?? 0;
            $cursor->addDay();
        }

        // 2) Doughnut status pembayaran (all time)
        $pay = Shipment::select('status_pembayaran', DB::raw('COUNT(*) as c'))
            ->groupBy('status_pembayaran')
            ->orderBy('status_pembayaran')
            ->get();

        $payLabels = $pay->pluck('status_pembayaran')->map(fn($x)=> $x ?? 'UNKNOWN')->values();
        $payCounts = $pay->pluck('c')->map(fn($x)=> (int)$x)->values();

        // 3) Bar top tujuan (last N days)
        $topTujuan = Shipment::select('tujuan', DB::raw('COUNT(*) as c'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('tujuan')
            ->orderByDesc('c')
            ->limit(8)
            ->get();

        $tujuanLabels = $topTujuan->pluck('tujuan')->values();
        $tujuanCounts = $topTujuan->pluck('c')->map(fn($x)=> (int)$x)->values();

        return [
            'range' => [
                'days' => $days,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'trend' => [
                'labels' => $labels,
                'counts' => $counts ?? [],
                'sums'   => $sums ?? [],
            ],
            'payment' => [
                'labels' => $payLabels,
                'counts' => $payCounts,
            ],
            'tujuan' => [
                'labels' => $tujuanLabels,
                'counts' => $tujuanCounts,
            ],
        ];
    }
}
