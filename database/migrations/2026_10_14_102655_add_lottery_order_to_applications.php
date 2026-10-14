<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // 応募の案内順を先着順（applied_at）から完全ランダム（抽選）に変更するための値。
            // 応募作成時に0〜1のランダムな値を1回だけ割り当て、以後はこの値で固定的にソートする
            // （毎回表示をシャッフルすると対応済み/未対応の管理が崩れるため、応募時点で確定させる方式にした）
            $table->double('lottery_order')->nullable()->after('applied_at')->index();
        });

        // 既存の応募にも一括でランダム値を割り当てる
        DB::statement('UPDATE applications SET lottery_order = RAND() WHERE lottery_order IS NULL');
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('lottery_order');
        });
    }
};
