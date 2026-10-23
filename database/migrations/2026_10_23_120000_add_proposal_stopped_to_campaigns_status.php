<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // status ENUM に proposal_stopped（打診ストップ）を追加。paused（募集停止）とclosed（案内終了）の間の状態。
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft','published','paused','proposal_stopped','closed') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft','published','paused','closed') NOT NULL DEFAULT 'draft'");
    }
};
