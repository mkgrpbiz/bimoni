<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // 通常打診が無回答のまま期限切れになった連続回数。1回目はpendingに戻して再抽選、
            // 2回連続になったらcancelledにする（AutoCancelExpiredProposals / ProposalController::confirm()で使用）
            $table->unsignedTinyInteger('unanswered_proposal_count')->default(0)->after('lottery_order');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('unanswered_proposal_count');
        });
    }
};
