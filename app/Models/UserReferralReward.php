<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserReferralReward extends Model
{
    protected $fillable = [
        'referrer_user_id', 'referred_user_id', 'monitor_report_id',
        'amount', 'payment_status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function referrer()     { return $this->belongsTo(User::class, 'referrer_user_id'); }
    public function referredUser() { return $this->belongsTo(User::class, 'referred_user_id'); }
    public function monitorReport() { return $this->belongsTo(MonitorReport::class); }
}
