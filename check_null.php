<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$reports = \App\Models\MonitorReport::with(['campaign','user'])
    ->where('status','approved')
    ->whereNull('purchase_amount')
    ->get();

$total = 0;
foreach($reports as $r){
    $fee = $r->campaign?->initial_purchase_fee ?? 0;
    $total += $fee;
    echo $r->user?->name . ' / ' . ($r->campaign?->title ?? 'null') . ' / initial_purchase_fee:' . $fee . PHP_EOL;
}
echo '合計initial_purchase_fee: ' . $total . PHP_EOL;
