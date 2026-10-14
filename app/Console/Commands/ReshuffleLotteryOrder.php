<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReshuffleLotteryOrder extends Command
{
    protected $signature   = 'applications:reshuffle-lottery';
    protected $description = '応募中（pending）の案内順抽選（lottery_order）を毎朝振り直す';

    public function handle(): void
    {
        $count = Application::where('status', 'pending')->update(['lottery_order' => DB::raw('RAND()')]);
        $this->info("抽選を振り直しました: {$count}件");
    }
}
