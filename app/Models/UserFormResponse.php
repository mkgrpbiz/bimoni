<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFormResponse extends Model
{
    protected $fillable = ['user_id', 'field_key', 'value'];

    public function user() { return $this->belongsTo(User::class); }
}
