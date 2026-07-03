<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\MonitorReport;
use Illuminate\Support\Facades\DB;

$apps = Application::where('imported_from', 'spreadsheet')->get();
echo "削除対象の応募レコード: {$apps->count()} 件\n";

foreach ($apps as $a) {
    $reportCount = MonitorReport::where('application_id', $a->id)->count();
    echo "  ID:{$a->id} user_id:{$a->user_id} campaign_id:{$a->campaign_id} status:{$a->status} 報告紐付:{$reportCount}件\n";
}

echo "\n本当に削除しますか？ [yes/no]: ";
$input = trim(fgets(STDIN));
if ($input !== 'yes') {
    echo "キャンセルしました。\n";
    exit;
}

DB::transaction(function () use ($apps) {
    $ids = $apps->pluck('id');

    // 紐づくMonitorReportのapplication_idをnullに
    $updated = MonitorReport::whereIn('application_id', $ids)->update(['application_id' => null]);
    echo "MonitorReport.application_id を null に更新: {$updated} 件\n";

    // 応募レコード削除
    $deleted = Application::whereIn('id', $ids)->delete();
    echo "応募レコード削除: {$deleted} 件\n";
});

echo "完了しました。\n";
