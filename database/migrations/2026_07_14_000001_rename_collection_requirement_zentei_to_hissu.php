<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE campaigns SET collection_requirement = '回収必須' WHERE collection_requirement = '回収前提'");
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN collection_requirement ENUM('回収必須', '回収不要') NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE campaigns SET collection_requirement = '回収前提' WHERE collection_requirement = '回収必須'");
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN collection_requirement ENUM('回収前提', '回収不要') NULL");
    }
};
