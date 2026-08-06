<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 新規LINE公式アカウントの名称が「BIMONI管理君」に確定したため、
 * "BIMONI"という既存の業務システム名と紛らわしい"line_agency"命名を
 * "kanrikun"に整理する（テーブルはまだ実データが無いため安全にリネーム可能）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('line_agency_contacts', 'kanrikun_contacts');
        Schema::rename('line_agency_messages', 'kanrikun_messages');

        Schema::table('kanrikun_messages', function (Blueprint $table) {
            $table->renameColumn('line_agency_contact_id', 'kanrikun_contact_id');
        });
    }

    public function down(): void
    {
        Schema::table('kanrikun_messages', function (Blueprint $table) {
            $table->renameColumn('kanrikun_contact_id', 'line_agency_contact_id');
        });

        Schema::rename('kanrikun_messages', 'line_agency_messages');
        Schema::rename('kanrikun_contacts', 'line_agency_contacts');
    }
};
