<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::firstOrCreate(
            ['email' => 'admin@bimoni.jp'],
            [
                'name'     => 'BIMONI管理者',
                'password' => Hash::make('password'),
            ]
        );
    }
}
