<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\UserReferralReward;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReferralProgramController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('liff')->user();

        // 代理店の招待リンクと同じ招待LP（/invite/{code}）を使う
        $referralLink = route('invite', ['code' => $user->bimoni_user_id]);

        $rewards = UserReferralReward::where('referrer_user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'referral_count' => $user->referrals()->count(),
            'first_use_count' => $rewards->count(),
            'this_month_points' => $rewards->filter(fn ($r) => $r->created_at->isSameMonth(now()))->sum('amount'),
            'total_points' => $rewards->sum('amount'),
        ];

        return view('member.referral_program.index', compact('user', 'referralLink', 'rewards', 'stats'));
    }
}
