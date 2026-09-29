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

    // 「campaign_id-period_year-period_month」→true のマップ。is_all_denied は月ごとの実績フラグなので、
    // campaign_idだけで判定すると他の月の全否認扱いが漏れて波及するバグになる（2026-09-29発覚・修正）。
    // 必ずreportIsAllDenied()経由で報告自身のcreated_atの年月と突き合わせて判定すること
    public static function allDeniedMap(): \Illuminate\Support\Collection
    {
        return static::where('is_all_denied', true)
            ->get(['campaign_id', 'period_year', 'period_month'])
            ->mapWithKeys(fn($r) => ["{$r->campaign_id}-{$r->period_year}-{$r->period_month}" => true]);
    }

    // MonitorReport 1件が「その報告が発生した月」において全否認扱いかどうかを判定する
    public static function reportIsAllDenied(\Illuminate\Support\Collection $allDeniedMap, \App\Models\MonitorReport $report): bool
    {
        if (!$report->created_at) return false;
        $key = "{$report->campaign_id}-{$report->created_at->year}-{$report->created_at->month}";
        return $allDeniedMap->has($key);
    }
}
