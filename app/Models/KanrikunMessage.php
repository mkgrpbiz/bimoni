<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanrikunMessage extends Model
{
    protected $fillable = [
        'kanrikun_contact_id', 'line_message_id', 'line_event_id', 'source_type',
        'line_group_id', 'line_group_name', 'message_type', 'text_body',
        'sticker_package_id', 'sticker_id', 'file_name', 'file_size',
        'attachment_path', 'attachment_mime', 'line_sent_at', 'raw_payload',
        'relayed_to_ai_office_at', 'relay_attempts', 'relay_last_error',
    ];

    protected function casts(): array
    {
        return [
            'line_sent_at' => 'datetime',
            'raw_payload' => 'array',
            'relayed_to_ai_office_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(KanrikunContact::class, 'kanrikun_contact_id');
    }

    public function attachmentUrl(): ?string
    {
        return $this->attachment_path ? url('storage/'.$this->attachment_path) : null;
    }
}
