<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionReport extends Model
{
    // 回収報告の月締め基準を報告日時から承認日時に変更した切替日時（2026-10-01）。
    // 過去に遡って集計月を変えると、既に処理済み（予約済み/支払済み）の月と食い違うため、
    // この日時以降に承認されたものだけ承認日時(reviewed_at)基準にし、それより前に承認済みだった
    // ものは従来通り報告日時(created_at)基準のまま扱う。monthCutoffSql()を必ず経由して判定すること
    public const MONTH_CUTOFF_SWITCH_AT = '2026-10-01 11:11:29';

    public static function monthCutoffSql(): string
    {
        return "CASE WHEN reviewed_at >= '" . self::MONTH_CUTOFF_SWITCH_AT . "' THEN reviewed_at ELSE created_at END";
    }

    protected $fillable = [
        'user_id', 'campaign_ids', 'box_image', 'label_image',
        'tracking_number', 'shipping_fee', 'estimated_arrival_date',
        'item_count', 'other_count', 'cooperation_fee',
        'adjustment_amount', 'adjustment_reason',
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'campaign_ids'           => 'array',
            'estimated_arrival_date' => 'date',
            'reviewed_at'            => 'datetime',
            'paid_at'                => 'datetime',
        ];
    }

    public function user()       { return $this->belongsTo(User::class); }
    public function reviewer()   { return $this->belongsTo(Admin::class, 'reviewed_by'); }

    public function campaigns()
    {
        return Campaign::whereIn('id', $this->campaign_ids ?? [])->get();
    }

    public static function calcFee(int $itemCount, int $shippingFee): int
    {
        $gross = $itemCount * 800;
        return $itemCount >= 5 ? $gross + $shippingFee : $gross;
    }

    public function totalFee(): int
    {
        return $this->cooperation_fee + ($this->adjustment_amount ?? 0);
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending'  => '承認待ち',
            'approved' => '承認',
            'rejected' => '差戻し',
            default    => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending'  => 'bg-yellow-100 text-yellow-700',
            'approved' => 'bg-green-100 text-green-700',
            'rejected' => 'bg-red-100 text-red-700',
            default    => 'bg-gray-100 text-gray-700',
        };
    }
}
