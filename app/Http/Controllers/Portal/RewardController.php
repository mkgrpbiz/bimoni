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
        $childId = $request->get('child_id');
        $codeFilter = $request->get('code_filter'); // コード別フィルター

        // 子フィルター対象エージェント
        $targetAgent = $agent;
        if (!$agent->parent_id && $childId) {
            $targetAgent = $agent->children->firstWhere('id', (int)$childId) ?? $agent;
        }

        $codes = \App\Services\PortalService::codes($targetAgent, $childId === null && !$agent->parent_id);

        // コードフィルター適用
        $filteredCodes = ($codeFilter && in_array($codeFilter, $codes)) ? [$codeFilter] : $codes;

        $month = null;
        if ($mode === 'month') {
            $month = $request->filled('month')
                ? \Carbon\Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
                : \Carbon\Carbon::now()->startOfMonth();
        }

        $reports = \App\Services\PortalService::approvedReports($filteredCodes, $month);

        // 親が「全体」（子で絞り込まず、子がいる）を見ている場合は、
        // レコードごとに実際の紹介元（親自身 or どの子か）を区別して計算する必要がある
        $isCombinedParentView = !$agent->parent_id && $childId === null && $agent->children->isNotEmpty();

        $codeOwnerMap = [];
        if ($isCombinedParentView) {
            foreach ($agent->codes as $c) {
                $codeOwnerMap[$c->code] = $agent;
            }
            foreach ($agent->children as $child) {
                foreach ($child->codes as $c) {
                    $codeOwnerMap[$c->code] = $child;
                }
            }
        }
        $resolveOwner = fn($report) => $codeOwnerMap[$report->user?->referred_by_code] ?? $targetAgent;

        // レコード1件ごとの「支払額（受け取り側の実際の取り分）」と「子への支払額」
        $payoutFor = function ($report) use ($isCombinedParentView, $resolveOwner, $targetAgent) {
            $owner = $isCombinedParentView ? $resolveOwner($report) : $targetAgent;
            return \App\Services\PortalService::calcReward($owner, $report);
        };
        $childPayoutFor = function ($report) use ($isCombinedParentView, $resolveOwner, $payoutFor) {
            $owner = $isCombinedParentView ? $resolveOwner($report) : null;
            // 子経由の紹介のみ集計（親自身の紹介分は含めない）
            return ($owner && $owner->parent_id) ? $payoutFor($report) : 0;
        };

        // 2ヶ月ブロック用データ（月次モード時）
        $thisMonth = \Carbon\Carbon::now()->startOfMonth();
        $lastMonth = $thisMonth->copy()->subMonth();

        $block = [];
        foreach ([$lastMonth, $thisMonth] as $bm) {
            $bReports = \App\Services\PortalService::approvedReports($filteredCodes, $bm);
            $block[] = [
                'month'        => $bm,
                'total'        => $bReports->sum($payoutFor),
                'child_payout' => $isCombinedParentView ? $bReports->sum($childPayoutFor) : null,
                'pay_date'     => $bm->copy()->addMonth()->endOfMonth(),
            ];
        }

        // 案件別集計
        $campaignGroups = $reports->groupBy('campaign_id')->map(function ($rows) use ($targetAgent, $isCombinedParentView, $payoutFor, $childPayoutFor) {
            $fee       = $rows->first()->campaign?->referral_fee ?? 0;
            $allDenied = $rows->groupBy('user_id')
                ->filter(fn($ur) => $ur->every(fn($r) => $r->status === 'rejected'))
                ->count();
            $eligible  = $rows->where('status', 'approved');

            if ($isCombinedParentView) {
                return [
                    'campaign'     => $rows->first()->campaign,
                    'count'        => $eligible->count(),
                    'fee'          => $fee,
                    'reward'       => null, // 混在するため単価は表示しない
                    'total'        => $eligible->sum($payoutFor),
                    'child_payout' => $eligible->sum($childPayoutFor),
                    'all_denied'   => $allDenied,
                    'is_child'     => false,
                    'is_combined'  => true,
                    'diff'         => null,
                ];
            }

            $reward = \App\Services\PortalService::calcReward($targetAgent, $rows->first());

            return [
                'campaign'     => $rows->first()->campaign,
                'count'        => $eligible->count(),
                'fee'          => $fee,
                'reward'       => $reward,
                'total'        => $eligible->count() * $reward,
                'child_payout' => null,
                'all_denied'   => $allDenied,
                'is_child'     => $targetAgent->parent_id !== null,
                'is_combined'  => false,
                'diff'         => $fee - $reward,
            ];
        })->values();

        $grandTotal       = $campaignGroups->sum('total');
        $grandChildPayout = $isCombinedParentView ? $campaignGroups->sum('child_payout') : null;

        // コードプルダウン用リスト（コード → 代理店名）
        $codeOptions = collect();
        foreach ($agent->codes as $c) {
            $codeOptions->put($c->code, $agent->name . '（' . $c->code . '）');
        }
        if (!$agent->parent_id) {
            foreach ($agent->children as $child) {
                foreach ($child->codes as $c) {
                    $codeOptions->put($c->code, $child->name . '（' . $c->code . '）');
                }
            }
        }

        return view('portal.rewards', compact(
            'agent','targetAgent','mode','month','block',
            'campaignGroups','grandTotal','grandChildPayout','isCombinedParentView','childId',
            'codeOptions','codeFilter'
        ));
    }
}
