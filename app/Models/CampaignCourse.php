<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignCourse extends Model
{
    protected $fillable = [
        'campaign_id', 'name', 'initial_purchase_fee', 'course_type',
        'continuation_count', 'continuation_judgment_enabled', 'continuation_rate',
        'continuation_fee_2', 'continuation_fee_3', 'continuation_fee_4',
        'percentage', 'invite_message', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'continuation_rate' => 'decimal:2',
            'continuation_judgment_enabled' => 'boolean',
        ];
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    // コース専用コード（{{コース名N}} {{初回購入費N}} {{継続購入費N-2}} {{継続購入費N-3}} {{継続購入費N-4}}、
    // N=このコースの並び順+2）を置換したあと、案件共通コードも解決する。他コースの値と混同しないようコースごとに番号が変わる
    public function resolveTemplate(string $template): string
    {
        $n = $this->sort_order + 2;

        $template = str_replace(
            ["{{コース名{$n}}}", "{{初回購入費{$n}}}", "{{継続購入費{$n}-2}}", "{{継続購入費{$n}-3}}", "{{継続購入費{$n}-4}}"],
            [
                $this->name ?? '',
                $this->initial_purchase_fee ? number_format($this->initial_purchase_fee) . '円' : '',
                $this->continuation_fee_2 ? number_format($this->continuation_fee_2) . '円' : '',
                $this->continuation_fee_3 ? number_format($this->continuation_fee_3) . '円' : '',
                $this->continuation_fee_4 ? number_format($this->continuation_fee_4) . '円' : '',
            ],
            $template
        );

        return $this->campaign->resolveTemplate($template);
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'course_id');
    }

    // 単発=初回購入費のみ、継続前提（継続確定）=初回購入費+継続購入費2（3回前提の場合はさらに+継続購入費3）。
    // 単発+継続判定有（継続するか未確定・確認する）は、継続購入費の合計に目標継続率を掛けた
    // 期待値を初回購入費に加算する（外側の加重平均で目標%が掛かるのと合わせて「コース利用率×継続率」の
    // 複合確率になる。1000円+継続購入費500円・目標継続率50%の例なら 1000 + 500×0.5 = 1250円）
    public function cost(): float
    {
        if ($this->course_type === '継続前提') {
            $cost = ($this->initial_purchase_fee ?? 0) + ($this->continuation_fee_2 ?? 0);
            if ((int) $this->continuation_count === 3) {
                $cost += ($this->continuation_fee_3 ?? 0);
            }
            return (float) $cost;
        }

        if ($this->continuation_judgment_enabled) {
            $continuationFees = ($this->continuation_fee_2 ?? 0);
            if ((int) $this->continuation_count >= 3) {
                $continuationFees += ($this->continuation_fee_3 ?? 0);
            }
            if ((int) $this->continuation_count >= 4) {
                $continuationFees += ($this->continuation_fee_4 ?? 0);
            }
            $rate = ($this->continuation_rate ?? 0) / 100;
            return (float) (($this->initial_purchase_fee ?? 0) + $continuationFees * $rate);
        }

        return (float) ($this->initial_purchase_fee ?? 0);
    }
}
