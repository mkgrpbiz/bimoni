<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 応募テーブルに画像
        if (!Schema::hasColumn('applications', 'form_image')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->string('form_image')->nullable()->after('applied_at');
            });
        }
        // 報告テーブルに画像
        if (!Schema::hasColumn('monitor_reports', 'report_image')) {
            Schema::table('monitor_reports', function (Blueprint $table) {
                $table->string('report_image')->nullable()->after('status');
            });
        }
        // 応募フォームに画像有効フラグ
        if (!Schema::hasColumn('campaigns', 'application_image_enabled')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->boolean('application_image_enabled')->default(false)->after('is_visible');
            });
        }
        // 報告フォームに画像有効フラグ
        if (!Schema::hasColumn('campaigns', 'report_image_enabled')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->boolean('report_image_enabled')->default(false)->after('application_image_enabled');
            });
        }
    }

    public function down(): void
    {
        Schema::table('applications', fn($t) => $t->dropColumn('form_image'));
        Schema::table('monitor_reports', fn($t) => $t->dropColumn('report_image'));
        Schema::table('campaigns', fn($t) => $t->dropColumn(['application_image_enabled', 'report_image_enabled']));
    }
};
