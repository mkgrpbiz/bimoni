<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('cooperation_fee_formula', 50)->nullable()->after('cooperation_fee');
            $table->string('continuation_cooperation_fee_formula', 50)->nullable()->after('continuation_cooperation_fee');
        });

        Schema::table('monitor_reports', function (Blueprint $table) {
            $table->unsignedInteger('purchase_amount')->nullable()->after('purchase_type');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['cooperation_fee_formula', 'continuation_cooperation_fee_formula']);
        });
        Schema::table('monitor_reports', function (Blueprint $table) {
            $table->dropColumn('purchase_amount');
        });
    }
};
