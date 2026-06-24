<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $agent   = \App\Services\PortalService::agent();
        $mode    = $request->get('mode', 'month');
        $childId = $request->get('child_id'); // 子フィルター（親のみ）

        // 子フィルター対象エージェント
        $targetAgent = $agent;
        if (!$agent->parent_id && $childId) {
            $targetAgent = $agent->children->firstWhere('id', (int)$childId) ?? $agent;
        }

        $codes = \App\Services\PortalService::codes($targetAgent, $childId === null && !$agent->parent_id);

        $month = null;
        if ($mode === 'month') {
            $month = $request->filled('month')
                ? \Carbon\Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
                : \Carbon\Carbon::now()->startOfMonth();
        }

        $reports = \App\Services\PortalService::approvedReports($codes, $month);

        // 2ヶ月ブロック用データ（月次モード時）
        $thisMonth = \Carbon\Carbon::now()->startOfMonth();
        $lastMonth = $thisMonth->copy()->subMonth();

        $block = [];
        foreach ([$lastMonth, $thisMonth] as $bm) {
            $bReports = \App\Services\PortalService::approvedReports($codes, $bm);
            $block[] = [
                'month'    => $bm,
                'total'    => $bReports->sum(fn($r) => \App\Services\PortalService::calcReward($targetAgent, $r)),
                'pay_date' => $bm->copy()->addMonth()->endOfMonth(),
            ];
        }

        // 案件別集計（全否認チェック含む）
        $campaignGroups = $reports->groupBy('campaign_id')->map(function ($rows) use ($targetAgent) {
            $fee       = $rows->first()->campaign?->referral_fee ?? 0;
            $userIds   = $rows->pluck('user_id')->unique();
            // 全否認: ユーザーの全報告が rejected（ここでは対象月内）
            $allDenied = $rows->groupBy('user_id')
                ->filter(fn($ur) => $ur->every(fn($r) => $r->status === 'rejected'))
                ->count();
            $eligible  = $rows->where('status', 'approved');
            $reward    = \App\Services\PortalService::calcReward($targetAgent, $rows->first());

            return [
                'campaign'   => $rows->first()->campaign,
                'count'      => $eligible->count(),
                'fee'        => $fee,
                'reward'     => $reward,
                'total'      => $eligible->count() * $reward,
                'all_denied' => $allDenied,
                'is_child'   => $targetAgent->parent_id !== null,
                'diff'       => $fee - $reward,
            ];
        })->values();

        $grandTotal = $campaignGroups->sum('total');

        return view('portal.rewards', compact(
            'agent','targetAgent','mode','month','block',
            'campaignGroups','grandTotal','childId'
        ));
    }
}
