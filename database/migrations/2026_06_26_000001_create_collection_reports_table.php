<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('campaign_ids');          // 対象案件ID配列
            $table->string('box_image');           // 段ボール写真
            $table->string('label_image');         // 発送伝票写真
            $table->string('tracking_number', 50);
            $table->unsignedInteger('shipping_fee')->default(0);
            $table->date('estimated_arrival_date');
            $table->unsignedInteger('item_count')->default(1);
            $table->integer('cooperation_fee')->default(0); // item_count*800 - (<=5 ? shipping_fee : 0)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_reports');
    }
};
