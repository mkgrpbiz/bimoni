<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationFormResponse extends Model
{
    protected $fillable = ['application_id', 'field_key', 'value'];

    public function application() { return $this->belongsTo(Application::class); }
}
