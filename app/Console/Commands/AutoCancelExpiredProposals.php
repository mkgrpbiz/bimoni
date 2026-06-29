<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\ApplicationStatusLog;
use Illuminate\Console\Command;

class AutoCancelExpiredProposals extends Command
{
    protected $signature   = 'proposals:auto-cancel';
    protected $description = '実施案内日時を過ぎた未回答の打診を自動キャンセルする';

    public function handle(): void
    {
        // 通常打診：invited_at を過ぎても未回答
        $expired = Application::where('status', 'line_contacted')
            ->whereNotNull('invited_at')
            ->where('invited_at', '<=', now())
            ->get();

        foreach ($expired as $app) {
            $app->update([
                'status'               => 'cancelled',
                'proposal_answered_at' => now(),
                'proposal_answer'      => 'expired',
            ]);
            ApplicationStatusLog::create([
                'application_id' => $app->id,
                'from_status'    => 'line_contacted',
                'to_status'      => 'cancelled',
                'changed_by'     => null,
                'memo'           => '実施案内日時までに回答なし・自動キャンセル',
            ]);
        }

        // PR打診（invited_at なし）：invited_end_at を過ぎても未回答
        $expiredPr = Application::where('status', 'line_contacted')
            ->whereNull('invited_at')
            ->whereNotNull('invited_end_at')
            ->where('invited_end_at', '<=', now())
            ->whereHas('campaign', fn($q) => $q->where('campaign_type', 'pr')->where('pr_media', 'IF'))
            ->get();

        foreach ($expiredPr as $app) {
            $app->update([
                'status'               => 'cancelled',
                'proposal_answered_at' => now(),
                'proposal_answer'      => 'expired',
            ]);
            ApplicationStatusLog::create([
                'application_id' => $app->id,
                'from_status'    => 'line_contacted',
                'to_status'      => 'cancelled',
                'changed_by'     => null,
                'memo'           => 'PR打診期限を過ぎたため自動キャンセル',
            ]);
        }

        $total = $expired->count() + $expiredPr->count();
        $this->info("自動キャンセル: {$total}件（通常: {$expired->count()}件、PR打診: {$expiredPr->count()}件）");
    }
}
