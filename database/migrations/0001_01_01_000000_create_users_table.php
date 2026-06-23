<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('line_user_id')->unique();
            $table->string('erme_respondent_id')->nullable()->index();
            $table->string('name', 100)->nullable();
            $table->string('name_kana', 100)->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('birthdate')->nullable();
            $table->string('area', 100)->nullable();
            $table->json('available_times')->nullable();
            $table->tinyInteger('wants_continuation')->nullable();
            $table->integer('point_balance')->default(0);
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamp('profile_completed_at')->nullable();
            $table->enum('imported_from', ['new', 'spreadsheet', 'erme'])->default('new');
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
