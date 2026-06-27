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

        // 2ヶ月ブロック用データ（月次モード時）
        $thisMonth = \Carbon\Carbon::now()->startOfMonth();
        $lastMonth = $thisMonth->copy()->subMonth();

        $block = [];
        foreach ([$lastMonth, $thisMonth] as $bm) {
            $bReports = \App\Services\PortalService::approvedReports($filteredCodes, $bm);
            $block[] = [
                'month'    => $bm,
                'total'    => $bReports->sum(fn($r) => \App\Services\PortalService::calcReward($targetAgent, $r)),
                'pay_date' => $bm->copy()->addMonth()->endOfMonth(),
            ];
        }

        // 案件別集計
        $campaignGroups = $reports->groupBy('campaign_id')->map(function ($rows) use ($targetAgent) {
            $fee       = $rows->first()->campaign?->referral_fee ?? 0;
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

        // コードフィルター選択時: ユーザー一覧・報告一覧
        $codeUsers   = null;
        $codeReports = null;
        if ($codeFilter && in_array($codeFilter, $codes)) {
            $codeUsers   = \App\Services\PortalService::users([$codeFilter]);
            $codeReports = \App\Services\PortalService::approvedReports([$codeFilter], $month);
        }

        return view('portal.rewards', compact(
            'agent','targetAgent','mode','month','block',
            'campaignGroups','grandTotal','childId',
            'codeOptions','codeFilter','codeUsers','codeReports'
        ));
    }
}
