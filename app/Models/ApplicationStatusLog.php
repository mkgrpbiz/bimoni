<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationStatusLog extends Model
{
    protected $fillable = [
        'application_id', 'from_status', 'to_status', 'changed_by', 'memo',
    ];

    public function application() { return $this->belongsTo(Application::class); }
    public function changedBy()   { return $this->belongsTo(Admin::class, 'changed_by'); }
}
