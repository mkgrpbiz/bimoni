<?php

namespace App\Services;

use App\Models\KanrikunMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Relays a stored KanrikunMessage to AI OFFICE (BIMONI → AI OFFICE
 * direction, distinct token from services.ai_office.token which authenticates
 * the opposite direction). Must never throw — a relay failure must not affect
 * the LINE webhook's own 200 response or any other BIMONI behavior.
 */
class KanrikunRelayService
{
    public function push(KanrikunMessage $message): void
    {
        $url = config('services.kanrikun.relay_url');
        $token = config('services.kanrikun.relay_token');

        if (! $url || ! $token) {
            Log::info('AI OFFICE relay未設定のためスキップ', ['message_id' => $message->id]);
            return;
        }

        try {
            $response = Http::withToken($token)->timeout(5)->post($url, $this->payload($message));

            if ($response->successful()) {
                $message->update(['relayed_to_ai_office_at' => now()]);
            } else {
                $this->recordFailure($message, "HTTP {$response->status()}");
            }
        } catch (\Throwable $e) {
            $this->recordFailure($message, $e->getMessage());
        }
    }

    private function recordFailure(KanrikunMessage $message, string $error): void
    {
        $message->increment('relay_attempts');
        $message->update(['relay_last_error' => $error]);
        Log::warning('AI OFFICE relay failed', ['message_id' => $message->id, 'error' => $error]);
    }

    private function payload(KanrikunMessage $message): array
    {
        $contact = $message->contact;

        return [
            'kanrikun_message_id' => $message->id,
            'contact' => [
                'line_user_id' => $contact->line_user_id,
                'display_name' => $contact->display_name,
                'picture_url' => $contact->picture_url,
                'is_anonymous_group_sender' => $contact->is_anonymous_group_sender,
            ],
            'source_type' => $message->source_type,
            'line_group_id' => $message->line_group_id,
            'message_type' => $message->message_type,
            'text_body' => $message->text_body,
            'sticker_package_id' => $message->sticker_package_id,
            'sticker_id' => $message->sticker_id,
            'attachment_url' => $message->attachmentUrl(),
            'file_name' => $message->file_name,
            'line_sent_at' => $message->line_sent_at->toIso8601String(),
        ];
    }
}
