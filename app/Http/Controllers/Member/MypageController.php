<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\CollectionReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MypageController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('liff')->user();

        $applications = Application::where('user_id', $user->id)
            ->with('campaign')
            ->latest('applied_at')
            ->get();

        $now = now();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd   = $now->copy()->subMonth()->endOfMonth();
        $thisMonthStart = $now->copy()->startOfMonth();
        $thisMonthEnd   = $now->copy()->endOfMonth();

        // 先月承認 → 今月10日支払い
        $payCurrentMonth = $applications
            ->whereIn('status', ['approved'])
            ->filter(fn($a) => $a->approved_at?->between($lastMonthStart, $lastMonthEnd))
            ->sum(fn($a) => ($a->campaign->cooperation_fee ?? 0) + ($a->bonus_amount ?? 0));
        $payCurrentMonth += CollectionReport::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('reviewed_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('cooperation_fee');

        // 今月承認 → 来月10日支払い
        $payNextMonth = $applications
            ->whereIn('status', ['approved'])
            ->filter(fn($a) => $a->approved_at?->between($thisMonthStart, $thisMonthEnd))
            ->sum(fn($a) => ($a->campaign->cooperation_fee ?? 0) + ($a->bonus_amount ?? 0));
        $payNextMonth += CollectionReport::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('reviewed_at', [$thisMonthStart, $thisMonthEnd])
            ->sum('cooperation_fee');

        $payCurrentDate = $now->copy()->day(10)->format('n月j日');
        $payNextDate    = $now->copy()->addMonth()->day(10)->format('n月j日');

        $groups = [
            '応募中'   => $applications->filter(fn($a) => in_array($a->status, ['pending', 'selected', 'line_contacted', 'scheduled', 'confirming'])),
            '実施完了' => $applications->filter(fn($a) => in_array($a->status, ['completed'])),
            '報告済'   => $applications->filter(fn($a) => in_array($a->status, ['reported', 'approved', 'point_granted'])),
            'キャンセル' => $applications->filter(fn($a) => in_array($a->status, ['rejected', 'cancelled'])),
        ];

        $collectionReports = CollectionReport::where('user_id', $user->id)
            ->latest()
            ->get();

        return view('member.mypage.index', compact(
            'user', 'groups', 'collectionReports',
            'payCurrentMonth', 'payNextMonth',
            'payCurrentDate', 'payNextDate'
        ));
    }
}
