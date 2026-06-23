<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->text('monitor_invite_message')->nullable()->after('notes');
            $table->unsignedTinyInteger('target_male_ratio')->nullable()->after('target_gender_ratio');
            $table->unsignedTinyInteger('target_female_ratio')->nullable()->after('target_male_ratio');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['monitor_invite_message', 'target_male_ratio', 'target_female_ratio']);
        });
    }
};
