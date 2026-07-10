<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE line_message_jobs MODIFY send_type ENUM('proposal', 'monitor_guide', 'reminder', 'report_request', 'campaign_closed') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE line_message_jobs MODIFY send_type ENUM('proposal', 'monitor_guide', 'reminder', 'report_request') NOT NULL");
    }
};
