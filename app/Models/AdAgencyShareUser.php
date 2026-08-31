<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class AdAgencyShareUser extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'       => 'hashed',
            'last_login_at'  => 'datetime',
        ];
    }
}
