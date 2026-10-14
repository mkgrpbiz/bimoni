<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;

class AutoCancelExpiredProposals extends Command
{
    protected $signature   = 'proposals:auto-cancel';
    protected $description = '実施案内日時・PR期限を過ぎた未回答の打診を自動処理する';

    public function handle(): void
    {
        $applications = Application::where('status', 'line_contacted')->get();

        $cancelledCount = 0;
        $pendingCount   = 0;

        foreach ($applications as $application) {
            if (!$application->expireProposalIfNeeded()) {
                continue;
            }
            if ($application->status === 'cancelled') {
                $cancelledCount++;
            } else {
                $pendingCount++;
            }
        }

        $this->info("自動処理: キャンセル{$cancelledCount}件、応募に戻した{$pendingCount}件");
    }
}
