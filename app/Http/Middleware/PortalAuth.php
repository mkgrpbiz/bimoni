<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PortalAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('portal_agent_id')) {
            abort(403, 'ポータルへのアクセスにはURLが必要です。');
        }

        $agent = \App\Models\Agent::with(['codes', 'children.codes', 'parent'])
            ->find(session('portal_agent_id'));

        if (!$agent) {
            abort(403, 'アクセス情報が無効です。');
        }

        \Illuminate\Support\Facades\View::share('portalAgent', $agent);

        return $next($request);
    }
}
