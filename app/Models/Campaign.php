<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'category_id', 'title', 'campaign_type', 'status', 'pr_media',
        'description', 'requirements', 'notes', 'monitor_invite_message', 'monitor_end_message',
        'product_name', 'product_price',
        'cooperation_fee', 'referral_fee', 'campaign_unit_price',
        'initial_purchase_fee', 'recurring_purchase_fee', 'gross_profit',
        'continuation_rate', 'closing_date', 'payment_timing',
        'target_gender_ratio', 'target_male_ratio', 'target_female_ratio',
        'capacity', 'solicitation_target', 'thumbnail',
        'application_start_at', 'application_end_at', 'created_by',
        'sort_order', 'is_visible',
        'application_image_enabled', 'report_image_enabled',
        'application_show_fields',
    ];

    protected function casts(): array
    {
        return [
            'application_start_at'   => 'date',
            'application_end_at'     => 'date',
            'application_show_fields' => 'array',
        ];
    }

    public function category()            { return $this->belongsTo(Category::class); }
    public function tags()                { return $this->belongsToMany(Tag::class); }
    public function applications()        { return $this->hasMany(Application::class); }
    public function dailySlots()          { return $this->hasMany(CampaignDailySlot::class)->orderBy('target_date'); }
    public function createdBy()           { return $this->belongsTo(Admin::class, 'created_by'); }
    public function approvalReflections() { return $this->hasMany(CampaignApprovalReflection::class); }
    public function formFields()          { return $this->belongsToMany(FormField::class, 'campaign_form_fields')->withPivot('sort_order')->orderByPivot('sort_order'); }

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
            'paused'    => '一時停止',
            'closed'    => '終了',
            default     => $this->status,
        };
    }

    public function getPrMediaLabel(): string
    {
        return match($this->pr_media) {
            'AD'      => 'AD',
            'IF'      => 'IF',
            'LINE'    => 'LINE',
            'monitor' => 'モニター',
            default   => $this->pr_media ?? '-',
        };
    }

    // 商品金額 = 初回購入費 + 継続購入費 × 継続率
    public function getProductCostAttribute(): int
    {
        $initial  = (int)($this->initial_purchase_fee ?? 0);
        $cont     = (int)($this->recurring_purchase_fee ?? 0);
        $rate     = (float)($this->continuation_rate ?? 0) / 100;
        return $initial + (int)($cont * $rate);
    }
}
