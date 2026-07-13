<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE campaign_courses CHANGE amount initial_purchase_fee INT UNSIGNED NOT NULL DEFAULT 0");

        DB::statement("UPDATE campaign_courses SET course_type = '継続' WHERE course_type = '定期'");
        DB::statement("ALTER TABLE campaign_courses MODIFY COLUMN course_type ENUM('単発', '継続') NOT NULL DEFAULT '単発'");

        Schema::table('campaign_courses', function (Blueprint $table) {
            $table->unsignedTinyInteger('continuation_count')->nullable()->after('course_type');
            $table->unsignedInteger('continuation_fee_2')->nullable()->after('continuation_count');
            $table->unsignedInteger('continuation_fee_3')->nullable()->after('continuation_fee_2');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_courses', function (Blueprint $table) {
            $table->dropColumn(['continuation_count', 'continuation_fee_2', 'continuation_fee_3']);
        });

        DB::statement("UPDATE campaign_courses SET course_type = '定期' WHERE course_type = '継続'");
        DB::statement("ALTER TABLE campaign_courses MODIFY COLUMN course_type ENUM('定期', '単発') NOT NULL DEFAULT '単発'");

        DB::statement("ALTER TABLE campaign_courses CHANGE initial_purchase_fee amount INT UNSIGNED NOT NULL DEFAULT 0");
    }
};
