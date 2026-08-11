<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BIMONI管理君LINEのWebhook受信・AI OFFICEへのリレー機能を廃止した
     * （2026-08-11、AI OFFICE側が直接LINE Webhookを受信する新方式へ移行）。
     * このテーブルはBIMONI側だけがリレー用に保持していたローカルコピーで、
     * BIMONI管理君の業務データ本体はAI OFFICE側のkanrikun_messages/
     * kanrikun_casesにあり、そちらは引き続き利用する（このmigrationとは無関係）。
     * BIMONI側では他のどのコード・画面からも参照されていないことを確認済み。
     */
    public function up(): void
    {
        Schema::dropIfExists('kanrikun_messages');
        Schema::dropIfExists('kanrikun_contacts');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('kanrikun_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('line_user_id')->nullable();
            $table->string('display_name')->nullable();
            $table->string('picture_url')->nullable();
            $table->boolean('is_anonymous_group_sender')->default(false);
            $table->string('line_group_id')->nullable();
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamps();

            $table->unique('line_user_id');
            $table->unique('line_group_id');
        });

        Schema::create('kanrikun_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kanrikun_contact_id')->constrained('kanrikun_contacts')->cascadeOnDelete();
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
};
