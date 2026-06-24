<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM(
            'pending','line_contacted','scheduled','confirming',
            'completed','reported','approved','point_granted','cancelled'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM(
            'pending','selected','rejected','line_contacted','scheduled','confirming',
            'completed','reported','approved','point_granted','cancelled'
        ) NOT NULL DEFAULT 'pending'");
    }
};
