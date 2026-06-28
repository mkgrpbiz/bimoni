<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Application;
use App\Models\Campaign;
use App\Models\CampaignApprovalReflection;
use App\Models\LineNotification;
use App\Models\MonitorReport;
use App\Models\ReferralPaymentStatus;
use App\Models\User;
use Carbon\Carbon;
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

        // ダッシュボードアラート
        $alerts = $this->buildAlerts();

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
            'year', 'month', 'mode', 'months', 'alerts'
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

    private function buildAlerts(): array
    {
        $alerts = [];
        $today  = Carbon::today();

        // LINEエラー: 過去24時間以内に failed な通知がある
        $lineErrors = LineNotification::where('status', 'failed')
            ->where('sent_at', '>=', $today->copy()->subDay())
            ->count();
        if ($lineErrors > 0) {
            $alerts[] = [
                'level'   => 'error',
                'message' => "LINEの自動送信でエラーが発生しています（{$lineErrors}件）。",
                'link'    => route('admin.notifications.line'),
                'label'   => 'LINE通知管理',
            ];
        }

        // 協力金: 毎月5日以降で前月分にpendingが残っている場合
        if ($today->day >= 5) {
            $prevMonth = $today->copy()->subMonth()->startOfMonth();
            $unpaidCount = MonitorReport::where('status', 'approved')
                ->where('payment_status', 'pending')
                ->whereBetween('created_at', [$prevMonth, $prevMonth->copy()->endOfMonth()])
                ->count();
            if ($unpaidCount > 0) {
                $alerts[] = [
                    'level'   => 'warning',
                    'message' => "前月（{$prevMonth->format('Y年n月')}）の協力金 {$unpaidCount}件 が予約済みになっていません（毎月5日までに対応してください）。",
                    'link'    => route('admin.points.index', ['month' => $prevMonth->format('Y-m')]),
                    'label'   => '協力金管理',
                ];
            }
        }

        // 紹介報酬: 毎月25日以降で前月分に処理済みでない代理店がある場合
        if ($today->day >= 25) {
            $prevMonth = $today->copy()->subMonth()->startOfMonth();
            $py = (int) $prevMonth->format('Y');
            $pm = (int) $prevMonth->format('n');

            // 前月に承認済み報告があったユーザーの招待コードを取得
            $reportUserIds = MonitorReport::where('status', 'approved')
                ->whereBetween('created_at', [$prevMonth->copy()->startOfMonth(), $prevMonth->copy()->endOfMonth()])
                ->pluck('user_id');
            $usedCodes = \App\Models\User::whereIn('id', $reportUserIds)
                ->whereNotNull('referred_by_code')
                ->pluck('referred_by_code')
                ->unique();

            // 該当コードを持つ親代理店IDを特定
            $agentIdsWithReports = \App\Models\AgentReferralCode::whereIn('code', $usedCodes)
                ->with('agent')
                ->get()
                ->map(fn($arc) => $arc->agent?->parent_id ?? $arc->agent?->id)
                ->filter()
                ->unique();

            $doneAgentIds = ReferralPaymentStatus::where('year', $py)->where('month', $pm)
                ->where('status', 'done')->pluck('agent_id');
            $undoneCount  = $agentIdsWithReports->diff($doneAgentIds)->count();

            if ($undoneCount > 0) {
                $alerts[] = [
                    'level'   => 'warning',
                    'message' => "前月（{$prevMonth->format('Y年n月')}）の紹介報酬 {$undoneCount}代理店 が処理済みになっていません（毎月25日までに対応してください）。",
                    'link'    => route('admin.referrals.index', ['month' => $prevMonth->format('Y-m')]),
                    'label'   => '紹介報酬管理',
                ];
            }
        }

        return $alerts;
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
