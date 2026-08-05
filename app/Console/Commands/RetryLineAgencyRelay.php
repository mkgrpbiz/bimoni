<?php

namespace App\Console\Commands;

use App\Models\LineAgencyMessage;
use App\Services\AiOfficeRelayService;
use Illuminate\Console\Command;

class RetryLineAgencyRelay extends Command
{
    protected $signature = 'line-agency:retry-relay';
    protected $description = 'AI OFFICEへのリレーに失敗したLINE代理店メッセージを再送する';

    public function handle(AiOfficeRelayService $relay): int
    {
        $pending = LineAgencyMessage::whereNull('relayed_to_ai_office_at')
            ->where('relay_attempts', '<', 10)
            ->orderBy('id')
            ->limit(50)
            ->get();

        if ($pending->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info("再送対象: {$pending->count()} 件");

        foreach ($pending as $message) {
            $relay->push($message);
        }

        return self::SUCCESS;
    }
}
