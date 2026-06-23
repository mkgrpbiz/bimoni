<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    public $timestamps = false;
    public $updatedAt  = false;

    protected $fillable = [
        'user_id', 'type', 'amount', 'reason',
        'application_id', 'settlement_id', 'granted_by', 'imported_from', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user()       { return $this->belongsTo(User::class); }
    public function application(){ return $this->belongsTo(Application::class); }
    public function settlement() { return $this->belongsTo(PointSettlement::class); }
    public function grantedBy()  { return $this->belongsTo(Admin::class, 'granted_by'); }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'earn'     => '獲得',
            'exchange' => '交換',
            'adjust'   => '調整',
            'cancel'   => '取消',
            default    => $this->type,
        };
    }
}
