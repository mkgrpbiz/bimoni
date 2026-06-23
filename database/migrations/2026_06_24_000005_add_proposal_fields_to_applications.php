<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('proposal_token', 64)->unique()->nullable()->after('continuation_invite_date');
            $table->timestamp('proposal_answered_at')->nullable()->after('proposal_token');
            $table->enum('proposal_answer', ['yes', 'no'])->nullable()->after('proposal_answered_at');
            $table->timestamp('proposal_sent_at')->nullable()->after('proposal_answer');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['proposal_token', 'proposal_answered_at', 'proposal_answer', 'proposal_sent_at']);
        });
    }
};
