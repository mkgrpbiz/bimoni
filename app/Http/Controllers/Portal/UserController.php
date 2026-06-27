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
        $agent = \App\Services\PortalService::agent();
        $codes = \App\Services\PortalService::codes($agent);
        $users = \App\Services\PortalService::users($codes);

        $mode  = $request->input('mode', 'all');
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $userIds = $users->pluck('id');

        // 集計（累計 or 月次）
        $appQuery    = Application::whereIn('user_id', $userIds);
        $reportQuery = MonitorReport::whereIn('user_id', $userIds)->where('status', 'approved');

        if ($mode === 'month') {
            $appQuery->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);
            $reportQuery->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);

            // 月次登録数
            $totalRegistered = $users->filter(fn($u) => $u->created_at?->between($month->copy()->startOfMonth(), $month->copy()->endOfMonth()))->count();
        } else {
            $totalRegistered = $users->count();
        }

        $totalApps    = $appQuery->count();
        $totalReports = $reportQuery->count();

        $appCounts    = Application::whereIn('user_id', $userIds)->selectRaw('user_id, count(*) as cnt')->groupBy('user_id')->pluck('cnt', 'user_id');
        $reportCounts = MonitorReport::whereIn('user_id', $userIds)->where('status', 'approved')->selectRaw('user_id, count(*) as cnt')->groupBy('user_id')->pluck('cnt', 'user_id');

        return view('portal.users', compact('agent', 'users', 'appCounts', 'reportCounts', 'mode', 'month', 'totalRegistered', 'totalApps', 'totalReports'));
    }
}
