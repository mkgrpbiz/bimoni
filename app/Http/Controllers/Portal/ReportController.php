<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $agent      = \App\Services\PortalService::agent();
        $mode       = $request->get('mode', 'month');
        $childId    = $request->get('child_id');
        $codeFilter = $request->get('code_filter');

        // 子フィルター（親のみ）
        $targetAgent = $agent;
        if (!$agent->parent_id && $childId) {
            $targetAgent = $agent->children->firstWhere('id', (int)$childId) ?? $agent;
        }

        $allCodes = \App\Services\PortalService::codes($targetAgent, $childId === null && !$agent->parent_id);

        // コードフィルター
        $codes = ($codeFilter && in_array($codeFilter, $allCodes)) ? [$codeFilter] : $allCodes;

        $month = null;
        if ($mode === 'month') {
            $month = $request->filled('month')
                ? \Carbon\Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
                : \Carbon\Carbon::now()->startOfMonth();
        }

        $reports = \App\Services\PortalService::approvedReports($codes, $month);

        // 親が「全体」（子で絞り込まず、子がいる）を見ている場合は、
        // レコードごとに実際の紹介元（親自身 or どの子か）を区別して単価を計算する
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

        // 全否認（管理者が承認反映ページでフラグを立てた案件）は報酬0円扱い。報告管理ページに
        // 全否認の除外が入っておらず、ここをコピーして支払いに使うと満額を払ってしまう穴があったため追加
        // （2026-09-30。報酬管理ページ・管理画面の紹介報酬管理と同じ基準で判定する）
        $allDeniedMap = \App\Models\CampaignApprovalReflection::allDeniedMap();

        $reports->each(function ($report) use ($isCombinedParentView, $codeOwnerMap, $targetAgent, $allDeniedMap) {
            $owner = $isCombinedParentView
                ? ($codeOwnerMap[$report->user?->referred_by_code] ?? $targetAgent)
                : $targetAgent;
            $report->isAllDenied = \App\Models\CampaignApprovalReflection::reportIsAllDenied($allDeniedMap, $report);
            $report->reward = $report->isAllDenied ? 0 : \App\Services\PortalService::calcReward($owner, $report);
        });

        // コードプルダウン
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

        return view('portal.reports', compact(
            'agent', 'targetAgent', 'reports', 'mode', 'month',
            'childId', 'codeOptions', 'codeFilter'
        ));
    }
}
