<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique(); // 'terms', 'privacy'
            $table->string('title', 100);
            $table->longText('content')->nullable();
            $table->timestamps();
        });

        // デフォルトレコードを挿入
        DB::table('legal_pages')->insert([
            ['slug' => 'terms',   'title' => '利用規約',             'content' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'privacy', 'title' => 'プライバシーポリシー', 'content' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_pages');
    }
};
