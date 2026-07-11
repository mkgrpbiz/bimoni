<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('cancellation_phone')->nullable()->after('cancellation_info');
            $table->string('cancellation_hours')->nullable()->after('cancellation_phone');
            $table->string('cancellation_mypage_url')->nullable()->after('cancellation_hours');
            $table->string('cancellation_email')->nullable()->after('cancellation_mypage_url');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['cancellation_phone', 'cancellation_hours', 'cancellation_mypage_url', 'cancellation_email']);
        });
    }
};
