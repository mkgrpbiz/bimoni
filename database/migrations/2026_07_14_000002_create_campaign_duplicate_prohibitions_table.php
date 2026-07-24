<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_duplicate_prohibitions', function (Blueprint $table) {
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('duplicate_campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->primary(['campaign_id', 'duplicate_campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_duplicate_prohibitions');
    }
};
