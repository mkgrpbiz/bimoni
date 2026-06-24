<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->enum('closing_date', ['20日', '25日', '月末'])->nullable()->after('continuation_rate');
            $table->enum('payment_timing', ['翌月末', '翌々月末'])->nullable()->after('closing_date');
            $table->unsignedInteger('sort_order')->default(0)->after('payment_timing');
            $table->boolean('is_visible')->default(true)->after('sort_order');
        });

        // status ENUM に paused を追加
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft','published','paused','closed') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['closing_date', 'payment_timing', 'sort_order', 'is_visible']);
        });
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft','published','closed') NOT NULL DEFAULT 'draft'");
    }
};
