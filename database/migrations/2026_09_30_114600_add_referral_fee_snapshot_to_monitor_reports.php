<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitor_reports', function (Blueprint $table) {
            // 報告作成/紐付け時点でのcampaign.referral_feeのスナップショット。これが無いと
            // 紹介報酬の計算が常にcampaignの「現在の」referral_feeを参照するため、単価を変更すると
            // 過去に確定・支払い済みの報告の金額まで遡って変わってしまう不具合があった（2026-09-30発覚）
            $table->unsignedInteger('referral_fee')->nullable()->after('campaign_id');
        });

        // 既存の全報告は「今の単価がそのまま当時の単価」なので、現在のcampaign.referral_feeで一括バックフィル
        DB::statement('
            UPDATE monitor_reports mr
            INNER JOIN campaigns c ON c.id = mr.campaign_id
            SET mr.referral_fee = c.referral_fee
            WHERE mr.referral_fee IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('monitor_reports', function (Blueprint $table) {
            $table->dropColumn('referral_fee');
        });
    }
};
