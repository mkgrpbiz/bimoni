<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 応募フォーム回答
        Schema::create('application_form_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('field_key', 100);
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['application_id', 'field_key']);
            $table->foreign('application_id')->references('id')->on('applications')->cascadeOnDelete();
        });

        // 報告フォーム回答
        Schema::create('report_form_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monitor_report_id');
            $table->string('field_key', 100);
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['monitor_report_id', 'field_key']);
            $table->foreign('monitor_report_id')->references('id')->on('monitor_reports')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_form_responses');
        Schema::dropIfExists('application_form_responses');
    }
};
