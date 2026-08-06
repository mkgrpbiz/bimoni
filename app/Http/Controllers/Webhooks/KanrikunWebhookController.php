<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\KanrikunMessageIngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook receiver for BIMONI管理君's LINE channel. Signature is verified
 * by the 'kanrikun.signature' middleware before this controller runs.
 * Always responds 200 (LINE requires a fast ack) — a single malformed event
 * must never take down the whole webhook or affect other BIMONI behavior.
 */
class KanrikunWebhookController extends Controller
{
    public function handle(Request $request, KanrikunMessageIngestService $ingest): JsonResponse
    {
        foreach ($request->input('events', []) as $event) {
            try {
                $ingest->handle($event);
            } catch (\Throwable $e) {
                Log::error('kanrikun webhook event failed', ['error' => $e->getMessage(), 'event' => $event]);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
