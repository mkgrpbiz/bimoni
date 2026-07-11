<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('hero_image')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('guide_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_page_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('intro_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('guide_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_section_id')->constrained()->cascadeOnDelete();
            $table->string('heading')->nullable();
            $table->text('body');
            $table->string('style')->default('normal'); // normal | warning
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('guide_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_section_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('sub_text')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_steps');
        Schema::dropIfExists('guide_notes');
        Schema::dropIfExists('guide_sections');
        Schema::dropIfExists('guide_pages');
    }
};
