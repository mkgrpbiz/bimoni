<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 先にEnumへ新値を追加（旧値も残したまま）してからUPDATEしないと、既存データがある環境で
        // 旧Enumのままの値へのUPDATEが「Data truncated」エラーになる（本番で実際に発生）
        DB::statement("ALTER TABLE campaign_courses MODIFY COLUMN course_type ENUM('単発', '継続', '継続前提') NOT NULL DEFAULT '単発'");
        DB::statement("UPDATE campaign_courses SET course_type = '継続前提' WHERE course_type = '継続'");
        DB::statement("ALTER TABLE campaign_courses MODIFY COLUMN course_type ENUM('単発', '継続前提') NOT NULL DEFAULT '単発'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE campaign_courses MODIFY COLUMN course_type ENUM('単発', '継続', '継続前提') NOT NULL DEFAULT '単発'");
        DB::statement("UPDATE campaign_courses SET course_type = '継続' WHERE course_type = '継続前提'");
        DB::statement("ALTER TABLE campaign_courses MODIFY COLUMN course_type ENUM('単発', '継続') NOT NULL DEFAULT '単発'");
    }
};
