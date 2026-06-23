<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'category_id', 'title', 'campaign_type', 'status', 'pr_media',
        'description', 'requirements', 'notes', 'product_name', 'product_price',
        'cooperation_fee', 'referral_fee', 'campaign_unit_price',
        'initial_purchase_fee', 'recurring_purchase_fee', 'gross_profit',
        'continuation_rate', 'target_gender_ratio', 'capacity',
        'solicitation_target', 'thumbnail', 'application_start_at',
        'application_end_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'application_start_at' => 'date',
            'application_end_at'   => 'date',
        ];
    }

    public function category()     { return $this->belongsTo(Category::class); }
    public function tags()         { return $this->belongsToMany(Tag::class); }
    public function applications() { return $this->hasMany(Application::class); }
    public function createdBy()    { return $this->belongsTo(Admin::class, 'created_by'); }

    public function getTypeLabel(): string
    {
        return match($this->campaign_type) {
            'experience' => '体験モニター',
            'product'    => '商品モニター',
            'recovery'   => '回収サービス',
            default      => $this->campaign_type,
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'draft'     => '下書き',
            'published' => '公開中',
            'closed'    => '終了',
            default     => $this->status,
        };
    }
}
