<?php

namespace App\Services;

use App\Models\LineNotification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineMessagingService
{
    public function sendPush(int $userId, string $message, string $type = 'general', ?int $applicationId = null): bool
    {
        $user = User::find($userId);

        if (!$user || !$user->line_user_id) {
            $this->log($userId, $applicationId, $type, $message, 'failed');
            return false;
        }

        $token = config('services.line.channel_access_token');

        if (empty($token)) {
            // LINE未設定の場合はログだけ残してtrueを返す（開発環境用）
            Log::info("LINE通知（未設定のためスキップ）: [{$type}] {$user->name} → {$message}");
            $this->log($userId, $applicationId, $type, $message, 'sent');
            return true;
        }

        $response = Http::withToken($token)
            ->post('https://api.line.me/v2/bot/message/push', [
                'to'       => $user->line_user_id,
                'messages' => [['type' => 'text', 'text' => $message]],
            ]);

        $status = $response->successful() ? 'sent' : 'failed';
        $this->log($userId, $applicationId, $type, $message, $status);

        return $response->successful();
    }

    public function sendVideo(int $userId, string $videoPath, string $previewPath, string $type, ?int $applicationId): bool
    {
        $user = User::find($userId);

        if (!$user || !$user->line_user_id) {
            return false;
        }

        $token = config('services.line.channel_access_token');

        if (empty($token)) {
            Log::info("LINE動画（未設定のためスキップ）: [{$type}] {$user->name} → {$videoPath}");
            return true;
        }

        $videoUrl   = url('storage/' . $videoPath);
        $previewUrl = url('storage/' . $previewPath);

        $response = Http::withToken($token)
            ->post('https://api.line.me/v2/bot/message/push', [
                'to'       => $user->line_user_id,
                'messages' => [[
                    'type'                => 'video',
                    'originalContentUrl'  => $videoUrl,
                    'previewImageUrl'     => $previewUrl,
                ]],
            ]);

        return $response->successful();
    }

    public function sendBulk(array $userIds, string $message): array
    {
        $results = ['sent' => 0, 'failed' => 0];

        foreach ($userIds as $userId) {
            $this->sendPush($userId, $message, 'general') ? $results['sent']++ : $results['failed']++;
        }

        return $results;
    }

    private function log(int $userId, ?int $applicationId, string $type, string $message, string $status): void
    {
        LineNotification::create([
            'user_id'           => $userId,
            'application_id'    => $applicationId,
            'notification_type' => $type,
            'message'           => $message,
            'status'            => $status,
            'sent_at'           => now(),
        ]);
    }
}
