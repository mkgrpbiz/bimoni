<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CampaignBonus extends Model
{
    protected $fillable = ['campaign_id', 'bonus_amount', 'start_at', 'end_at'];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at'   => 'datetime',
        ];
    }

    public function campaign() { return $this->belongsTo(Campaign::class); }

    public function isActive(): bool
    {
        $now = Carbon::now();
        return $now->gte($this->start_at) && $now->lte($this->end_at);
    }

    public function applicationsCount(): int
    {
        return Application::where('campaign_id', $this->campaign_id)
            ->whereNotNull('bonus_amount')
            ->whereBetween('applied_at', [$this->start_at, $this->end_at])
            ->count();
    }
}
