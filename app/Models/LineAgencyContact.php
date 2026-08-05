<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineAgencyContact extends Model
{
    protected $fillable = [
        'line_user_id', 'display_name', 'picture_url',
        'is_anonymous_group_sender', 'line_group_id',
        'first_seen_at', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_anonymous_group_sender' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LineAgencyMessage::class);
    }
}
