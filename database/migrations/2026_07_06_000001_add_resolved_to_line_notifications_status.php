<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE line_notifications MODIFY COLUMN status ENUM('sent', 'failed', 'resolved') NOT NULL DEFAULT 'sent'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE line_notifications MODIFY COLUMN status ENUM('sent', 'failed') NOT NULL DEFAULT 'sent'");
    }
};
