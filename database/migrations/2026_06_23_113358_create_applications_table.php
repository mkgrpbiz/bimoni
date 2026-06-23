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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->enum('status', [
                'pending', 'selected', 'rejected', 'line_contacted',
                'scheduled', 'completed', 'reported', 'approved',
                'point_granted', 'cancelled',
            ])->default('pending');
            $table->enum('line_contact_status', ['not_sent', 'sent', 'confirmed'])->default('not_sent');
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('selected_at')->nullable();
            $table->timestamp('line_contacted_at')->nullable();
            $table->timestamp('schedule_confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->enum('imported_from', ['new', 'spreadsheet'])->default('new');
            $table->timestamps();

            $table->unique(['user_id', 'campaign_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
