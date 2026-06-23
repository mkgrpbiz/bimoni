<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->index('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_logs');
    }
};
