<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonitorReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        // 月内の承認済み報告を取得
        $reports = MonitorReport::with(['user:id,name,bimoni_user_id,referral_code,referred_by_code', 'campaign:id,title,referral_fee'])
            ->where('status', 'approved')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get();

        // 紹介コード別に紹介者ユーザーを取得
        $referrers = User::whereNotNull('referral_code')
            ->whereHas('referrals')
            ->get()
            ->keyBy('referral_code');

        if ($request->filled('code')) {
            $searchCode = strtoupper($request->code);
            $referrers = $referrers->filter(fn($u) => str_contains($u->referral_code, $searchCode));
        }

        // 紹介コード別の集計
        $allReferredUsers = User::whereNotNull('referred_by_code')->get()->groupBy('referred_by_code');
        $allApplications  = \App\Models\Application::whereHas('user', fn($q) => $q->whereNotNull('referred_by_code'))->with('user:id,referred_by_code')->get();

        $summary = $referrers->map(function (User $referrer) use ($reports, $allReferredUsers, $allApplications, $month) {
            $code = $referrer->referral_code;

            // この紹介コードで登録したユーザー
            $referredUsers = $allReferredUsers->get($code, collect());
            $referredUserIds = $referredUsers->pluck('id');

            // 当月の承認報告（被紹介者のもの）
            $monthReports = $reports->filter(fn($r) => $referredUserIds->contains($r->user_id));

            // 全否認ユーザー数（当月報告が全て rejected の被紹介者）
            $allDenied = $monthReports->groupBy('user_id')
                ->filter(fn($userReports) => $userReports->every(fn($r) => $r->status === 'rejected'))
                ->count();

            // 支払い対象ユーザー（少なくとも1件承認）
            $eligibleUserIds = $monthReports
                ->where('status', 'approved')
                ->pluck('user_id')
                ->unique();

            $expectedPay = $monthReports
                ->where('status', 'approved')
                ->whereIn('user_id', $eligibleUserIds->all())
                ->sum(fn($r) => $r->campaign?->referral_fee ?? 0);

            return [
                'referrer'       => $referrer,
                'code'           => $code,
                'registered'     => $referredUsers->count(),
                'applications'   => $allApplications->filter(fn($a) => $referredUserIds->contains($a->user_id))->count(),
                'reports'        => $monthReports->where('status', 'approved')->count(),
                'all_denied'     => $allDenied,
                'expected_pay'   => $expectedPay,
            ];
        })->filter(fn($s) => $s['registered'] > 0)->values();

        return view('admin.referrals.index', compact('summary', 'month'));
    }

    public function show(Request $request, string $code): View
    {
        $code = strtoupper($code);
        $referrer = User::where('referral_code', $code)->firstOrFail();

        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $referredUsers = User::where('referred_by_code', $code)->orderBy('created_at')->get();
        $referredUserIds = $referredUsers->pluck('id');

        $reports = MonitorReport::with(['user:id,name,bimoni_user_id', 'campaign:id,title,cooperation_fee,referral_fee'])
            ->whereIn('user_id', $referredUserIds)
            ->where('status', 'approved')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->orderBy('created_at')
            ->get();

        return view('admin.referrals.show', compact('referrer', 'referredUsers', 'reports', 'month', 'code'));
    }
}
