<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Machine-to-machine auth for the read-only internal API AI OFFICE calls
 * (see routes/web.php "internal-api" group). Not Sanctum/OAuth — this is a
 * single trusted server-to-server caller, so a shared bearer token is
 * proportionate. Does not touch the session-based admin/member guards.
 */
class VerifyAiOfficeToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.ai_office.token');

        if (! $expected || ! hash_equals($expected, (string) $request->bearerToken())) {
            abort(401, 'invalid or missing token');
        }

        return $next($request);
    }
}
