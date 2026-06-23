<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointExchange extends Model
{
    protected $fillable = [
        'user_id', 'points', 'exchange_type', 'status', 'processed_by', 'processed_at',
    ];

    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }

    public function user()        { return $this->belongsTo(User::class); }
    public function processedBy() { return $this->belongsTo(Admin::class, 'processed_by'); }
}
