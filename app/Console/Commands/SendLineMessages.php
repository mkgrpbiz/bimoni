<?php

namespace App\Console\Commands;

use App\Models\Campaign;
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

            // monitor_guide 送信成功時に案内動画があれば追送
            if ($success && $job->send_type === 'monitor_guide' && $job->campaign_id) {
                $campaign = Campaign::find($job->campaign_id);
                $previewPath = $campaign?->monitor_video_thumbnail ?? $campaign?->thumbnail;
                if ($campaign?->monitor_video && $previewPath) {
                    $lineService->sendVideo(
                        $job->user_id,
                        $campaign->monitor_video,
                        $previewPath,
                        $job->send_type,
                        $job->application_id
                    );
                    $this->line("動画追送: job#{$job->id} campaign#{$job->campaign_id}");
                } elseif ($campaign?->monitor_video) {
                    $this->warn("動画スキップ（サムネイル未設定）: job#{$job->id} campaign#{$job->campaign_id}");
                }
            }
        }

        return self::SUCCESS;
    }
}
