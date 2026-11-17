<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_courses', function (Blueprint $table) {
            $table->unsignedInteger('cooperation_fee')->nullable()->after('continuation_fee_4');
            $table->unsignedInteger('continuation_cooperation_fee')->nullable()->after('cooperation_fee');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_courses', function (Blueprint $table) {
            $table->dropColumn(['cooperation_fee', 'continuation_cooperation_fee']);
        });
    }
};
