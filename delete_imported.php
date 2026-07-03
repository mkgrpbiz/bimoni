<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MonitorReport;
use App\Models\Application;

$apps = Application::where('imported_from', 'spreadsheet')->get();
$appIds = $apps->pluck('id');

$reportCount = MonitorReport::whereIn('application_id', $appIds)->count();
$appCount = $apps->count();

echo "削除対象: 報告 {$reportCount}件 / 応募 {$appCount}件" . PHP_EOL;

MonitorReport::whereIn('application_id', $appIds)->delete();
Application::where('imported_from', 'spreadsheet')->delete();

echo "削除完了" . PHP_EOL;
