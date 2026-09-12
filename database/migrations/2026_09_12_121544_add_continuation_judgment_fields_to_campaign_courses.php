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
        Schema::table('campaign_courses', function (Blueprint $table) {
            // 継続前提コースで実際に継続確認（continuation_response）を行い目標継続率を追跡するかどうか。
            // 無の場合は継続購入費2/3欄自体を使わない運用（モニターコスト計算からも除外）
            $table->boolean('continuation_judgment_enabled')->default(false)->after('continuation_count');
            $table->decimal('continuation_rate', 5, 2)->nullable()->after('continuation_judgment_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_courses', function (Blueprint $table) {
            $table->dropColumn(['continuation_judgment_enabled', 'continuation_rate']);
        });
    }
};
