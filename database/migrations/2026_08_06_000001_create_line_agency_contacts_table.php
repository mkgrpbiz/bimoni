<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_agency_contacts', function (Blueprint $table) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('line_agency_contacts');
    }
};
