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

        $this->info("自動キャンセル: {$expired->count()}件");
    }
}
