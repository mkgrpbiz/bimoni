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

        $reports->each(function ($report) use ($isCombinedParentView, $codeOwnerMap, $targetAgent) {
            $owner = $isCombinedParentView
                ? ($codeOwnerMap[$report->user?->referred_by_code] ?? $targetAgent)
                : $targetAgent;
            $report->reward = \App\Services\PortalService::calcReward($owner, $report);
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
