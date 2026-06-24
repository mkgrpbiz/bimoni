<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_approval_reflections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->unsignedInteger('reflection_count')->default(0);
            $table->boolean('is_all_denied')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['campaign_id', 'period_year', 'period_month'], 'car_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_approval_reflections');
    }
};
