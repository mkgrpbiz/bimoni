<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$reports = \App\Models\MonitorReport::with(['campaign','application'])
    ->where('status','approved')
    ->get();

$totalPurchase = 0;
$totalCoop = 0;
$totalBonus = 0;
$totalOld = 0; // initial_purchase_fee + cooperation_fee

foreach($reports as $r){
    $purchase = $r->purchase_amount ?? 0;
    $coop = $r->purchase_type !== 'continuation' ? ($r->campaign?->cooperation_fee ?? 0) : 0;
    $bonus = $r->application?->bonus_amount ?? 0;
    $initialFee = $r->campaign?->initial_purchase_fee ?? 0;

    $totalPurchase += $purchase;
    $totalCoop += $coop;
    $totalBonus += $bonus;
    $totalOld += $initialFee + ($r->campaign?->cooperation_fee ?? 0) + $bonus;
}

echo '【現在の計算（purchase_amount + cooperation_fee）】' . PHP_EOL;
echo 'purchase_amount合計: ' . number_format($totalPurchase) . PHP_EOL;
echo 'cooperation_fee合計: ' . number_format($totalCoop) . PHP_EOL;
echo 'bonus合計: ' . number_format($totalBonus) . PHP_EOL;
echo '総合計: ' . number_format($totalPurchase + $totalCoop + $totalBonus) . PHP_EOL;
echo PHP_EOL;
echo '【旧計算（initial_purchase_fee + cooperation_fee）】' . PHP_EOL;
echo '総合計: ' . number_format($totalOld) . PHP_EOL;
echo PHP_EOL;
echo '件数: ' . $reports->count() . PHP_EOL;
