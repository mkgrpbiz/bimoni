<?php

namespace App\Http\Controllers;

use App\Models\AgentReferralCode;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InviteController extends Controller
{
    public function show(string $code): View
    {
        $code = strtoupper($code);

        // コードが存在するか確認（存在しなくても招待ページは表示、ただしエージェント名は出ない）
        $referralCode = AgentReferralCode::with('agent.parent')->where('code', $code)->first();

        $agentName = null;
        if ($referralCode) {
            $agent = $referralCode->agent;
            $agentName = $agent?->parent
                ? $agent->parent->name
                : $agent?->name;
        }

        $liffId        = config('services.line.liff_id');
        $officialId    = config('services.line.official_account_id');

        // LIFF URL（コード付き）
        $liffUrl = $liffId
            ? "https://liff.line.me/{$liffId}?referral_code={$code}"
            : route('member.register');

        // LINE公式アカウント追加URL
        $addFriendUrl = $officialId
            ? "https://line.me/R/ti/p/@{$officialId}"
            : null;

        return view('invite', compact('code', 'agentName', 'liffUrl', 'addFriendUrl', 'liffId'));
    }
}
