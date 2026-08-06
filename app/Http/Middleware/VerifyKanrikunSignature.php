<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies LINE's X-Line-Signature header (HMAC-SHA256 of the raw request
 * body, base64-encoded) for BIMONI管理君's LINE channel webhook. Must use
 * the raw body ($request->getContent()), not re-serialized JSON, since LINE
 * signs the exact bytes it sent.
 */
class VerifyKanrikunSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.kanrikun.channel_secret');
        $signature = $request->header('X-Line-Signature');

        if (! $secret || ! $signature) {
            abort(401, 'missing signature or secret not configured');
        }

        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        if (! hash_equals($expected, $signature)) {
            abort(401, 'invalid signature');
        }

        return $next($request);
    }
}
