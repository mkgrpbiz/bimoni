<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->enum('collection_requirement', ['回収前提', '回収不要'])->nullable()->after('collection_info');
            $table->unsignedTinyInteger('collection_count_judgment')->nullable()->after('collection_requirement');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['collection_requirement', 'collection_count_judgment']);
        });
    }
};
