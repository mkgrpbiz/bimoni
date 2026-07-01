<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineMessageDefault extends Model
{
    protected $fillable = ['pr_media', 'monitor_invite_message', 'monitor_end_message'];
}
