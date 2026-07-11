<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Application;
use App\Models\Campaign;
use App\Models\CampaignApprovalReflection;
use App\Models\CampaignDailySlot;
use App\Models\CollectionReport;
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
        $pendingReportsAmount = $pendingReports->sum(function ($r) {
            $coopFee = $r->purchase_type === 'continuation'
                ? ($r->campaign?->continuation_cooperation_fee ?? 0)
                : ($r->campaign?->cooperation_fee ?? 0);
            return ($r->purchase_amount ?? 0) + $coopFee + ($r->bonus_amount ?? 0) + ($r->adjustment_amount ?? 0);
        });

        // 承認待ちアラート（CollectionReport が pending のもの）
        $pendingCollectionCount = CollectionReport::where('status', 'pending')->count();

        // ダッシュボードアラート
        $alerts = $this->buildAlerts();

        // 日次KPI（本日・昨日の状況確認）
        $dailyKpi = $this->calcDailyKpi();

        // メイン指標
        $metrics = $this->calcMetrics($year, $month, $mode);

        // 前月比
        [$prevYear, $prevMonth] = $month === 1
            ? [$year - 1, 12]
            : [$year, $month - 1];
        $prevMetrics = $this->calcMetrics($prevYear, $prevMonth, $mode);

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

        return view('admin.dashboard', compact(
            'pendingReportsCount', 'pendingReportsAmount',
            'pendingCollectionCount',
            'metrics', 'prevMetrics', 'chartData',
            'year', 'month', 'mode', 'months', 'alerts', 'dailyKpi'
        ));
    }

    private function calcDailyKpi(): array
    {
        $today     = Carbon::today();
        $yesterday = $today->copy()->subDay();

        $appliedToday     = Application::whereDate('applied_at', $today)->count();
        $appliedYesterday = Application::whereDate('applied_at', $yesterday)->count();

        // 実施完了数は completed_at ベース（その後さらに報告・承認等に進んでいても実施完了扱い）
        $completedStatuses = ['completed', 'reported', 'approved', 'point_granted'];
        $completedToday     = Application::whereIn('status', $completedStatuses)->whereDate('completed_at', $today)->count();
        $completedYesterday = Application::whereIn('status', $completedStatuses)->whereDate('completed_at', $yesterday)->count();

        // 打診中・予約中・実施確認中は現在の件数（日次比較ではなく現状のパイプライン件数）
        $lineContacted = Application::where('status', 'line_contacted')->count();
        $scheduled     = Application::where('status', 'scheduled')->count();
        $confirming    = Application::where('status', 'confirming')->count();

        return compact(
            'appliedToday', 'appliedYesterday',
            'completedToday', 'completedYesterday',
            'lineContacted', 'scheduled', 'confirming'
        );
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

    // 2026-02より前を除外するSQL条件
    private const EXCLUDE_PERIOD_SQL = "(period_year > 2026 OR (period_year = 2026 AND period_month >= 2))";
    private const EXCLUDE_DATE_SQL   = "%s >= '2026-02-01'";

    private function calcMetrics(int $year, int $month, string $mode): array
    {
        $exDate = fn(string $col) => sprintf(self::EXCLUDE_DATE_SQL, $col);

        $appQuery = Application::query();
        if ($mode === 'monthly') {
            $appQuery->whereYear('applied_at', $year)->whereMonth('applied_at', $month);
        } else {
            $appQuery->whereRaw($exDate('applied_at'));
        }

        $members   = User::when($mode === 'monthly', fn($q) => $q->whereYear('created_at', $year)->whereMonth('created_at', $month))->count();
        $applied   = (clone $appQuery)->count();
        // 実施数は案内日時ベース
        $completed = Application::whereIn('status', ['completed', 'reported', 'approved', 'point_granted'])
            ->when($mode === 'monthly', fn($q) => $q->whereYear('invited_at', $year)->whereMonth('invited_at', $month))
            ->when($mode !== 'monthly', fn($q) => $q->whereRaw($exDate('invited_at')))
            ->count();
        // 報告数 = 報告管理の承認済み（報告提出日で期間フィルタ）
        $reported = MonitorReport::where('status', 'approved')
            ->when($mode === 'monthly', fn($q) => $q->whereYear('created_at', $year)->whereMonth('created_at', $month))
            ->when($mode !== 'monthly', fn($q) => $q->whereRaw($exDate('created_at')))
            ->count();

        // 承認反映データ
        $reflectionQuery = CampaignApprovalReflection::with('campaign');
        if ($mode === 'monthly') {
            $reflectionQuery->where('period_year', $year)->where('period_month', $month);
        } else {
            $reflectionQuery->whereRaw(self::EXCLUDE_PERIOD_SQL);
        }
        $reflections = $reflectionQuery->get();

        // 全否認キャンペーンは承認数から除外
        $approvedCount = $reflections->filter(fn($r) => !$r->is_all_denied)->sum('reflection_count');

        // 協力金 = 承認済み報告の初回費/継続費 + 協力金の実績合計（回収報告含む）
        $reportQuery = MonitorReport::with(['campaign', 'application'])->where('status', 'approved');
        if ($mode === 'monthly') {
            $reportQuery->whereYear('created_at', $year)->whereMonth('created_at', $month);
        } else {
            $reportQuery->whereRaw($exDate('created_at'));
        }
        $collectionQuery = CollectionReport::where('status', 'approved');
        if ($mode === 'monthly') {
            $collectionQuery->whereYear('created_at', $year)->whereMonth('created_at', $month);
        } else {
            $collectionQuery->whereRaw($exDate('created_at'));
        }
        $cooperationFee = $reportQuery->get()->sum(function ($r) {
            $c        = $r->campaign;
            $coopFee  = $r->purchase_type === 'continuation'
                ? ($c?->continuation_cooperation_fee ?? 0)
                : ($c?->cooperation_fee ?? 0);
            return ($r->purchase_amount ?? 0) + $coopFee + ($r->bonus_amount ?? 0);
        }) + $collectionQuery->sum('cooperation_fee');

        // 売上 = 承認数 × 案件単価
        $sales = $reflections->sum(fn($r) => $r->reflection_count * ($r->campaign?->campaign_unit_price ?? 0));

        // 実施数を承認反映ページと同じロジックで取得（completed_at ベース）
        $appStats = Application::selectRaw('
                campaign_id,
                SUM(CASE WHEN status IN (\'completed\',\'reported\',\'approved\',\'point_granted\') THEN 1 ELSE 0 END) as completed_count
            ')
            ->when($mode === 'monthly', fn($q) => $q->whereYear('completed_at', $year)->whereMonth('completed_at', $month))
            ->when($mode !== 'monthly', fn($q) => $q->whereRaw("completed_at >= '2026-02-01'"))
            ->groupBy('campaign_id')
            ->get()->keyBy('campaign_id');

        // 全否認キャンペーンID（承認反映ページと同じ判定）
        $allDeniedCampaignIds = CampaignApprovalReflection::where('is_all_denied', true)
            ->pluck('campaign_id')->unique();

        // 漏れ経費・全否認コスト（キャンペーン単位で集計してから計算）
        $leakCost  = 0;
        $allDenied = 0;
        $campaigns = Campaign::all()->keyBy('id');
        foreach ($reflections->groupBy('campaign_id') as $campaignId => $recs) {
            $c = $campaigns->get($campaignId);
            if (!$c) continue;

            $completedCount  = $appStats->get($campaignId)?->completed_count ?? 0;
            $totalReflected  = $recs->filter(fn($r) => !$r->is_all_denied)->sum('reflection_count');
            $isAllDenied     = $allDeniedCampaignIds->contains($campaignId);

            if ($isAllDenied) {
                // 全否認コスト = 実施数 × (初回+継続×率 + 協力金)
                $productCost = ($c->initial_purchase_fee ?? 0) + ($c->recurring_purchase_fee ?? 0) * (($c->continuation_rate ?? 0) / 100);
                $allDenied  += $completedCount * ($productCost + ($c->cooperation_fee ?? 0));
            } else {
                // 漏れ経費 = (実施数 - 承認数) × (初回購入費 + 協力金 + 紹介単価)
                $diff     = max(0, $completedCount - $totalReflected);
                $perUnit  = ($c->initial_purchase_fee ?? 0) + ($c->cooperation_fee ?? 0) + ($c->referral_fee ?? 0);
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
                    'link'        => route('admin.points.index', ['year' => $prevMonth->year, 'month' => $prevMonth->month]),
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
            $d = now()->subMonths($i);
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
