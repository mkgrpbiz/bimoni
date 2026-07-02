<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // purchase_type に 'other' を追加
        DB::statement("ALTER TABLE monitor_reports MODIFY COLUMN purchase_type ENUM('initial','continuation','other') NOT NULL DEFAULT 'initial'");

        // application_id を nullable 化（その他報告は応募に紐づかない）
        // まず外部キー制約とユニーク制約を外す
        DB::statement("ALTER TABLE monitor_reports DROP FOREIGN KEY monitor_reports_application_id_foreign");
        DB::statement("ALTER TABLE monitor_reports DROP INDEX monitor_reports_application_id_unique");
        DB::statement("ALTER TABLE monitor_reports MODIFY COLUMN application_id BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE monitor_reports ADD CONSTRAINT monitor_reports_application_id_foreign FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE monitor_reports MODIFY COLUMN purchase_type ENUM('initial','continuation') NOT NULL DEFAULT 'initial'");
        DB::statement("ALTER TABLE monitor_reports DROP FOREIGN KEY monitor_reports_application_id_foreign");
        DB::statement("ALTER TABLE monitor_reports MODIFY COLUMN application_id BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE monitor_reports ADD UNIQUE (application_id)");
        DB::statement("ALTER TABLE monitor_reports ADD CONSTRAINT monitor_reports_application_id_foreign FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE");
    }
};
