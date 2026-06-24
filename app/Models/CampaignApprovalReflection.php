<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignApprovalReflection extends Model
{
    protected $fillable = [
        'campaign_id', 'period_year', 'period_month',
        'reflection_count', 'is_all_denied', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_all_denied' => 'boolean'];
    }

    public function campaign() { return $this->belongsTo(Campaign::class); }
    public function updatedBy() { return $this->belongsTo(Admin::class, 'updated_by'); }
}
