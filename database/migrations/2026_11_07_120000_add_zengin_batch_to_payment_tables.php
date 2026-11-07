<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitor_reports', function (Blueprint $table) {
            $table->unsignedInteger('zengin_batch')->nullable()->after('payment_status');
        });
        Schema::table('collection_reports', function (Blueprint $table) {
            $table->unsignedInteger('zengin_batch')->nullable()->after('payment_status');
        });
        Schema::table('user_referral_rewards', function (Blueprint $table) {
            $table->unsignedInteger('zengin_batch')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('monitor_reports', function (Blueprint $table) {
            $table->dropColumn('zengin_batch');
        });
        Schema::table('collection_reports', function (Blueprint $table) {
            $table->dropColumn('zengin_batch');
        });
        Schema::table('user_referral_rewards', function (Blueprint $table) {
            $table->dropColumn('zengin_batch');
        });
    }
};
