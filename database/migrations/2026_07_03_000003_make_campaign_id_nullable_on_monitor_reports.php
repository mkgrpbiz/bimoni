<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE monitor_reports DROP FOREIGN KEY monitor_reports_campaign_id_foreign");
        DB::statement("ALTER TABLE monitor_reports MODIFY COLUMN campaign_id BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE monitor_reports ADD CONSTRAINT monitor_reports_campaign_id_foreign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE");
    }

    public function down(): void
    {
        DB::statement("UPDATE monitor_reports SET campaign_id = 0 WHERE campaign_id IS NULL");
        DB::statement("ALTER TABLE monitor_reports DROP FOREIGN KEY monitor_reports_campaign_id_foreign");
        DB::statement("ALTER TABLE monitor_reports MODIFY COLUMN campaign_id BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE monitor_reports ADD CONSTRAINT monitor_reports_campaign_id_foreign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE");
    }
};
