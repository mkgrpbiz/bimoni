<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorReport extends Model
{
    protected $fillable = [
        'application_id', 'user_id', 'campaign_id',
        'report_body', 'status', 'reviewed_by', 'reviewed_at', 'reject_reason',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function application() { return $this->belongsTo(Application::class); }
    public function user()        { return $this->belongsTo(User::class); }
    public function campaign()    { return $this->belongsTo(Campaign::class); }
    public function images()      { return $this->hasMany(MonitorReportImage::class)->orderBy('sort_order'); }
    public function reviewedBy()  { return $this->belongsTo(Admin::class, 'reviewed_by'); }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending'  => '審査中',
            'approved' => '承認済',
            'rejected' => '差戻し',
            default    => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending'  => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
            'approved' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
            'rejected' => 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300',
            default    => 'bg-gray-100 text-gray-500',
        };
    }
}
