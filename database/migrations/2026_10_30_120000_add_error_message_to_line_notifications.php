<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('line_notifications', function (Blueprint $table) {
            // LINE APIから返ってきた実際のエラー内容（quota超過・トークン不正等の原因特定用）
            $table->text('error_message')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('line_notifications', function (Blueprint $table) {
            $table->dropColumn('error_message');
        });
    }
};
