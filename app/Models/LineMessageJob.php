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
            'pending'  => 'bg-yellow-100 text-yellow-700',
            'sent'     => 'bg-green-100 text-green-700',
            'failed'   => 'bg-red-100 text-red-600',
            'canceled' => 'bg-gray-100 text-gray-400',
            default    => 'bg-gray-100 text-gray-400',
        };
    }
}
