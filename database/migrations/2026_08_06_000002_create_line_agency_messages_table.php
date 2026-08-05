<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_agency_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_agency_contact_id')->constrained()->cascadeOnDelete();
            $table->string('line_message_id')->unique();
            $table->string('line_event_id')->nullable();
            $table->enum('source_type', ['user', 'group', 'room']);
            $table->string('line_group_id')->nullable();
            $table->string('line_group_name')->nullable();
            $table->enum('message_type', ['text', 'image', 'file', 'sticker', 'video', 'audio', 'location', 'unsupported']);
            $table->longText('text_body')->nullable();
            $table->string('sticker_package_id')->nullable();
            $table->string('sticker_id')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->timestamp('line_sent_at')->useCurrent();
            $table->json('raw_payload');
            $table->timestamp('relayed_to_ai_office_at')->nullable();
            $table->unsignedTinyInteger('relay_attempts')->default(0);
            $table->text('relay_last_error')->nullable();
            $table->timestamps();

            $table->index(['relayed_to_ai_office_at', 'relay_attempts'], 'line_agency_messages_relay_idx');
            $table->index('line_sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_agency_messages');
    }
};
