<?php

namespace App\Services;

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
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for the admin dashboard's aggregate figures.
 * Extracted verbatim from Admin\DashboardController so both the existing
 * admin dashboard page and the AI OFFICE internal API (see
 * routes/web.php "internal-api" group) compute identical numbers from one
 * place — AI OFFICE must never reimplement this SQL on its own side.
 */
class DashboardSummaryService
{
    // 2026-02より前を除外するSQL条件
    private const EXCLUDE_PERIOD_SQL = "(period_year > 2026 OR (period_year = 2026 AND period_month >= 2))";
    private const EXCLUDE_DATE_SQL   = "%s >= '2026-02-01'";

    public function dailyKpi(): array
    {
        $today     = Carbon::today();
        $yesterday = $today->copy()->subDay();

        // LIFFを開いただけで登録未完了のユーザーは会員として数えない（$membersと同じ条件）
        $membersToday     = User::whereNotNull('profile_completed_at')->whereDate('created_at', $today)->count();
        $membersYesterday = User::whereNotNull('profile_completed_at')->whereDate('created_at', $yesterday)->count();

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
            'membersToday', 'membersYesterday',
            'appliedToday', 'appliedYesterday',
            'completedToday', 'completedYesterday',
            'lineContacted', 'scheduled', 'confirming'
        );
    }

    public function pendingApprovals(): array
    {
        $pendingReports = MonitorReport::where('status', 'pending')->with('campaign')->get();

        $pendingReportsCount  = $pendingReports->count();
        $pendingReportsAmount = $pendingReports->sum(function ($r) {
            $coopFee = $r->purchase_type === 'continuation'
                ? ($r->campaign?->continuation_cooperation_fee ?? 0)
                : ($r->campaign?->cooperation_fee ?? 0);

            return ($r->purchase_amount ?? 0) + $coopFee + ($r->bonus_amount ?? 0) + ($r->adjustment_amount ?? 0);
        });

        $pendingCollectionCount = CollectionReport::where('status', 'pending')->count();

        return compact('pendingReportsCount', 'pendingReportsAmount', 'pendingCollectionCount');
    }

    public function monthlyMetrics(int $year, int $month, string $mode = 'monthly'): array
    {
        $exDate = fn (string $col) => sprintf(self::EXCLUDE_DATE_SQL, $col);

        $appQuery = Application::query();
        if ($mode === 'monthly') {
            $appQuery->whereYear('applied_at', $year)->whereMonth('applied_at', $month);
        } else {
            $appQuery->whereRaw($exDate('applied_at'));
        }

        // LIFFを開いただけで登録未完了のユーザーは会員として数えない（ポータル等の会員一覧と同じ条件）
        $members = User::whereNotNull('profile_completed_at')
            ->when($mode === 'monthly', fn ($q) => $q->whereYear('created_at', $year)->whereMonth('created_at', $month))
            ->count();
        $applied = (clone $appQuery)->count();
        // 実施数は案内日時ベース
        $completed = Application::whereIn('status', ['completed', 'reported', 'approved', 'point_granted'])
            ->when($mode === 'monthly', fn ($q) => $q->whereYear('invited_at', $year)->whereMonth('invited_at', $month))
            ->when($mode !== 'monthly', fn ($q) => $q->whereRaw($exDate('invited_at')))
            ->count();
        // 報告数 = 報告管理の承認済み（報告提出日で期間フィルタ）
        $reported = MonitorReport::where('status', 'approved')
            ->when($mode === 'monthly', fn ($q) => $q->whereYear('created_at', $year)->whereMonth('created_at', $month))
            ->when($mode !== 'monthly', fn ($q) => $q->whereRaw($exDate('created_at')))
            ->count();

        // 承認反映データ
        $reflectionQuery = CampaignApprovalReflection::with('campaign');
        if ($mode === 'monthly') {
            $reflectionQuery->where('period_year', $year)->where('period_month', $month);
        } else {
            $reflectionQuery->whereRaw(self::EXCLUDE_PERIOD_SQL);
        }
        $reflections = $reflectionQuery->get();

        $salesLastReflectedAt = $reflections->max('updated_at');

        // 全否認キャンペーンは承認数から除外
        $approvedCount = $reflections->filter(fn ($r) => ! $r->is_all_denied)->sum('reflection_count');

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
        $referralRewardQuery = \App\Models\UserReferralReward::query();
        if ($mode === 'monthly') {
            $referralRewardQuery->whereYear('created_at', $year)->whereMonth('created_at', $month);
        } else {
            $referralRewardQuery->whereRaw($exDate('created_at'));
        }
        $reports = $reportQuery->get();
        $collectionReports = $collectionQuery->get();
        $referralRewards = $referralRewardQuery->get();
        $cooperationFee = $reports->sum(function ($r) {
            $c = $r->campaign;
            $coopFee = $r->purchase_type === 'continuation'
                ? ($c?->continuation_cooperation_fee ?? 0)
                : ($c?->cooperation_fee ?? 0);

            return ($r->purchase_amount ?? 0) + $coopFee + ($r->bonus_amount ?? 0);
        }) + $collectionReports->sum(fn ($r) => $r->totalFee()) + $referralRewards->sum('amount');

        $cooperationFeeLastApprovedAt = $reports->pluck('reviewed_at')
            ->merge($collectionReports->pluck('reviewed_at'))
            ->filter()
            ->max();

        // 売上 = 承認数 × 案件単価
        $sales = $reflections->sum(fn ($r) => $r->reflection_count * ($r->campaign?->campaign_unit_price ?? 0));

        // 実施数を承認反映ページと同じロジックで取得（completed_at ベース）
        $appStats = Application::selectRaw('
                campaign_id,
                SUM(CASE WHEN status IN (\'completed\',\'reported\',\'approved\',\'point_granted\') THEN 1 ELSE 0 END) as completed_count
            ')
            ->when($mode === 'monthly', fn ($q) => $q->whereYear('completed_at', $year)->whereMonth('completed_at', $month))
            ->when($mode !== 'monthly', fn ($q) => $q->whereRaw("completed_at >= '2026-02-01'"))
            ->groupBy('campaign_id')
            ->get()->keyBy('campaign_id');

        // 全否認キャンペーンID（承認反映ページと同じ判定）
        $allDeniedCampaignIds = CampaignApprovalReflection::where('is_all_denied', true)
            ->pluck('campaign_id')->unique();

        // 漏れ経費・全否認コスト（キャンペーン単位で集計してから計算）
        $leakCost = 0;
        $allDenied = 0;
        $campaigns = Campaign::all()->keyBy('id');
        foreach ($reflections->groupBy('campaign_id') as $campaignId => $recs) {
            $c = $campaigns->get($campaignId);
            if (! $c) {
                continue;
            }

            $completedCount = $appStats->get($campaignId)?->completed_count ?? 0;
            $totalReflected = $recs->filter(fn ($r) => ! $r->is_all_denied)->sum('reflection_count');
            $isAllDenied = $allDeniedCampaignIds->contains($campaignId);

            if ($isAllDenied) {
                // 全否認コスト = 実施数 × (初回+継続×率 + 協力金)
                $productCost = ($c->initial_purchase_fee ?? 0) + ($c->recurring_purchase_fee ?? 0) * (($c->continuation_rate ?? 0) / 100);
                $allDenied += $completedCount * ($productCost + ($c->cooperation_fee ?? 0));
            } else {
                // 漏れ経費 = (実施数 - 承認数) × (初回購入費 + 協力金 + 紹介単価)
                $diff = max(0, $completedCount - $totalReflected);
                $perUnit = ($c->initial_purchase_fee ?? 0) + ($c->cooperation_fee ?? 0) + ($c->referral_fee ?? 0);
                $leakCost += $diff * $perUnit;
            }
        }

        // 粗利 = 承認数 × 案件粗利 - 漏れ経費 - 全否認
        $grossProfit = $reflections->sum(fn ($r) => $r->reflection_count * ($r->campaign?->gross_profit ?? 0))
            - $leakCost
            - $allDenied;

        return compact(
            'members', 'applied', 'completed', 'reported',
            'approvedCount', 'cooperationFee', 'sales', 'leakCost', 'allDenied', 'grossProfit',
            'salesLastReflectedAt', 'cooperationFeeLastApprovedAt'
        );
    }

    public function alerts(): array
    {
        $alerts = [];
        $today = Carbon::today();
        $dismissed = session('dismissed_alerts', []);

        // LINEエラー: 過去24時間以内に failed な通知がある
        $lineErrors = LineNotification::where('status', 'failed')
            ->where('sent_at', '>=', $today->copy()->subDay())
            ->count();
        $lineKey = 'line_error_'.$today->toDateString();
        if ($lineErrors > 0 && ! ($dismissed[$lineKey] ?? false)) {
            $alerts[] = [
                'level' => 'error',
                'message' => "LINEの自動送信でエラーが発生しています（{$lineErrors}件）。",
                'link' => route('admin.notifications.line'),
                'label' => 'LINE通知管理',
                'dismiss_key' => $lineKey,
            ];
        }

        // 協力金: 毎月5日以降で前月分にpendingが残っている場合
        if ($today->day >= 5) {
            $prevMonth = $today->copy()->startOfMonth()->subMonth();
            $unpaidCount = MonitorReport::where('status', 'approved')
                ->where('payment_status', 'pending')
                ->whereBetween('created_at', [$prevMonth, $prevMonth->copy()->endOfMonth()])
                ->count();
            $coopKey = 'coop_'.$prevMonth->format('Y_m');
            if ($unpaidCount > 0 && ! ($dismissed[$coopKey] ?? false)) {
                $alerts[] = [
                    'level' => 'warning',
                    'message' => "前月（{$prevMonth->format('Y年n月')}）のポイント還元 {$unpaidCount}件 が予約待ちのままです（毎月5日までに対応してください）。",
                    'link' => route('admin.points.index', ['year' => $prevMonth->year, 'month' => $prevMonth->month]),
                    'label' => 'ポイント還元管理',
                    'dismiss_key' => $coopKey,
                ];
            }
        }

        // 紹介報酬: 毎月25日以降で前月分に処理済みでない代理店がある場合
        if ($today->day >= 25) {
            $prevMonth = $today->copy()->startOfMonth()->subMonth();
            $py = (int) $prevMonth->format('Y');
            $pm = (int) $prevMonth->format('n');

            $reportUserIds = MonitorReport::where('status', 'approved')
                ->whereBetween('created_at', [$prevMonth->copy()->startOfMonth(), $prevMonth->copy()->endOfMonth()])
                ->pluck('user_id');
            $usedCodes = User::whereIn('id', $reportUserIds)
                ->whereNotNull('referred_by_code')
                ->pluck('referred_by_code')
                ->unique();

            $agentIdsWithReports = \App\Models\AgentReferralCode::whereIn('code', $usedCodes)
                ->with('agent')
                ->get()
                ->toBase()
                ->map(fn ($arc) => $arc->agent?->parent_id ?? $arc->agent?->id)
                ->filter()
                ->unique();

            $doneAgentIds = ReferralPaymentStatus::where('year', $py)->where('month', $pm)
                ->where('status', 'done')->pluck('agent_id');
            $undoneCount = $agentIdsWithReports->diff($doneAgentIds)->count();

            $refKey = 'referral_'.$py.'_'.$pm;
            if ($undoneCount > 0 && ! ($dismissed[$refKey] ?? false)) {
                $alerts[] = [
                    'level' => 'warning',
                    'message' => "前月（{$prevMonth->format('Y年n月')}）の紹介報酬 {$undoneCount}代理店 が処理済みになっていません（毎月25日までに対応してください）。",
                    'link' => route('admin.referrals.index', ['year' => $py, 'month' => $pm]),
                    'label' => '紹介報酬管理',
                    'dismiss_key' => $refKey,
                ];
            }
        }

        // 打診予約: ダブルブッキング（今後のみ・案件別、終了案件は除外）
        $duplicateGroups = Application::whereIn('status', ['line_contacted', 'scheduled', 'confirming'])
            ->whereNotNull('invited_at')
            ->where('invited_at', '>=', now())
            ->whereHas('campaign', fn ($q) => $q->where('status', 'published'))
            ->select('campaign_id', 'invited_at', DB::raw('COUNT(*) as cnt'))
            ->groupBy('campaign_id', 'invited_at')
            ->havingRaw('COUNT(*) > 1')
            ->with('campaign:id,title')
            ->get();
        foreach ($duplicateGroups as $dup) {
            $key = 'dup_'.$dup->campaign_id.'_'.Carbon::parse($dup->invited_at)->timestamp;
            if ($dismissed[$key] ?? false) {
                continue;
            }
            $alerts[] = [
                'level' => 'error',
                'message' => Carbon::parse($dup->invited_at)->format('m/d H:i')." に {$dup->cnt}件入っています",
                'link' => route('admin.proposal_reservations.index'),
                'label' => '状況確認',
                'dismiss_key' => $key,
                'campaign_name' => $dup->campaign?->title,
                'campaign_link' => $dup->campaign_id ? route('admin.campaigns.applications', $dup->campaign_id) : null,
            ];
        }

        // 打診予約: 翌日未達成（終了案件の古い目標件数は対象外）
        $activeStatuses = ['line_contacted', 'scheduled', 'confirming', 'completed', 'reported', 'approved', 'point_granted'];
        $tomorrowDate = $today->copy()->addDay()->toDateString();
        $tomorrowSlots = CampaignDailySlot::where('target_date', $tomorrowDate)
            ->where('planned_count', '>', 0)
            ->whereHas('campaign', fn ($q) => $q->where('status', 'published'))
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
        $underKey = 'under_'.$tomorrowDate;
        if ($underCount > 0 && ! ($dismissed[$underKey] ?? false)) {
            $alerts[] = [
                'level' => 'warning',
                'message' => "翌日（{$today->copy()->addDay()->format('m/d')}）の打診が目標に達していない案件が {$underCount}件 あります。",
                'link' => route('admin.applications.index'),
                'label' => '応募管理',
                'dismiss_key' => $underKey,
            ];
        }

        return $alerts;
    }

    /**
     * Not extracted from anywhere — no existing screen currently computes
     * this, so it's a small genuinely-new read-only aggregate (not a
     * duplicate of existing logic) added for AI OFFICE's department
     * dashboard. Campaign.status is an enum of draft/published/closed;
     * there is no separate "stopped" state in BIMONI today.
     */
    public function campaignCounts(): array
    {
        return [
            'published' => Campaign::where('status', 'published')->count(),
            'closed' => Campaign::where('status', 'closed')->count(),
        ];
    }
}
