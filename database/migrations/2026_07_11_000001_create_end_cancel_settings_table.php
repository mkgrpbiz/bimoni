<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('end_cancel_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('send_start_hour')->default(9);
            $table->unsignedTinyInteger('send_end_hour')->default(22);
            $table->text('message_template')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('end_cancel_settings');
    }
};
