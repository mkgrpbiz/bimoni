<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE collection_reports MODIFY COLUMN box_image VARCHAR(255) NULL");
        DB::statement("ALTER TABLE collection_reports MODIFY COLUMN label_image VARCHAR(255) NULL");
        DB::statement("ALTER TABLE collection_reports MODIFY COLUMN estimated_arrival_date DATE NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE collection_reports SET box_image = '' WHERE box_image IS NULL");
        DB::statement("UPDATE collection_reports SET label_image = '' WHERE label_image IS NULL");
        DB::statement("UPDATE collection_reports SET estimated_arrival_date = CURDATE() WHERE estimated_arrival_date IS NULL");
        DB::statement("ALTER TABLE collection_reports MODIFY COLUMN box_image VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE collection_reports MODIFY COLUMN label_image VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE collection_reports MODIFY COLUMN estimated_arrival_date DATE NOT NULL");
    }
};
