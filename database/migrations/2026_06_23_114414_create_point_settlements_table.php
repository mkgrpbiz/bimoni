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
        Schema::create('point_settlements', function (Blueprint $table) {
            $table->id();
            $table->date('settlement_month');
            $table->date('payment_due_date');
            $table->enum('status', ['open', 'closed', 'paid'])->default('open');
            $table->unsignedInteger('total_amount')->default(0);
            $table->foreignId('closed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_settlements');
    }
};
