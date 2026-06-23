<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->enum('campaign_type', ['experience', 'product', 'recovery']);
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->string('pr_media')->nullable();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->text('notes')->nullable();
            $table->string('product_name')->nullable();
            $table->unsignedInteger('product_price')->nullable();
            $table->unsignedInteger('cooperation_fee')->default(0);
            $table->unsignedInteger('referral_fee')->default(0);
            $table->unsignedInteger('campaign_unit_price')->nullable();
            $table->unsignedInteger('initial_purchase_fee')->nullable();
            $table->unsignedInteger('recurring_purchase_fee')->nullable();
            $table->integer('gross_profit')->nullable();
            $table->decimal('continuation_rate', 5, 2)->nullable();
            $table->string('target_gender_ratio', 50)->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->unsignedInteger('solicitation_target')->nullable();
            $table->string('thumbnail')->nullable();
            $table->date('application_start_at')->nullable();
            $table->date('application_end_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('campaign_type');
            $table->index('application_end_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
