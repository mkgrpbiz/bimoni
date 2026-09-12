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
            // 単発+継続判定有のコースは継続前提より多い最大4回まで継続購入費を追跡できるようにする
            $table->unsignedInteger('continuation_fee_4')->nullable()->after('continuation_fee_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_courses', function (Blueprint $table) {
            $table->dropColumn('continuation_fee_4');
        });
    }
};
