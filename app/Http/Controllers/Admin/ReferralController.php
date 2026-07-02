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

        // 月内の承認済み報告
        $reports = MonitorReport::with(['user:id,name,bimoni_user_id,referred_by_code', 'campaign:id,title,referral_fee,cooperation_fee'])
            ->where('status', 'approved')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get();

        // 全被紹介ユーザー
        $allReferredUsers = User::whereNotNull('referred_by_code')->get()->groupBy('referred_by_code');

        // 全応募
        $allApplications = Application::whereHas('user', fn($q) => $q->whereNotNull('referred_by_code'))
            ->with('user:id,referred_by_code,name')
            ->get();

        // 代理店別集計（親代理店単位）
        $summary = $agents->map(function (Agent $agent) use ($reports, $allReferredUsers, $allApplications, $allCodes, $year, $mon) {
            $codeStrings = $agent->getAllCodeStrings();

            $referredUsers   = collect();
            $referredUserIds = collect();
            foreach ($codeStrings as $code) {
                $users = $allReferredUsers->get($code, collect());
                $referredUsers   = $referredUsers->concat($users);
                $referredUserIds = $referredUserIds->concat($users->pluck('id'));
            }

            $monthReports = $reports->filter(fn($r) => $referredUserIds->contains($r->user_id));

            // 報告数・応募数を単価別
            $reportsByFee = $monthReports->where('status', 'approved')
                ->groupBy(fn($r) => $r->campaign?->referral_fee ?? 0);
            $appsByFee = $allApplications
                ->filter(fn($a) => $referredUserIds->contains($a->user_id))
                ->groupBy(fn($a) => 0); // 単価は案件が必要なので件数のみ

            $totalApps = $allApplications->filter(fn($a) => $referredUserIds->contains($a->user_id))->count();

            $allDenied = $monthReports->groupBy('user_id')
                ->filter(fn($userReports) => $userReports->every(fn($r) => $r->status === 'rejected'))
                ->count();

            $expectedPay = $monthReports->where('status', 'approved')
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
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->orderBy('created_at')
            ->get();

        $rejectedReports = MonitorReport::with(['campaign:id,referral_fee'])
            ->whereIn('user_id', $referredUserIds)
            ->where('status', 'rejected')
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
