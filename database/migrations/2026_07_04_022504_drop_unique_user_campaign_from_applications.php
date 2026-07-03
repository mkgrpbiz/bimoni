<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // user_id FK が composite unique を使っているため、先に単体インデックスを追加してから unique を DROP
        Schema::table('applications', function (Blueprint $table) {
            $table->index('user_id', 'applications_user_id_index');
        });
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique('applications_user_id_campaign_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->unique(['user_id', 'campaign_id']);
            $table->dropIndex('applications_user_id_index');
        });
    }
};
