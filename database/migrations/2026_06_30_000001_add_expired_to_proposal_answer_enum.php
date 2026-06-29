<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE applications MODIFY proposal_answer ENUM('yes', 'no', 'expired') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE applications MODIFY proposal_answer ENUM('yes', 'no') NULL");
    }
};
