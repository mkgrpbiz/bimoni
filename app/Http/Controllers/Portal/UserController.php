<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\MonitorReport;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $agent      = \App\Services\PortalService::agent();
        $mode       = $request->input('mode', 'all');
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

        $users = \App\Services\PortalService::users($codes);

        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $userIds = $users->pluck('id');

        // 集計
        $appQuery    = Application::whereIn('user_id', $userIds);
        $reportQuery = MonitorReport::whereIn('user_id', $userIds)->where('status', 'approved');

        if ($mode === 'month') {
            $s = $month->copy()->startOfMonth();
            $e = $month->copy()->endOfMonth();
            $appQuery->whereBetween('created_at', [$s, $e]);
            $reportQuery->whereBetween('created_at', [$s, $e]);
            $totalRegistered = $users->filter(fn($u) => $u->created_at?->between($s, $e))->count();
        } else {
            $totalRegistered = $users->count();
        }

        $totalApps    = $appQuery->count();
        $totalReports = $reportQuery->count();

        $appCounts    = Application::whereIn('user_id', $userIds)->selectRaw('user_id, count(*) as cnt')->groupBy('user_id')->pluck('cnt', 'user_id');
        $reportCounts = MonitorReport::whereIn('user_id', $userIds)->where('status', 'approved')->selectRaw('user_id, count(*) as cnt')->groupBy('user_id')->pluck('cnt', 'user_id');

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

        return view('portal.users', compact(
            'agent', 'targetAgent', 'users', 'appCounts', 'reportCounts',
            'mode', 'month', 'totalRegistered', 'totalApps', 'totalReports',
            'childId', 'codeOptions', 'codeFilter'
        ));
    }
}
