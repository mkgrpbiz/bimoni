<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportFormResponse extends Model
{
    protected $fillable = ['monitor_report_id', 'field_key', 'value'];

    public function monitorReport() { return $this->belongsTo(MonitorReport::class); }
}
