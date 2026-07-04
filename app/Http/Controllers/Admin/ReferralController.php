<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentReferralCode;
use App\Models\Application;
use App\Models\MonitorReport;
use App\Models\ReferralPaymentStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $year  = (int)($request->input('year',  now()->year));
        $mon   = (int)($request->input('month', now()->month));
        $month = Carbon::createFromDate($year, $mon, 1)->startOfMonth();

        // 全代理店（親のみ）とそのコードを取得
        $agents = Agent::with(['children.codes', 'codes'])->whereNull('parent_id')->get();

        // 全AgentReferralCodeを取得してマッピング
        $allCodes = AgentReferralCode::with('agent.parent')->get()->keyBy('code');

        // 全否認キャンペーンID（承認反映の is_all_denied=true）
        $allDeniedCampaignIds = \App\Models\CampaignApprovalReflection::where('is_all_denied', true)
            ->pluck('campaign_id')->unique();

        // 月内の承認済み報告（初回のみ。継続・回収は紹介報酬なし）
        $reports = MonitorReport::with(['user:id,name,bimoni_user_id,referred_by_code', 'campaign:id,title,referral_fee,cooperation_fee'])
            ->where('status', 'approved')
            ->where('purchase_type', 'initial')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get();

        // 全被紹介ユーザー
        $allReferredUsers = User::whereNotNull('referred_by_code')->get()->groupBy('referred_by_code');

        // 全応募
        $allApplications = Application::whereHas('user', fn($q) => $q->whereNotNull('referred_by_code'))
            ->with('user:id,referred_by_code,name')
            ->get();

        // 代理店別集計（親代理店単位）
        $summary = $agents->map(function (Agent $agent) use ($reports, $allReferredUsers, $allApplications, $allCodes, $year, $mon, $allDeniedCampaignIds) {
            $codeStrings = $agent->getAllCodeStrings();

            $referredUsers   = collect();
            $referredUserIds = collect();
            foreach ($codeStrings as $code) {
                $users = $allReferredUsers->get($code, collect());
                $referredUsers   = $referredUsers->concat($users);
                $referredUserIds = $referredUserIds->concat($users->pluck('id'));
            }

            $monthReports = $reports->filter(fn($r) => $referredUserIds->contains($r->user_id));

            // 報告数は全承認済み（全否認含む）
            $reportsByFee = $monthReports->where('status', 'approved')
                ->groupBy(fn($r) => $r->campaign?->referral_fee ?? 0);
            $appsByFee = $allApplications
                ->filter(fn($a) => $referredUserIds->contains($a->user_id))
                ->groupBy(fn($a) => 0); // 単価は案件が必要なので件数のみ

            $totalApps = $allApplications->filter(fn($a) => $referredUserIds->contains($a->user_id))->count();

            // 全否認 = 承認反映で is_all_denied=true のキャンペーンに紐づく報告数
            $allDenied = $monthReports->where('status', 'approved')
                ->filter(fn($r) => $allDeniedCampaignIds->contains($r->campaign_id))
                ->count();

            // 紹介報酬合計 = 全承認報告の合計 - 全否認分
            $expectedPay = $monthReports->where('status', 'approved')
                ->sum(fn($r) => $r->campaign?->referral_fee ?? 0)
                - $monthReports->where('status', 'approved')
                    ->filter(fn($r) => $allDeniedCampaignIds->contains($r->campaign_id))
                    ->sum(fn($r) => $r->campaign?->referral_fee ?? 0);

            $payStatus = ReferralPaymentStatus::getStatus($agent->id, $year, $mon);

            return [
                'agent'          => $agent,
                'codes'          => $codeStrings,
                'registered'     => $referredUsers->count(),
                'applications'   => $totalApps,
                'reports_by_fee' => $reportsByFee,
                'reports_total'  => $monthReports->where('status', 'approved')->count(),
                'all_denied'     => $allDenied,
                'expected_pay'   => $expectedPay,
                'pay_status'     => $payStatus,
            ];
        })->filter(fn($s) => $s['registered'] > 0 || $s['expected_pay'] > 0)
          ->sortByDesc('expected_pay')
          ->values();

        // 当月合計
        $currentTotal = $summary->sum('expected_pay');

        // 先月合計
        $prevMonth = $month->copy()->subMonth();
        $prevTotal = MonitorReport::with('campaign:id,referral_fee')
            ->where('status', 'approved')
            ->where('purchase_type', 'initial')
            ->whereBetween('created_at', [$prevMonth->copy()->startOfMonth(), $prevMonth->copy()->endOfMonth()])
            ->whereHas('user', fn($q) => $q->whereNotNull('referred_by_code'))
            ->get()
            ->sum(fn($r) => $r->campaign?->referral_fee ?? 0);

        $months = MonitorReport::where('status', 'approved')
            ->whereHas('user', fn($q) => $q->whereNotNull('referred_by_code'))
            ->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m')
            ->groupBy('y', 'm')
            ->orderByDesc('y')->orderByDesc('m')
            ->get()
            ->map(fn($r) => ['year' => (int)$r->y, 'month' => (int)$r->m, 'label' => Carbon::createFromDate($r->y, $r->m, 1)->format('Y年n月')])
            ->toArray();

        // 当月が含まれていない場合は先頭に追加
        $nowY = now()->year;
        $nowM = now()->month;
        if (!collect($months)->contains(fn($m) => $m['year'] === $nowY && $m['month'] === $nowM)) {
            array_unshift($months, ['year' => $nowY, 'month' => $nowM, 'label' => now()->format('Y年n月')]);
        }

        return view('admin.referrals.index', compact('summary', 'month', 'year', 'mon', 'months', 'currentTotal', 'prevTotal'));
    }

    public function markDone(Request $request): RedirectResponse
    {
        $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'month'    => 'required|date_format:Y-m',
        ]);

        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        ReferralPaymentStatus::markDone(
            (int) $request->agent_id,
            (int) $month->format('Y'),
            (int) $month->format('n')
        );

        return back()->with('success', '処理済みにしました。');
    }

    public function markPending(Request $request): RedirectResponse
    {
        $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'month'    => 'required|date_format:Y-m',
        ]);

        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        ReferralPaymentStatus::markPending(
            (int) $request->agent_id,
            (int) $month->format('Y'),
            (int) $month->format('n')
        );

        return back()->with('success', '処理待ちに戻しました。');
    }

    public function exportCsv(Request $request, Agent $agent)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $agent->load(['codes', 'children.codes']);
        $codeStrings = $agent->getAllCodeStrings();

        $referredUserIds = User::whereIn('referred_by_code', $codeStrings)->pluck('id');
        $referredUsers   = User::whereIn('referred_by_code', $codeStrings)->orderBy('created_at')->get();

        $approvedReports = MonitorReport::with(['campaign:id,referral_fee'])
            ->whereIn('user_id', $referredUserIds)
            ->where('status', 'approved')
            ->where('purchase_type', 'initial')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get();

        $rejectedReports = MonitorReport::with(['campaign:id,referral_fee'])
            ->whereIn('user_id', $referredUserIds)
            ->where('status', 'rejected')
            ->where('purchase_type', 'initial')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get();

        $activeUsers = $referredUsers->filter(fn($ru) => $approvedReports->where('user_id', $ru->id)->isNotEmpty());

        $filename = $agent->name . '_' . $month->format('Y年n月') . '_承認ユーザー.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($activeUsers, $approvedReports, $rejectedReports) {
            $out = fopen('php://output', 'w');
            // BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['登録日', '登録コード', 'LINE表示名', '名前', 'フリガナ', '報告数(¥500)', '報告数(¥1000)', '全否認数(¥500)', '全否認数(¥1000)', '紹介報酬合計']);

            foreach ($activeUsers as $ru) {
                $userApproved  = $approvedReports->where('user_id', $ru->id);
                $userRejected  = $rejectedReports->where('user_id', $ru->id);
                fputcsv($out, [
                    $ru->created_at?->format('Y/m/d'),
                    $ru->referred_by_code,
                    $ru->line_display_name ?? '',
                    $ru->name ?? '',
                    $ru->name_kana ?? '',
                    $userApproved->filter(fn($r) => ($r->campaign?->referral_fee ?? 0) == 500)->count(),
                    $userApproved->filter(fn($r) => ($r->campaign?->referral_fee ?? 0) == 1000)->count(),
                    $userRejected->filter(fn($r) => ($r->campaign?->referral_fee ?? 0) == 500)->count(),
                    $userRejected->filter(fn($r) => ($r->campaign?->referral_fee ?? 0) == 1000)->count(),
                    $userApproved->sum(fn($r) => $r->campaign?->referral_fee ?? 0),
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function show(Request $request, Agent $agent): View
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $agent->load(['codes', 'children.codes']);
        $codeStrings = $agent->getAllCodeStrings();

        $referredUsers   = User::whereIn('referred_by_code', $codeStrings)->orderBy('created_at')->get();
        $referredUserIds = $referredUsers->pluck('id');

        $reports = MonitorReport::with(['user:id,name,bimoni_user_id,referred_by_code', 'campaign:id,title,cooperation_fee,referral_fee'])
            ->whereIn('user_id', $referredUserIds)
            ->where('status', 'approved')
            ->where('purchase_type', 'initial')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->orderBy('created_at')
            ->get();

        $rejectedReports = MonitorReport::with(['campaign:id,referral_fee'])
            ->whereIn('user_id', $referredUserIds)
            ->where('status', 'rejected')
            ->where('purchase_type', 'initial')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get();

        $payStatus = ReferralPaymentStatus::getStatus($agent->id, (int) $month->format('Y'), (int) $month->format('n'));

        // 自分のコードを登録者数順にソート
        $userCounts = User::whereIn('referred_by_code', $agent->codes->pluck('code')->toArray())
            ->selectRaw('referred_by_code, count(*) as cnt')
            ->groupBy('referred_by_code')
            ->pluck('cnt', 'referred_by_code');
        $sortedCodes = $agent->codes->sortByDesc(fn($c) => $userCounts[$c->code] ?? 0)->values();

        // 子代理店のコードも登録者数順にソート
        $childrenWithSortedCodes = $agent->children->map(function ($child) {
            $counts = User::whereIn('referred_by_code', $child->codes->pluck('code')->toArray())
                ->selectRaw('referred_by_code, count(*) as cnt')
                ->groupBy('referred_by_code')
                ->pluck('cnt', 'referred_by_code');
            $child->sortedCodes = $child->codes->sortByDesc(fn($c) => $counts[$c->code] ?? 0)->values();
            $child->codeCounts  = $counts;
            return $child;
        });

        return view('admin.referrals.show', compact(
            'agent', 'referredUsers', 'reports', 'rejectedReports', 'month', 'codeStrings', 'payStatus',
            'sortedCodes', 'userCounts', 'childrenWithSortedCodes'
        ));
    }
}
