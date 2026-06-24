<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            if (!Schema::hasColumn('form_fields', 'form_type')) {
                $table->enum('form_type', ['registration', 'application', 'report'])
                    ->default('registration')
                    ->after('id');
            }
            if (!Schema::hasColumn('form_fields', 'description')) {
                $table->text('description')->nullable()->after('label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn(['form_type', 'description']);
        });
    }
};
