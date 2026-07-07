<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\CollectionReport;
use App\Models\MonitorReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MypageController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('liff')->user();

        $applications = Application::where('user_id', $user->id)
            ->with(['campaign', 'report'])
            ->latest('applied_at')
            ->get();

        $now = now();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd   = $now->copy()->subMonth()->endOfMonth();
        $thisMonthStart = $now->copy()->startOfMonth();
        $thisMonthEnd   = $now->copy()->endOfMonth();

        $calcMonitorFee = function (MonitorReport $r): int {
            $coopFee = $r->purchase_type === 'continuation'
                ? ($r->campaign?->continuation_cooperation_fee ?? 0)
                : ($r->campaign?->cooperation_fee ?? 0);
            return ($r->purchase_amount ?? 0) + $coopFee + ($r->bonus_amount ?? 0);
        };

        // 先月報告（created_at）→ 今月10日支払い
        $lastMonthReports = MonitorReport::where('user_id', $user->id)
            ->where('status', 'approved')
            ->with('campaign')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->get();
        $payCurrentMonth = $lastMonthReports->sum($calcMonitorFee);
        $payCurrentMonth += CollectionReport::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('cooperation_fee');

        // 今月報告（created_at）→ 来月10日支払い
        $thisMonthReports = MonitorReport::where('user_id', $user->id)
            ->where('status', 'approved')
            ->with('campaign')
            ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])
            ->get();
        $payNextMonth = $thisMonthReports->sum($calcMonitorFee);
        $payNextMonth += CollectionReport::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])
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
