<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineMessageJob extends Model
{
    protected $fillable = [
        'application_id', 'user_id', 'campaign_id', 'line_user_id',
        'send_type', 'message_body', 'send_at', 'sent_at',
        'status', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'send_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function application() { return $this->belongsTo(Application::class); }
    public function user()        { return $this->belongsTo(User::class); }
    public function campaign()    { return $this->belongsTo(Campaign::class); }

    public function getSendTypeLabel(): string
    {
        return match($this->send_type) {
            'proposal'       => '打診',
            'monitor_guide'  => '案内文',
            'reminder'       => 'リマインド',
            'report_request' => '報告依頼',
            default          => $this->send_type,
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending'  => '送信待ち',
            'sent'     => '送信済み',
            'failed'   => '失敗',
            'canceled' => 'キャンセル',
            default    => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending'  => 'bg-yellow-500 text-white',
            'sent'     => 'bg-green-500 text-white',
            'failed'   => 'bg-red-500 text-white',
            'canceled' => 'bg-gray-500 text-white',
            default    => 'bg-gray-500 text-white',
        };
    }
}
