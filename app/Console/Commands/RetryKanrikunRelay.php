<?php

namespace App\Console\Commands;

use App\Models\KanrikunMessage;
use App\Services\KanrikunRelayService;
use Illuminate\Console\Command;

class RetryKanrikunRelay extends Command
{
    protected $signature = 'kanrikun:retry-relay';
    protected $description = 'AI OFFICEへのリレーに失敗したBIMONI管理君のLINEメッセージを再送する';

    public function handle(KanrikunRelayService $relay): int
    {
        $pending = KanrikunMessage::whereNull('relayed_to_ai_office_at')
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
