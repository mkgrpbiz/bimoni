<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * LINE webhook events only include a messageId for image/file/video
 * attachments — the binary itself must be fetched separately from LINE's
 * content API. Stored on the existing 'public' disk (already symlinked via
 * storage:link), same convention as LineMessagingService::sendVideo().
 */
class KanrikunContentFetcher
{
    public function fetchAndStore(array $message): array
    {
        $token = config('services.kanrikun.channel_access_token');

        if (! $token) {
            // チャンネル未設定（本番稼働前など）。メッセージ自体は保存を続行し、添付なしとする。
            return [];
        }

        $response = Http::withToken($token)->timeout(15)
            ->get("https://api-data.line.me/v2/bot/message/{$message['id']}/content");

        if ($response->failed()) {
            Log::warning('kanrikun content fetch failed', [
                'message_id' => $message['id'],
                'status' => $response->status(),
            ]);
            return [];
        }

        $mime = $response->header('Content-Type');
        $path = 'kanrikun/'.date('Y/m').'/'.$message['id'].'.'.$this->extensionFor($mime);

        Storage::disk('public')->put($path, $response->body());

        return [
            'attachment_path' => $path,
            'attachment_mime' => $mime,
            'file_name' => $message['fileName'] ?? null,
            'file_size' => $message['fileSize'] ?? $response->header('Content-Length'),
        ];
    }

    private function extensionFor(?string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'video/mp4' => 'mp4',
            'audio/x-m4a', 'audio/m4a' => 'm4a',
            default => 'bin',
        };
    }
}
