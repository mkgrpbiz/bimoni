<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_form_fields', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('form_field_id');
            $table->integer('sort_order')->default(0);
            $table->primary(['campaign_id', 'form_field_id']);
            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
            $table->foreign('form_field_id')->references('id')->on('form_fields')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_form_fields');
    }
};
