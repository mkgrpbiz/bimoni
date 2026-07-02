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
