<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'application_id', 'notification_type', 'message', 'status', 'sent_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function user()        { return $this->belongsTo(User::class); }
    public function application() { return $this->belongsTo(Application::class); }
}
