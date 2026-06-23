<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_message_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('line_user_id')->nullable();
            $table->enum('send_type', ['proposal', 'monitor_guide', 'reminder', 'report_request']);
            $table->text('message_body');
            $table->dateTime('send_at');
            $table->dateTime('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'canceled'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'send_at']);
            $table->index('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_message_jobs');
    }
};
