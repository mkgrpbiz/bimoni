<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_daily_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->date('target_date');
            $table->unsignedInteger('planned_count')->default(0);
            $table->unsignedInteger('invited_count')->default(0);
            $table->unsignedInteger('reserved_count')->default(0);
            $table->unsignedInteger('completed_count')->default(0);
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'target_date']);
            $table->index('target_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_daily_slots');
    }
};
