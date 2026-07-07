<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('bimoni_user_id', 'BMN010492')->first();

if (!$user) {
    echo "ユーザーが見つかりません\n";
    exit(1);
}

echo "削除対象: {$user->bimoni_user_id} / {$user->name} / {$user->line_display_name}\n";
echo "applications: " . $user->applications()->count() . "件\n";
echo "monitor_reports: " . $user->monitorReports()->count() . "件\n";
echo "collection_reports: " . $user->collectionReports()->count() . "件\n";

$user->delete();

echo "削除完了\n";
