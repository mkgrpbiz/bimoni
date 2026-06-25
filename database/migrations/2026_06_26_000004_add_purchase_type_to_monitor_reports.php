<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitor_reports', function (Blueprint $table) {
            $table->enum('purchase_type', ['initial', 'continuation'])->default('initial')->after('report_body');
            $table->string('payment_method')->nullable()->after('purchase_type');
            $table->string('payment_method_other')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('monitor_reports', function (Blueprint $table) {
            $table->dropColumn(['purchase_type', 'payment_method', 'payment_method_other']);
        });
    }
};
