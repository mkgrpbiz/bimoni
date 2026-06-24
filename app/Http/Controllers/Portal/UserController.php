<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $agent   = \App\Services\PortalService::agent();
        $codes   = \App\Services\PortalService::codes($agent);
        $users   = \App\Services\PortalService::users($codes);

        $userIds = $users->pluck('id');
        $appCounts    = \App\Models\Application::whereIn('user_id', $userIds)->selectRaw('user_id, count(*) as cnt')->groupBy('user_id')->pluck('cnt','user_id');
        $reportCounts = \App\Models\MonitorReport::whereIn('user_id', $userIds)->where('status','approved')->selectRaw('user_id, count(*) as cnt')->groupBy('user_id')->pluck('cnt','user_id');

        return view('portal.users', compact('agent','users','appCounts','reportCounts'));
    }
}
