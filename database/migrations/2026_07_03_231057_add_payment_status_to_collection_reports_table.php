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
        Schema::table('collection_reports', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->after('status');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('collection_reports', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'paid_at']);
        });
    }
};
