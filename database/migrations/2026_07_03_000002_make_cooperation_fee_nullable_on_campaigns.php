<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN cooperation_fee INT UNSIGNED NULL DEFAULT NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE campaigns SET cooperation_fee = 0 WHERE cooperation_fee IS NULL");
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN cooperation_fee INT UNSIGNED NOT NULL DEFAULT 0");
    }
};
