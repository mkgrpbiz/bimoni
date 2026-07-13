<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignCourse extends Model
{
    protected $fillable = [
        'campaign_id', 'name', 'amount', 'course_type', 'percentage', 'invite_message', 'sort_order',
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

    // 単発=金額そのまま、定期=金額×継続率
    public function cost(float $continuationRate): float
    {
        return $this->course_type === '定期'
            ? $this->amount * ($continuationRate / 100)
            : (float) $this->amount;
    }
}
