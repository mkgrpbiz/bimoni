<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ENUM に confirming を追加
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM(
            'pending','selected','rejected',
            'line_contacted','scheduled','confirming',
            'completed','reported','approved','point_granted','cancelled'
        ) NOT NULL DEFAULT 'pending'");

        Schema::table('applications', function (Blueprint $table) {
            $table->timestamp('sounded_at')->nullable()->after('line_contacted_at');
            $table->timestamp('reserved_at')->nullable()->after('sounded_at');
            $table->timestamp('monitoring_confirmed_at')->nullable()->after('reserved_at');
            $table->dateTime('invited_at')->nullable()->after('monitoring_confirmed_at');
            $table->dateTime('invited_end_at')->nullable()->after('invited_at');
            $table->date('continuation_invite_date')->nullable()->after('invited_end_at');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'sounded_at', 'reserved_at', 'monitoring_confirmed_at',
                'invited_at', 'invited_end_at', 'continuation_invite_date',
            ]);
        });

        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM(
            'pending','selected','rejected',
            'line_contacted','scheduled',
            'completed','reported','approved','point_granted','cancelled'
        ) NOT NULL DEFAULT 'pending'");
    }
};
