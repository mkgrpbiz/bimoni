<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')
            ->whereNotNull('name')
            ->get(['id', 'name', 'name_kana']);

        foreach ($users as $user) {
            $updates = [];

            $newName = preg_replace('/[\s\x{3000}]+/u', '', $user->name ?? '');
            if ($newName !== ($user->name ?? '')) {
                $updates['name'] = $newName;
            }

            $newKana = preg_replace('/[\s\x{3000}]+/u', '', $user->name_kana ?? '');
            if ($newKana !== ($user->name_kana ?? '')) {
                $updates['name_kana'] = $newKana ?: null;
            }

            if ($updates) {
                DB::table('users')->where('id', $user->id)->update($updates);
            }
        }
    }

    public function down(): void {}
};
