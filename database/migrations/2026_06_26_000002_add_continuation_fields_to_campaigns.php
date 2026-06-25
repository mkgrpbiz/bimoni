<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedInteger('continuation_cooperation_fee')->nullable()->after('cooperation_fee');
            $table->text('collection_info')->nullable()->after('cancellation_info');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['continuation_cooperation_fee', 'collection_info']);
        });
    }
};
