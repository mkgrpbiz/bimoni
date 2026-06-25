<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // campaign_typeをVARCHARに（ENUMの場合prが追加できないため）
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN campaign_type VARCHAR(30) NOT NULL DEFAULT 'experience'");

        Schema::table('campaigns', function (Blueprint $table) {
            $table->text('cancellation_info')->nullable()->after('notes');
            $table->text('monitor_guide')->nullable()->after('cancellation_info');
            $table->string('link', 500)->nullable()->after('monitor_guide');
            $table->string('monitor_video')->nullable()->after('link');
            $table->integer('capacity')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['cancellation_info', 'monitor_guide', 'link', 'monitor_video']);
            $table->integer('capacity')->default(1)->change();
        });
    }
};
