<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserReferralSetting extends Model
{
    protected $fillable = ['enabled', 'reward_amount'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'enabled'       => true,
            'reward_amount' => 1000,
        ]);
    }
}
