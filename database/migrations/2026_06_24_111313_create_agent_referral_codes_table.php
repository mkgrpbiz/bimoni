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
        Schema::create('agent_referral_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('code', 10)->unique();
            $table->string('label')->nullable();
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('agents')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_referral_codes');
    }
};
