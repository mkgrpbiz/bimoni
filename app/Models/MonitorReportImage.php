<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorReportImage extends Model
{
    public $timestamps = false;

    protected $fillable = ['monitor_report_id', 'image_path', 'sort_order'];

    public function report() { return $this->belongsTo(MonitorReport::class, 'monitor_report_id'); }
}
