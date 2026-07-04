<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Application;
use App\Models\Campaign;
use App\Models\CampaignApprovalReflection;
use App\Models\CampaignDailySlot;
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

        // 承認待ちアラート（MonitorReport が pending のもの）
        $pendingReports = MonitorReport::where('status', 'pending')
            ->with('campaign')
            ->get();
        $pendingReportsCount  = $pendingReports->count();
        $pendingReportsAmount = $pendingReports->sum(fn($r) => $r->campaign?->cooperation_fee ?? 0);

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
        $months = Application::selectRaw('YEAR(created_at) as y, MONTH(created_at) as m')
            ->groupBy('y', 'm')
            ->orderByDesc('y')->orderByDesc('m')
            ->get()
            ->map(fn($r) => ['year' => (int)$r->y, 'month' => (int)$r->m, 'label' => Carbon::createFromDate($r->y, $r->m, 1)->format('Y年n月')])
            ->toArray();

        return view('admin.dashboard', compact(
            'pendingReportsCount', 'pendingReportsAmount',
            'metrics', 'prevMetrics', 'chartData',
            'year', 'month', 'mode', 'months', 'alerts'
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

        // 協力金 = 承認済み報告の初回費/継続費 + 協力金の実績合計
        $reportQuery = MonitorReport::with(['campaign', 'application'])->where('status', 'approved');
        if ($mode === 'monthly') {
            $reportQuery->whereYear('created_at', $year)->whereMonth('created_at', $month);
        }
        $cooperationFee = $reportQuery->get()->sum(function ($r) {
            $c = $r->campaign;
            $fee = $r->purchase_type === 'continuation'
                ? ($c?->recurring_purchase_fee ?? 0) + ($c?->continuation_cooperation_fee ?? 0)
                : ($c?->initial_purchase_fee ?? 0) + ($c?->cooperation_fee ?? 0);
            return $fee + ($r->application?->bonus_amount ?? 0);
        });

        // 売上 = 承認数 × 案件単価
        $sales = $reflections->sum(fn($r) => $r->reflection_count * ($r->campaign?->campaign_unit_price ?? 0));

        // 漏れ経費・全否認コストをループで同時計算
        $leakCost  = 0;
        $allDenied = 0;
        $campaigns = Campaign::all()->keyBy('id');
        foreach ($reflections as $r) {
            $c = $campaigns->get($r->campaign_id);
            if (!$c) continue;
            $completedForCampaign = Application::where('campaign_id', $r->campaign_id)
                ->whereIn('status', ['completed', 'reported', 'approved', 'point_granted'])
                ->when($mode === 'monthly', fn($q) => $q->whereYear('completed_at', $year)->whereMonth('completed_at', $month))
                ->count();

            // 全否認コスト = 実施完了数 × (初回+継続×率 + 協力金)
            if ($r->is_all_denied) {
                $productCost = ($c->initial_purchase_fee ?? 0) + ($c->recurring_purchase_fee ?? 0) * (($c->continuation_rate ?? 0) / 100);
                $allDenied += $completedForCampaign * ($productCost + ($c->cooperation_fee ?? 0));
            }

            // 漏れ経費 = (実施数 - 承認数) × (粗利 + 商品金額 + 協力金 - 紹介単価)
            // 全否認は allDenied で処理済みのため除外
            if (!$r->is_all_denied) {
                $diff = max(0, $completedForCampaign - $r->reflection_count);
                $perUnit = ($c->gross_profit ?? 0)
                    + ($c->initial_purchase_fee ?? 0) + ($c->recurring_purchase_fee ?? 0) * (($c->continuation_rate ?? 0) / 100)
                    + ($c->cooperation_fee ?? 0)
                    - ($c->referral_fee ?? 0);
                $leakCost += $diff * $perUnit;
            }
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
        $alerts    = [];
        $today     = Carbon::today();
        $dismissed = session('dismissed_alerts', []);

        // LINEエラー: 過去24時間以内に failed な通知がある
        $lineErrors = LineNotification::where('status', 'failed')
            ->where('sent_at', '>=', $today->copy()->subDay())
            ->count();
        $lineKey = 'line_error_' . $today->toDateString();
        if ($lineErrors > 0 && !($dismissed[$lineKey] ?? false)) {
            $alerts[] = [
                'level'       => 'error',
                'message'     => "LINEの自動送信でエラーが発生しています（{$lineErrors}件）。",
                'link'        => route('admin.notifications.line'),
                'label'       => 'LINE通知管理',
                'dismiss_key' => $lineKey,
            ];
        }

        // 協力金: 毎月5日以降で前月分にpendingが残っている場合
        if ($today->day >= 5) {
            $prevMonth = $today->copy()->subMonth()->startOfMonth();
            $unpaidCount = MonitorReport::where('status', 'approved')
                ->where('payment_status', 'pending')
                ->whereBetween('created_at', [$prevMonth, $prevMonth->copy()->endOfMonth()])
                ->count();
            $coopKey = 'coop_' . $prevMonth->format('Y_m');
            if ($unpaidCount > 0 && !($dismissed[$coopKey] ?? false)) {
                $alerts[] = [
                    'level'       => 'warning',
                    'message'     => "前月（{$prevMonth->format('Y年n月')}）の協力金 {$unpaidCount}件 が予約待ちのままです（毎月5日までに対応してください）。",
                    'link'        => route('admin.points.index', ['month' => $prevMonth->format('Y-m')]),
                    'label'       => '協力金管理',
                    'dismiss_key' => $coopKey,
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
                ->toBase()
                ->map(fn($arc) => $arc->agent?->parent_id ?? $arc->agent?->id)
                ->filter()
                ->unique();

            $doneAgentIds = ReferralPaymentStatus::where('year', $py)->where('month', $pm)
                ->where('status', 'done')->pluck('agent_id');
            $undoneCount  = $agentIdsWithReports->diff($doneAgentIds)->count();

            $refKey = 'referral_' . $py . '_' . $pm;
            if ($undoneCount > 0 && !($dismissed[$refKey] ?? false)) {
                $alerts[] = [
                    'level'       => 'warning',
                    'message'     => "前月（{$prevMonth->format('Y年n月')}）の紹介報酬 {$undoneCount}代理店 が処理済みになっていません（毎月25日までに対応してください）。",
                    'link'        => route('admin.referrals.index', ['month' => $prevMonth->format('Y-m')]),
                    'label'       => '紹介報酬管理',
                    'dismiss_key' => $refKey,
                ];
            }
        }

        // 打診予約: ダブルブッキング（今後のみ・案件別）
        $duplicateGroups = Application::whereIn('status', ['line_contacted', 'scheduled', 'confirming'])
            ->whereNotNull('invited_at')
            ->where('invited_at', '>=', now())
            ->select('campaign_id', 'invited_at', DB::raw('COUNT(*) as cnt'))
            ->groupBy('campaign_id', 'invited_at')
            ->havingRaw('COUNT(*) > 1')
            ->with('campaign:id,title')
            ->get();
        foreach ($duplicateGroups as $dup) {
            $key = 'dup_' . $dup->campaign_id . '_' . Carbon::parse($dup->invited_at)->timestamp;
            if ($dismissed[$key] ?? false) continue;
            $alerts[] = [
                'level'         => 'error',
                'message'       => Carbon::parse($dup->invited_at)->format('m/d H:i') . " に {$dup->cnt}件入っています",
                'link'          => route('admin.proposal_reservations.index'),
                'label'         => '状況確認',
                'dismiss_key'   => $key,
                'campaign_name' => $dup->campaign?->title,
                'campaign_link' => $dup->campaign_id ? route('admin.campaigns.applications', $dup->campaign_id) : null,
            ];
        }

        // 打診予約: 翌日未達成
        $activeStatuses = ['line_contacted', 'scheduled', 'confirming', 'completed', 'reported', 'approved', 'point_granted'];
        $tomorrowDate   = $today->copy()->addDay()->toDateString();
        $tomorrowSlots  = CampaignDailySlot::where('target_date', $tomorrowDate)
            ->where('planned_count', '>', 0)
            ->get();
        $underCount = 0;
        foreach ($tomorrowSlots as $slot) {
            $booked = Application::where('campaign_id', $slot->campaign_id)
                ->whereIn('status', $activeStatuses)
                ->whereNotNull('invited_at')
                ->whereDate('invited_at', $tomorrowDate)
                ->count();
            if ($booked < $slot->planned_count) {
                $underCount++;
            }
        }
        $underKey = 'under_' . $tomorrowDate;
        if ($underCount > 0 && !($dismissed[$underKey] ?? false)) {
            $alerts[] = [
                'level'       => 'warning',
                'message'     => "翌日（{$today->copy()->addDay()->format('m/d')}）の打診が目標に達していない案件が {$underCount}件 あります。",
                'link'        => route('admin.proposal_reservations.index'),
                'label'       => '打診予約管理',
                'dismiss_key' => $underKey,
            ];
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
