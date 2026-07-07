<?php
// 承認済み報告がある応募のステータスを approved に修正
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// application_id で紐づいている場合
$count1 = DB::table('applications')
    ->whereIn('status', ['completed', 'confirming'])
    ->whereExists(function ($q) {
        $q->select(DB::raw(1))
          ->from('monitor_reports')
          ->whereColumn('monitor_reports.application_id', 'applications.id')
          ->where('monitor_reports.status', 'approved');
    })
    ->update(['status' => 'approved']);

// application_id なし → user_id + campaign_id で紐づいている場合
$count2 = DB::table('applications')
    ->whereIn('status', ['completed', 'confirming'])
    ->whereExists(function ($q) {
        $q->select(DB::raw(1))
          ->from('monitor_reports')
          ->whereColumn('monitor_reports.user_id', 'applications.user_id')
          ->whereColumn('monitor_reports.campaign_id', 'applications.campaign_id')
          ->whereNull('monitor_reports.application_id')
          ->where('monitor_reports.status', 'approved');
    })
    ->update(['status' => 'approved']);

echo "修正件数（application_id紐づき）: {$count1}\n";
echo "修正件数（user/campaign紐づき）: {$count2}\n";
echo "完了\n";
