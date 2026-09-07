<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monitor_reports', function (Blueprint $table) {
            // 継続前提（Campaign.continuation_condition = '2回前提'/'3回前提'）商品の
            // 何回目の継続購入かを表す。継続前提でない商品・初回報告・その他報告はnullのまま
            $table->unsignedTinyInteger('continuation_round')->nullable()->after('purchase_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_reports', function (Blueprint $table) {
            $table->dropColumn('continuation_round');
        });
    }
};
