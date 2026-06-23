<?php

namespace App\Console\Commands;

use App\Models\LineMessageJob;
use App\Services\LineMessagingService;
use Illuminate\Console\Command;

class SendLineMessages extends Command
{
    protected $signature   = 'line:send-messages';
    protected $description = '送信予定の LINE メッセージジョブを実行する';

    public function handle(LineMessagingService $lineService): int
    {
        $jobs = LineMessageJob::where('status', 'pending')
            ->where('send_at', '<=', now())
            ->with('user')
            ->get();

        if ($jobs->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info("送信対象: {$jobs->count()} 件");

        foreach ($jobs as $job) {
            // LINE UID が空の場合はスキップ（ログのみ）
            if (empty($job->line_user_id) && empty($job->user?->line_user_id)) {
                $job->update(['status' => 'failed', 'error_message' => 'LINE UID 未設定']);
                $this->warn("スキップ (LINE UID なし): job#{$job->id}");
                continue;
            }

            $success = $lineService->sendPush(
                $job->user_id,
                $job->message_body,
                $job->send_type,
                $job->application_id
            );

            $job->update([
                'status'  => $success ? 'sent' : 'failed',
                'sent_at' => now(),
                'error_message' => $success ? null : '送信失敗',
            ]);

            $this->line($success
                ? "送信済み: job#{$job->id} [{$job->send_type}]"
                : "失敗: job#{$job->id} [{$job->send_type}]"
            );
        }

        return self::SUCCESS;
    }
}
