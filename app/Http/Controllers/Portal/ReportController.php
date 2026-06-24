<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $agent  = \App\Services\PortalService::agent();
        $codes  = \App\Services\PortalService::codes($agent);
        $mode   = $request->get('mode', 'all'); // all or month

        $month = null;
        if ($mode === 'month') {
            $month = $request->filled('month')
                ? \Carbon\Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
                : \Carbon\Carbon::now()->startOfMonth();
        }

        $reports = \App\Services\PortalService::approvedReports($codes, $month);

        return view('portal.reports', compact('agent','reports','mode','month'));
    }
}
