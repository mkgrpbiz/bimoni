<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'referral_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('referral_code', 10)->nullable()->unique()->after('bimoni_user_id');
                $table->string('referred_by_code', 10)->nullable()->after('referral_code');
                $table->index('referred_by_code');
            });
        }

        // 紹介コード未設定のユーザーに付与
        $users = DB::table('users')->whereNull('referral_code')->orderBy('id')->pluck('id');
        foreach ($users as $userId) {
            do {
                $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
            } while (DB::table('users')->where('referral_code', $code)->exists());
            DB::table('users')->where('id', $userId)->update(['referral_code' => $code]);
        }

        if (!Schema::hasColumn('campaigns', 'referral_fee')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->unsignedInteger('referral_fee')->default(0)->after('cooperation_fee');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['referred_by_code']);
            $table->dropColumn(['referral_code', 'referred_by_code']);
        });
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('referral_fee');
        });
    }
};
