<?php

namespace App\Services;

use App\Models\LineNotification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineMessagingService
{
    // sendPush()が失敗した直後に、呼び出し元がLINEから実際に返ってきたエラー内容を読めるようにする
    // （戻り値はbool互換を保ったまま、追加メッセージのquota超過等の原因特定に使う。2026-10-30追加）
    public ?string $lastError = null;

    public function sendPush(int $userId, string $message, string $type = 'general', ?int $applicationId = null): bool
    {
        $this->lastError = null;
        $user = User::find($userId);

        if (!$user || !$user->line_user_id) {
            $this->lastError = 'LINE UID未設定';
            $this->log($userId, $applicationId, $type, $message, 'failed', $this->lastError);
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
        if (!$response->successful()) {
            // LINE APIのエラーレスポンスは通常 {"message": "...", "details": [...]} 形式
            $this->lastError = $response->status() . ': ' . ($response->json('message') ?? $response->body());
        }
        $this->log($userId, $applicationId, $type, $message, $status, $this->lastError);

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

    private function log(int $userId, ?int $applicationId, string $type, string $message, string $status, ?string $errorMessage = null): void
    {
        LineNotification::create([
            'user_id'           => $userId,
            'application_id'    => $applicationId,
            'notification_type' => $type,
            'message'           => $message,
            'status'            => $status,
            'error_message'     => $errorMessage,
            'sent_at'           => now(),
        ]);
    }
}
