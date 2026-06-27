<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')->orderBy('id')->pluck('id');

        foreach ($users as $i => $id) {
            $newId = 'BMN' . str_pad(10001 + $i, 6, '0', STR_PAD_LEFT);
            DB::table('users')->where('id', $id)->update(['bimoni_user_id' => $newId]);
        }
    }

    public function down(): void
    {
        // 元の採番に戻す処理は省略（手動対応）
    }
};
