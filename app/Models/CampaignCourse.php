<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignCourse extends Model
{
    protected $fillable = [
        'campaign_id', 'name', 'initial_purchase_fee', 'course_type',
        'continuation_count', 'continuation_fee_2', 'continuation_fee_3',
        'percentage', 'invite_message', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
        ];
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'course_id');
    }

    // 単発=初回購入費のみ、継続=初回購入費+継続購入費2（3回の場合はさらに+継続購入費3）
    public function cost(): float
    {
        if ($this->course_type === '継続') {
            $cost = ($this->initial_purchase_fee ?? 0) + ($this->continuation_fee_2 ?? 0);
            if ((int) $this->continuation_count === 3) {
                $cost += ($this->continuation_fee_3 ?? 0);
            }
            return (float) $cost;
        }
        return (float) ($this->initial_purchase_fee ?? 0);
    }
}
