<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;

class AutoDeclineExpiredContinuations extends Command
{
    protected $signature   = 'continuations:auto-decline';
    protected $description = '継続依頼LINE送信から24時間経過し未回答の応募を自動的に継続NGにする';

    public function handle(): void
    {
        $expired = Application::whereNotNull('continuation_sent_at')
            ->whereNull('continuation_response')
            ->where('continuation_sent_at', '<=', now()->subHours(24))
            ->get();

        foreach ($expired as $app) {
            $app->update([
                'continuation_response'     => 'not_possible',
                'continuation_responded_at' => now(),
            ]);
        }

        $this->info("自動NG化: {$expired->count()}件");
    }
}
