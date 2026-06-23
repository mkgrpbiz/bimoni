<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE line_notifications MODIFY COLUMN notification_type ENUM(
            'applied','selected','schedule','report_request','point_granted','general',
            'proposal','monitor_guide','reminder'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE line_notifications MODIFY COLUMN notification_type ENUM(
            'applied','selected','schedule','report_request','point_granted','general'
        ) NOT NULL");
    }
};
