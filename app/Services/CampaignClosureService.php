<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationStatusLog;
use App\Models\Campaign;
use App\Models\EndCancelSetting;
use App\Models\LineMessageJob;
use Carbon\Carbon;

class CampaignClosureService
{
    // キャンペーン終了時に、進行中の打診・案内予約を応募状態へ戻し、終了案内LINEを予約する
    public function handleClosure(Campaign $campaign, ?int $adminId = null): void
    {
        $settings = EndCancelSetting::current();

        $applications = $campaign->applications()
            ->whereIn('status', ['line_contacted', 'scheduled', 'confirming'])
            ->with('user')
            ->get();

        foreach ($applications as $application) {
            if (!$this->isStillPending($application)) {
                continue;
            }

            LineMessageJob::where('application_id', $application->id)
                ->whereIn('send_type', ['monitor_guide', 'reminder'])
                ->where('status', 'pending')
                ->update(['status' => 'canceled']);

            $fromStatus = $application->status;

            $application->update([
                'status'               => 'pending',
                'proposal_token'       => null,
                'proposal_answer'      => null,
                'proposal_answered_at' => null,
                'invited_at'           => null,
                'invited_end_at'       => null,
            ]);

            ApplicationStatusLog::create([
                'application_id' => $application->id,
                'from_status'    => $fromStatus,
                'to_status'      => 'pending',
                'changed_by'     => $adminId,
                'memo'           => 'キャンペーン終了のため応募状態に戻しました',
            ]);

            LineMessageJob::create([
                'application_id' => $application->id,
                'user_id'        => $application->user_id,
                'campaign_id'    => $campaign->id,
                'line_user_id'   => $application->user?->line_user_id,
                'send_type'      => 'campaign_closed',
                'message_body'   => $campaign->resolveTemplate($settings->message_template ?? ''),
                'send_at'        => $this->computeSendAt($settings),
                'status'         => 'pending',
            ]);
        }
    }

    // 案内予定時刻(invited_at)がすでに過ぎている場合は実施済み/実施中の可能性があるため対象外
    private function isStillPending(Application $application): bool
    {
        $now = now();

        if ($application->invited_at && $application->invited_at->lte($now)) {
            return false;
        }

        // PR打診等 invited_at が null のケースは invited_end_at（回答期限）で判定
        if (!$application->invited_at && $application->invited_end_at && $application->invited_end_at->lte($now)) {
            return false;
        }

        return true;
    }

    private function computeSendAt(EndCancelSetting $settings): Carbon
    {
        $now = now();

        if ($now->hour >= $settings->send_start_hour && $now->hour < $settings->send_end_hour) {
            return $now;
        }

        $next = $now->copy()->setTime($settings->send_start_hour, 0, 0);
        if ($next->lte($now)) {
            $next->addDay();
        }

        return $next;
    }
}
