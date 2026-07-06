<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use Carbon\Carbon;

// 直近24時間以内にline_contactedになった応募を確認
$apps = Application::where('status', 'line_contacted')
    ->where('line_contacted_at', '>=', Carbon::now()->subHours(24))
    ->with(['campaign', 'user'])
    ->orderByDesc('line_contacted_at')
    ->get();

// キャンセル済みで最近更新された応募も確認
$cancelledApps = Application::where('status', 'cancelled')
    ->where('updated_at', '>=', Carbon::now()->subHours(24))
    ->with(['campaign', 'user'])
    ->orderByDesc('updated_at')
    ->limit(5)
    ->get();

echo "=== 直近24時間の打診中応募 ===\n";
foreach ($apps as $a) {
    echo "ID: {$a->id}\n";
    echo "  ユーザー: " . ($a->user->name ?? 'N/A') . "\n";
    echo "  案件: " . ($a->campaign->title ?? 'N/A') . "\n";
    echo "  ステータス: {$a->status}\n";
    echo "  invited_at: " . ($a->invited_at ? $a->invited_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "  invited_end_at: " . ($a->invited_end_at ? $a->invited_end_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "  proposal_token: " . ($a->proposal_token ? substr($a->proposal_token, 0, 10) . '...' : 'NULL') . "\n";
    echo "  line_contacted_at: " . ($a->line_contacted_at ? $a->line_contacted_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "  now >= invited_at?: " . ($a->invited_at && now()->gte($a->invited_at) ? 'YES(期限切れ!)' : 'NO(有効)') . "\n";
    echo "\n";
}

echo "\n=== 直近24時間にキャンセルされた応募 ===\n";
foreach ($cancelledApps as $a) {
    echo "ID: {$a->id}\n";
    echo "  ユーザー: " . ($a->user->name ?? 'N/A') . "\n";
    echo "  案件: " . ($a->campaign->title ?? 'N/A') . "\n";
    echo "  ステータス: {$a->status}\n";
    echo "  invited_at: " . ($a->invited_at ? $a->invited_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "  proposal_token: " . ($a->proposal_token ? substr($a->proposal_token, 0, 10) . '...' : 'NULL') . "\n";
    echo "  updated_at: {$a->updated_at}\n";
    echo "\n";
}
