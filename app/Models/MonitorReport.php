<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorReport extends Model
{
    protected $fillable = [
        'application_id', 'user_id', 'campaign_id',
        'report_body', 'purchase_type', 'purchase_amount', 'bonus_amount', 'payment_method', 'payment_method_other',
        'status', 'reviewed_by', 'reviewed_at', 'reject_reason',
        'payment_status', 'paid_at',
        'report_image',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'paid_at'     => 'datetime',
        ];
    }

    public function application() { return $this->belongsTo(Application::class); }
    public function user()        { return $this->belongsTo(User::class); }
    public function campaign()    { return $this->belongsTo(Campaign::class); }
    public function images()      { return $this->hasMany(MonitorReportImage::class)->orderBy('sort_order'); }
    public function reviewedBy()  { return $this->belongsTo(Admin::class, 'reviewed_by'); }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending'  => '承認待ち',
            'approved' => '承認',
            'rejected' => '差戻し',
            default    => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending'  => 'bg-yellow-500 text-white',
            'approved' => 'bg-green-500 text-white',
            'rejected' => 'bg-red-500 text-white',
            default    => 'bg-gray-500 text-white',
        };
    }
}
