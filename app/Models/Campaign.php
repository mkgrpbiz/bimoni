<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'category_id', 'title', 'campaign_type', 'status', 'pr_media',
        'description', 'requirements', 'notes',
        'cancellation_info',
        'cancellation_method', 'cancellation_phone', 'cancellation_hours',
        'cancellation_mypage_url', 'cancellation_email', 'cancellation_visible',
        'monitor_guide', 'link', 'monitor_video', 'monitor_video_thumbnail',
        'monitor_invite_message', 'monitor_end_message',
        'product_name', 'product_price',
        'cooperation_fee', 'cooperation_fee_formula',
        'continuation_cooperation_fee', 'continuation_cooperation_fee_formula',
        'continuation_condition',
        'referral_fee', 'campaign_unit_price',
        'initial_purchase_fee', 'recurring_purchase_fee', 'gross_profit',
        'continuation_rate', 'closing_date', 'payment_timing',
        'collection_info',
        'collection_requirement', 'collection_count_judgment',
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
            'cancellation_visible'    => 'boolean',
        ];
    }

    public function category()            { return $this->belongsTo(Category::class); }
    public function tags()                { return $this->belongsToMany(Tag::class); }
    public function applications()        { return $this->hasMany(Application::class); }
    public function dailySlots()          { return $this->hasMany(CampaignDailySlot::class)->orderBy('target_date'); }
    public function createdBy()           { return $this->belongsTo(Admin::class, 'created_by'); }
    public function approvalReflections() { return $this->hasMany(CampaignApprovalReflection::class); }
    public function bonuses()             { return $this->hasMany(CampaignBonus::class); }
    public function activeBonus()         { return $this->bonuses()->where('start_at', '<=', now())->where('end_at', '>=', now())->latest('start_at'); }
    public function formFields()          { return $this->belongsToMany(FormField::class, 'campaign_form_fields')->withPivot('sort_order')->orderByPivot('sort_order'); }

    public function getTypeLabel(): string
    {
        return match($this->campaign_type) {
            'experience' => '体験モニター',
            'product'    => '商品モニター',
            'pr'         => 'PRモニター',
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

    // 「解約方法管理」機能（案件編集とは別画面）で使う項目が1つでも入力されているか
    public function hasCancellationInfo(): bool
    {
        return filled($this->cancellation_method)
            || filled($this->cancellation_phone)
            || filled($this->cancellation_hours)
            || filled($this->cancellation_mypage_url)
            || filled($this->cancellation_email);
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

    // メッセージテンプレートの{{変数}}を実際の値に置換
    public function resolveTemplate(string $template): string
    {
        return str_replace(
            ['{{商品名}}', '{{初回購入費}}', '{{モニター協力金}}', '{{解約について}}', '{{モニター案内文}}', '{{リンク}}'],
            [
                $this->title ?? '',
                $this->initial_purchase_fee ? number_format($this->initial_purchase_fee) . '円' : '',
                number_format($this->cooperation_fee) . '円',
                $this->cancellation_info ?? '',
                $this->monitor_guide ?? '',
                $this->link ?? '',
            ],
            $template
        );
    }

    // フォーミュラから数値合計を返す（"3000+500" → 3500, "500" → 500）
    public static function parseCooperationFormula(string $formula): int
    {
        if (str_contains($formula, '+')) {
            return array_sum(array_map('intval', explode('+', $formula)));
        }
        return (int) $formula;
    }

    // 協力金の "extra" 部分（フォーミュラの + 以降）を返す
    public function getCooperationExtraAttribute(): ?int
    {
        if (!$this->cooperation_fee_formula || !str_contains($this->cooperation_fee_formula, '+')) {
            return null;
        }
        $parts = explode('+', $this->cooperation_fee_formula);
        return (int) trim(end($parts));
    }

    public function getContinuationCooperationExtraAttribute(): ?int
    {
        if (!$this->continuation_cooperation_fee_formula || !str_contains($this->continuation_cooperation_fee_formula, '+')) {
            return null;
        }
        $parts = explode('+', $this->continuation_cooperation_fee_formula);
        return (int) trim(end($parts));
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
