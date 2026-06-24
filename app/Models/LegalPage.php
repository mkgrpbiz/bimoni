<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalPage extends Model
{
    protected $fillable = ['slug', 'title', 'content'];

    public static function terms(): self
    {
        return static::firstOrCreate(['slug' => 'terms'], ['title' => '利用規約']);
    }

    public static function privacy(): self
    {
        return static::firstOrCreate(['slug' => 'privacy'], ['title' => 'プライバシーポリシー']);
    }
}
