<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Application;
use App\Models\CampaignApprovalReflection;
use App\Models\CollectionReport;
use App\Models\MonitorReport;
use App\Services\DashboardSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardSummaryService $summary) {}

    public function index(Request $request)
    {
        $year  = (int)($request->input('year',  now()->year));
        $month = (int)($request->input('month', now()->month));
        $mode  = $request->input('mode', 'monthly');

        // 承認待ちアラート（MonitorReport / CollectionReport が pending のもの）
        ['pendingReportsCount' => $pendingReportsCount, 'pendingReportsAmount' => $pendingReportsAmount, 'pendingCollectionCount' => $pendingCollectionCount]
            = $this->summary->pendingApprovals();

        // ダッシュボードアラート
        $alerts = $this->summary->alerts();

        // 日次KPI（本日・昨日の状況確認）
        $dailyKpi = $this->summary->dailyKpi();

        // メイン指標
        $metrics = $this->summary->monthlyMetrics($year, $month, $mode);

        // 前月比
        [$prevYear, $prevMonth] = $month === 1
            ? [$year - 1, 12]
            : [$year, $month - 1];
        $prevMetrics = $this->summary->monthlyMetrics($prevYear, $prevMonth, $mode);

        // グラフ用データ（直近12ヶ月）
        $chartData = $this->getChartData();

        // 月一覧（旧体制期間を除外）
        $months = Application::selectRaw('YEAR(applied_at) as y, MONTH(applied_at) as m')
            ->whereRaw(sprintf(self::EXCLUDE_DATE_SQL, 'applied_at'))
            ->groupBy('y', 'm')
            ->orderByDesc('y')->orderByDesc('m')
            ->get()
            ->map(fn($r) => ['year' => (int)$r->y, 'month' => (int)$r->m, 'label' => Carbon::createFromDate($r->y, $r->m, 1)->format('Y年n月')])
            ->toArray();

        // 当月がまだ応募0件でも選べるよう、含まれていなければ先頭に追加
        $nowY = now()->year;
        $nowM = now()->month;
        if (!collect($months)->contains(fn($m) => $m['year'] === $nowY && $m['month'] === $nowM)) {
            array_unshift($months, ['year' => $nowY, 'month' => $nowM, 'label' => now()->format('Y年n月')]);
        }

        return view('admin.dashboard', compact(
            'pendingReportsCount', 'pendingReportsAmount',
            'pendingCollectionCount',
            'metrics', 'prevMetrics', 'chartData',
            'year', 'month', 'mode', 'months', 'alerts', 'dailyKpi'
        ));
    }

    public function dismissAlert(Request $request): \Illuminate\Http\RedirectResponse
    {
        $key = $request->input('alert_key', '');
        if ($key !== '') {
            $dismissed = session('dismissed_alerts', []);
            $dismissed[$key] = true;
            session(['dismissed_alerts' => $dismissed]);
        }
        return back();
    }

    // 2026-02より前を除外するSQL条件（$monthsクエリで使用）
    private const EXCLUDE_DATE_SQL = "%s >= '2026-02-01'";

    private function getChartData(): array
    {
        $labels    = [];
        $sales     = [];
        $fees      = [];
        $grossArr  = [];
        $approvals = [];

        for ($i = 11; $i >= 0; $i--) {
            // 月末日からsubMonths()すると対象月にその日が存在せず繰り上がるバグを避けるためstartOfMonth()してから引く
            $d = now()->startOfMonth()->subMonths($i);
            $y = (int)$d->format('Y');
            $m = (int)$d->format('n');

            // 2026-02より前を除外
            if ($y < 2026 || ($y === 2026 && $m < 2)) {
                continue;
            }

            $labels[] = $d->format('n月');

            $refs = CampaignApprovalReflection::with('campaign')
                ->where('period_year', $y)->where('period_month', $m)
                ->get();

            $monthSales = $refs->sum(fn($r) => $r->reflection_count * ($r->campaign?->campaign_unit_price ?? 0));
            $monthGross = $refs->sum(fn($r) => $r->reflection_count * ($r->campaign?->gross_profit ?? 0));

            // 協力金 = 承認済み報告のモニター経費+協力金+ボーナス（メインKPIカードと同じ計算式）
            $monthReports = MonitorReport::with('campaign')->where('status', 'approved')
                ->whereYear('created_at', $y)->whereMonth('created_at', $m)->get();
            $monthFee = $monthReports->sum(function ($r) {
                $c       = $r->campaign;
                $coopFee = $r->purchase_type === 'continuation'
                    ? ($c?->continuation_cooperation_fee ?? 0)
                    : ($c?->cooperation_fee ?? 0);
                return ($r->purchase_amount ?? 0) + $coopFee + ($r->bonus_amount ?? 0);
            }) + CollectionReport::where('status', 'approved')
                ->whereYear('created_at', $y)->whereMonth('created_at', $m)
                ->get()->sum(fn($r) => $r->totalFee());
            $completed  = Application::whereIn('status', ['completed', 'reported', 'approved', 'point_granted'])
                ->whereYear('completed_at', $y)->whereMonth('completed_at', $m)->count();

            $sales[]     = $monthSales;
            $fees[]      = $monthFee;
            $grossArr[]  = $monthGross;
            $approvals[] = $completed > 0 ? round($refs->sum('reflection_count') / $completed * 100, 1) : 0;
        }

        return compact('labels', 'sales', 'fees', 'grossArr', 'approvals');
    }
}
