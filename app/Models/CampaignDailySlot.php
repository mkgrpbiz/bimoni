<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignDailySlot extends Model
{
    protected $fillable = [
        'campaign_id', 'target_date', 'planned_count',
        'invited_count', 'reserved_count', 'completed_count', 'memo',
    ];

    protected function casts(): array
    {
        return ['target_date' => 'date'];
    }

    public function campaign() { return $this->belongsTo(Campaign::class); }
}
