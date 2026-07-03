<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\MonitorReport;
use Illuminate\Support\Facades\DB;

$campaignId = (int)($argv[1] ?? 0);
if (!$campaignId) {
    echo "使い方: php8.3 fix_delete_campaign_applications.php {campaign_id}\n";
    exit(1);
}

$apps = Application::where('campaign_id', $campaignId)
    ->where('imported_from', 'spreadsheet')
    ->get();

echo "案件ID {$campaignId} のインポート応募レコード: {$apps->count()} 件\n";
if ($apps->isEmpty()) { echo "削除対象なし。\n"; exit; }

echo "\n本当に削除しますか？ [yes/no]: ";
$input = trim(fgets(STDIN));
if ($input !== 'yes') { echo "キャンセルしました。\n"; exit; }

DB::transaction(function () use ($apps) {
    $ids = $apps->pluck('id');
    $updated = MonitorReport::whereIn('application_id', $ids)->update(['application_id' => null]);
    echo "MonitorReport.application_id を null に更新: {$updated} 件\n";
    $deleted = Application::whereIn('id', $ids)->delete();
    echo "応募レコード削除: {$deleted} 件\n";
});

echo "完了しました。\n";
