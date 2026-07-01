<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_message_defaults', function (Blueprint $table) {
            $table->id();
            $table->string('pr_media')->unique();
            $table->text('monitor_invite_message')->nullable();
            $table->text('monitor_end_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_message_defaults');
    }
};
