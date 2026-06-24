<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Campaign;
use App\Models\CampaignApprovalReflection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $year  = (int)($request->input('year',  now()->year));
        $month = (int)($request->input('month', now()->month));
        $mode  = $request->input('mode', 'monthly');

        // 承認待ちアラート（審査中 = reported ステータスのもの）
        $pendingReports = Application::where('status', 'reported')
            ->with('campaign')
            ->get();
        $pendingReportsCount  = $pendingReports->count();
        $pendingReportsAmount = $pendingReports->sum(fn($a) => $a->campaign?->cooperation_fee ?? 0);

        // メイン指標
        $metrics = $this->calcMetrics($year, $month, $mode);

        // 前月比
        [$prevYear, $prevMonth] = $month === 1
            ? [$year - 1, 12]
            : [$year, $month - 1];
        $prevMetrics = $this->calcMetrics($prevYear, $prevMonth, $mode);

        // グラフ用データ（直近12ヶ月）
        $chartData = $this->getChartData();

        // 月一覧
        $months = [];
        $start  = now()->subMonths(11);
        for ($i = 0; $i < 18; $i++) {
            $d        = $start->copy()->addMonths($i);
            $months[] = ['year' => (int)$d->format('Y'), 'month' => (int)$d->format('n'), 'label' => $d->format('Y年n月')];
        }

        return view('admin.dashboard', compact(
            'pendingReportsCount', 'pendingReportsAmount',
            'metrics', 'prevMetrics', 'chartData',
            'year', 'month', 'mode', 'months'
        ));
    }

    private function calcMetrics(int $year, int $month, string $mode): array
    {
        $appQuery = Application::query();
        if ($mode === 'monthly') {
            $appQuery->whereYear('created_at', $year)->whereMonth('created_at', $month);
        }

        $members     = User::when($mode === 'monthly', fn($q) => $q->whereYear('created_at', $year)->whereMonth('created_at', $month))->count();
        $applied     = (clone $appQuery)->count();
        $completed   = (clone $appQuery)->whereIn('status', ['completed', 'reported', 'approved', 'point_granted'])->count();
        $reported    = (clone $appQuery)->whereIn('status', ['approved', 'point_granted'])->count();

        // 承認反映データ
        $reflectionQuery = CampaignApprovalReflection::with('campaign');
        if ($mode === 'monthly') {
            $reflectionQuery->where('period_year', $year)->where('period_month', $month);
        }
        $reflections = $reflectionQuery->get();

        $approvedCount = $reflections->sum('reflection_count');

        // 協力金 = 承認数 × 各案件の協力金
        $cooperationFee = $reflections->sum(fn($r) => $r->reflection_count * ($r->campaign?->cooperation_fee ?? 0));

        // 売上 = 承認数 × 案件単価
        $sales = $reflections->sum(fn($r) => $r->reflection_count * ($r->campaign?->campaign_unit_price ?? 0));

        // 全否認コスト = 全否認フラグのある案件の (初回+継続×率) + 協力金
        $allDenied = $reflections
            ->where('is_all_denied', true)
            ->sum(function ($r) {
                $c = $r->campaign;
                if (!$c) return 0;
                $productCost = ($c->initial_purchase_fee ?? 0) + ($c->recurring_purchase_fee ?? 0) * (($c->continuation_rate ?? 0) / 100);
                return $productCost + ($c->cooperation_fee ?? 0);
            });

        // 漏れ経費 = (実施数 - 承認数) × (粗利 + 商品金額 + 協力金 - 紹介単価) per campaign
        $leakCost = 0;
        $campaigns = Campaign::all()->keyBy('id');
        foreach ($reflections as $r) {
            $c = $campaigns->get($r->campaign_id);
            if (!$c) continue;
            $completedForCampaign = Application::where('campaign_id', $r->campaign_id)
                ->whereIn('status', ['completed', 'reported', 'approved', 'point_granted'])
                ->when($mode === 'monthly', fn($q) => $q->whereYear('completed_at', $year)->whereMonth('completed_at', $month))
                ->count();
            $diff = max(0, $completedForCampaign - $r->reflection_count);
            $perUnit = ($c->gross_profit ?? 0)
                + ($c->initial_purchase_fee ?? 0) + ($c->recurring_purchase_fee ?? 0) * (($c->continuation_rate ?? 0) / 100)
                + ($c->cooperation_fee ?? 0)
                - ($c->referral_fee ?? 0);
            $leakCost += $diff * $perUnit;
        }

        // 粗利 = 承認数 × 案件粗利 - 漏れ経費 - 全否認
        $grossProfit = $reflections->sum(fn($r) => $r->reflection_count * ($r->campaign?->gross_profit ?? 0))
            - $leakCost
            - $allDenied;

        return compact(
            'members', 'applied', 'completed', 'reported',
            'approvedCount', 'cooperationFee', 'sales', 'leakCost', 'allDenied', 'grossProfit'
        );
    }

    private function getChartData(): array
    {
        $labels    = [];
        $sales     = [];
        $fees      = [];
        $grossArr  = [];
        $approvals = [];

        for ($i = 11; $i >= 0; $i--) {
            $d     = now()->subMonths($i);
            $y     = (int)$d->format('Y');
            $m     = (int)$d->format('n');
            $label = $d->format('n月');

            $labels[] = $label;

            $refs = CampaignApprovalReflection::with('campaign')
                ->where('period_year', $y)->where('period_month', $m)
                ->get();

            $monthSales = $refs->sum(fn($r) => $r->reflection_count * ($r->campaign?->campaign_unit_price ?? 0));
            $monthFee   = $refs->sum(fn($r) => $r->reflection_count * ($r->campaign?->cooperation_fee ?? 0));
            $monthGross = $refs->sum(fn($r) => $r->reflection_count * ($r->campaign?->gross_profit ?? 0));
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
