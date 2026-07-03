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
$totalInitial = 0;

foreach($reports as $r){
    $purchase = $r->purchase_amount ?? 0;
    $isInitial = $r->purchase_type !== 'continuation';
    $coop = $isInitial ? ($r->campaign?->cooperation_fee ?? 0) : 0;
    $bonus = $r->application?->bonus_amount ?? 0;
    $initial = $r->campaign?->initial_purchase_fee ?? 0;

    $totalPurchase += $purchase;
    $totalCoop += $coop;
    $totalBonus += $bonus;
    $totalInitial += $initial;
}

$newTotal = $totalPurchase + $totalCoop + $totalBonus;
$oldTotal = $totalInitial + $totalCoop + $totalBonus;

echo '件数: ' . $reports->count() . PHP_EOL;
echo PHP_EOL;
echo '【現在の計算】purchase_amount + cooperation_fee + bonus' . PHP_EOL;
echo '  purchase_amount合計: ' . number_format($totalPurchase) . PHP_EOL;
echo '  cooperation_fee合計: ' . number_format($totalCoop) . PHP_EOL;
echo '  bonus合計: ' . number_format($totalBonus) . PHP_EOL;
echo '  → 総合計: ' . number_format($newTotal) . PHP_EOL;
echo PHP_EOL;
echo '【旧計算参考】initial_purchase_fee + cooperation_fee + bonus' . PHP_EOL;
echo '  initial_purchase_fee合計: ' . number_format($totalInitial) . PHP_EOL;
echo '  → 総合計: ' . number_format($oldTotal) . PHP_EOL;
echo PHP_EOL;
echo 'purchase_amountがnullの件数: ' . $reports->whereNull('purchase_amount')->count() . PHP_EOL;
echo 'purchase_amountが0の件数: ' . $reports->where('purchase_amount', 0)->count() . PHP_EOL;
