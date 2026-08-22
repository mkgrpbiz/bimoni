<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\MonitorReport;
use App\Models\UserReferralReward;
use App\Models\UserReferralSetting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserReferralController extends Controller
{
    public function index(Request $request): View
    {
        $year  = (int)($request->input('year',  now()->year));
        $mon   = (int)($request->input('month', now()->month));
        $month = Carbon::createFromDate($year, $mon, 1)->startOfMonth();
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();

        $rewards = UserReferralReward::with(['referrer', 'referredUser', 'monitorReport.campaign'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $summary = $rewards->groupBy('referrer_user_id')->map(function ($rows) {
            $referrer = $rows->first()->referrer;
            $status = $rows->contains('payment_status', 'pending') ? 'pending'
                : ($rows->every(fn ($r) => $r->payment_status === 'paid') ? 'paid' : 'reserved');

            return [
                'referrer'       => $referrer,
                'referral_count' => $referrer ? $referrer->referrals()->count() : 0,
                'reward_count'   => $rows->count(),
                'total'          => $rows->sum('amount'),
                'status'         => $status,
            ];
        })->sortByDesc('total')->values();

        $currentTotal = $summary->sum('total');

        $prevMonth = $month->copy()->subMonth();
        $prevTotal = UserReferralReward::whereBetween('created_at', [$prevMonth->copy()->startOfMonth(), $prevMonth->copy()->endOfMonth()])
            ->sum('amount');

        $months = UserReferralReward::selectRaw('YEAR(created_at) as y, MONTH(created_at) as m')
            ->groupBy('y', 'm')
            ->orderByDesc('y')->orderByDesc('m')
            ->get()
            ->map(fn ($r) => ['year' => (int) $r->y, 'month' => (int) $r->m, 'label' => Carbon::createFromDate($r->y, $r->m, 1)->format('Y年n月')])
            ->toArray();

        $nowY = now()->year;
        $nowM = now()->month;
        if (!collect($months)->contains(fn ($m) => $m['year'] === $nowY && $m['month'] === $nowM)) {
            array_unshift($months, ['year' => $nowY, 'month' => $nowM, 'label' => now()->format('Y年n月')]);
        }

        return view('admin.user_referrals.index', compact('summary', 'month', 'year', 'mon', 'months', 'currentTotal', 'prevTotal'));
    }

    public function settingsEdit(): View
    {
        $setting = UserReferralSetting::current();
        return view('admin.user_referrals.settings', compact('setting'));
    }

    public function settingsUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled'       => 'nullable|in:0,1',
            'reward_amount' => 'required|integer|min:0',
        ]);

        UserReferralSetting::current()->update([
            'enabled'       => ($validated['enabled'] ?? '0') === '1',
            'reward_amount' => $validated['reward_amount'],
        ]);

        return back()->with('success', '設定を更新しました。');
    }

    // ダッシュボードに出す紹介ユーザー数・会員数・単価別初回利用数・削減額
    public function buildStats(): array
    {
        $referralUserCount = \App\Models\User::whereNotNull('referred_by_user_id')
            ->distinct('referred_by_user_id')->count('referred_by_user_id');
        $memberCount = \App\Models\User::whereNotNull('referred_by_user_id')->count();

        $rewards = UserReferralReward::with('monitorReport.campaign')->get();
        $tier500 = $rewards->filter(fn ($r) => ($r->monitorReport?->campaign?->referral_fee ?? 0) == 500)->count();
        $tier1000 = $rewards->filter(fn ($r) => ($r->monitorReport?->campaign?->referral_fee ?? 0) == 1000)->count();

        // 2回目以降の実施数（紹介された各ユーザーの初回report以外の承認済みinitial report）を、
        // その案件自身の紹介単価で評価して削減額を算出。500円単価の初回は1,000P払っているため500円分の赤字として差し引く。
        $referredUserIds = $rewards->pluck('referred_user_id');
        $savedFromRepeats = 0;
        if ($referredUserIds->isNotEmpty()) {
            $firstReportIds = $rewards->pluck('monitor_report_id')->filter();
            $repeatReports = MonitorReport::with('campaign')
                ->whereIn('user_id', $referredUserIds)
                ->where('status', 'approved')
                ->where('purchase_type', 'initial')
                ->whereNotIn('id', $firstReportIds)
                ->get();
            $savedFromRepeats = $repeatReports->sum(fn ($r) => $r->campaign?->referral_fee ?? 0);
        }
        $deficitFromTier500 = $tier500 * 500;
        $savings = $savedFromRepeats - $deficitFromTier500;

        return [
            'referral_user_count' => $referralUserCount,
            'member_count'        => $memberCount,
            'tier_500'            => $tier500,
            'tier_1000'           => $tier1000,
            'savings'             => $savings,
        ];
    }
}
