<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CollectionReport;

$targets = CollectionReport::where('item_count', 5)
    ->where('cooperation_fee', '<', 4000)
    ->get();

echo "対象件数: " . $targets->count() . "\n";

foreach ($targets as $r) {
    $old = $r->cooperation_fee;
    $new = $r->cooperation_fee + $r->shipping_fee;
    $r->cooperation_fee = $new;
    $r->saveQuietly();
    echo "ID:{$r->id} ¥{$old} → ¥{$new}（送料¥{$r->shipping_fee}を戻す）\n";
}

echo "完了\n";
