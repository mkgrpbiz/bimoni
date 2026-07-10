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
        $rejectedReports = \App\Services\PortalService::rejectedReports($filteredCodes, $month);

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

        // 案件別集計（承認0件・全否認のみの案件も一覧に含めるため、否認のみの案件IDも対象に含める）
        $allCampaignIds = $reports->pluck('campaign_id')->merge($rejectedReports->pluck('campaign_id'))->unique();

        $campaignGroups = $allCampaignIds->map(function ($campaignId) use ($reports, $rejectedReports, $targetAgent, $isCombinedParentView, $payoutFor, $childPayoutFor) {
            $rows        = $reports->where('campaign_id', $campaignId); // 承認済みのみ
            $rejectedRows = $rejectedReports->where('campaign_id', $campaignId);
            $campaign    = $rows->first()?->campaign ?? $rejectedRows->first()?->campaign;
            $fee         = $campaign?->referral_fee ?? 0;

            // 全否認: 承認済みが1件もないのに否認だけあるユーザー数
            $approvedUserIds = $rows->pluck('user_id')->unique();
            $allDenied = $rejectedRows->pluck('user_id')->unique()->diff($approvedUserIds)->count();

            if ($isCombinedParentView) {
                return [
                    'campaign'     => $campaign,
                    'count'        => $rows->count(),
                    'fee'          => $fee,
                    'reward'       => null, // 混在するため単価は表示しない
                    'total'        => $rows->sum($payoutFor),
                    'child_payout' => $rows->sum($childPayoutFor),
                    'all_denied'   => $allDenied,
                    'is_child'     => false,
                    'is_combined'  => true,
                    'diff'         => null,
                ];
            }

            $sampleReport = $rows->first() ?? $rejectedRows->first();
            $reward = \App\Services\PortalService::calcReward($targetAgent, $sampleReport);

            return [
                'campaign'     => $campaign,
                'count'        => $rows->count(),
                'fee'          => $fee,
                'reward'       => $reward,
                'total'        => $rows->count() * $reward,
                'child_payout' => null,
                'all_denied'   => $allDenied,
                'is_child'     => $targetAgent->parent_id !== null,
                'is_combined'  => false,
                'diff'         => $fee - $reward,
            ];
        })->values();

        $grandTotal       = $campaignGroups->sum('total');
        $grandChildPayout = $isCombinedParentView ? $campaignGroups->sum('child_payout') : null;

        // 単価別サマリー（¥500案件・¥1000案件などをまとめて把握しやすくする）
        $feeGroups = $campaignGroups->groupBy('fee')->map(function ($rows, $fee) use ($isCombinedParentView) {
            return [
                'fee'          => (int) $fee,
                'reward'       => $isCombinedParentView ? null : $rows->first()['reward'],
                'count'        => $rows->sum('count'),
                'all_denied'   => $rows->sum('all_denied'),
                'total'        => $rows->sum('total'),
                'child_payout' => $isCombinedParentView ? $rows->sum('child_payout') : null,
            ];
        })->sortKeys()->values();

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
            'campaignGroups','feeGroups','grandTotal','grandChildPayout','isCombinedParentView','childId',
            'codeOptions','codeFilter'
        ));
    }
}
