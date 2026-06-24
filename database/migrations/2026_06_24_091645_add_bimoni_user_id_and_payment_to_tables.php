<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // users に bimoni_user_id 追加
        Schema::table('users', function (Blueprint $table) {
            $table->string('bimoni_user_id', 15)->nullable()->unique()->after('id');
        });

        // 既存ユーザーに登録順で BMN00100001〜 を付与
        $users = DB::table('users')->orderBy('id')->pluck('id');
        foreach ($users as $i => $userId) {
            $seq = str_pad(1000001 + $i, 8, '0', STR_PAD_LEFT);
            DB::table('users')->where('id', $userId)->update([
                'bimoni_user_id' => 'BMN' . $seq,
            ]);
        }

        // monitor_reports に payment_status / paid_at 追加
        Schema::table('monitor_reports', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'paid'])->default('pending')->after('status');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bimoni_user_id');
        });
        Schema::table('monitor_reports', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'paid_at']);
        });
    }
};
