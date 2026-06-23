<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earn', 'exchange', 'adjust', 'cancel']);
            $table->integer('amount');
            $table->string('reason')->nullable();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('settlement_id')->nullable()->constrained('point_settlements')->nullOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->enum('imported_from', ['new', 'spreadsheet'])->default('new');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points');
    }
};
