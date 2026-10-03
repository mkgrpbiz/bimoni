<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // 初回解約時に容器を返送してもらう案件向け。返送費用は初回解約（＝継続しなかった）場合にのみ
            // 発生するコストなので、粗利計算では (1 - 目標継続率) を掛けた期待値として扱う
            $table->boolean('has_return_fee')->default(false)->after('continuation_rate');
            $table->string('return_method')->nullable()->after('has_return_fee');
            $table->unsignedInteger('return_fee')->nullable()->after('return_method');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['has_return_fee', 'return_method', 'return_fee']);
        });
    }
};
