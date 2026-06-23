<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointSettlement extends Model
{
    protected $fillable = [
        'settlement_month', 'payment_due_date', 'status', 'total_amount', 'closed_by', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'settlement_month' => 'date',
            'payment_due_date' => 'date',
            'closed_at'        => 'datetime',
        ];
    }

    public function points()   { return $this->hasMany(Point::class, 'settlement_id'); }
    public function closedBy() { return $this->belongsTo(Admin::class, 'closed_by'); }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'open'   => '未締め',
            'closed' => '締め済み',
            'paid'   => '支払済み',
            default  => $this->status,
        };
    }
}
