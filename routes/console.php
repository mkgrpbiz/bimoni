<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// LINE メッセージジョブを毎分実行
Schedule::command('line:send-messages')->everyMinute();

// 実施案内日時切れの打診を自動キャンセル（毎5分）
Schedule::command('proposals:auto-cancel')->everyFiveMinutes();
