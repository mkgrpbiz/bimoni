<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->enum('continuation_wish', ['希望', '不可'])->nullable()->after('notes');
            $table->json('purchase_available_times')->nullable()->after('continuation_wish');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['continuation_wish', 'purchase_available_times']);
        });
    }
};
