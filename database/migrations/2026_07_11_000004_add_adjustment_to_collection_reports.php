<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_reports', function (Blueprint $table) {
            $table->integer('adjustment_amount')->nullable()->after('cooperation_fee');
            $table->string('adjustment_reason')->nullable()->after('adjustment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('collection_reports', function (Blueprint $table) {
            $table->dropColumn(['adjustment_amount', 'adjustment_reason']);
        });
    }
};
