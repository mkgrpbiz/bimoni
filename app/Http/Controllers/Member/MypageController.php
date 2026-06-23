<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Application;
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

        $groups = [
            '応募中'         => $applications->whereIn('status', ['pending']),
            '打診・日程調整中' => $applications->whereIn('status', ['selected', 'line_contacted', 'scheduled']),
            '実施待ち'        => $applications->whereIn('status', ['completed']),
            '報告待ち'        => $applications->whereIn('status', ['reported']),
            '承認済み'        => $applications->whereIn('status', ['approved', 'point_granted']),
            '否認・キャンセル' => $applications->whereIn('status', ['rejected', 'cancelled']),
        ];

        return view('member.mypage.index', compact('user', 'groups'));
    }
}
