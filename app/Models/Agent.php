<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'access_token',
        'child_reward_500', 'child_reward_1000',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $agent) {
            if (empty($agent->access_token)) {
                do {
                    $token = \Illuminate\Support\Str::random(48);
                } while (static::where('access_token', $token)->exists());
                $agent->access_token = $token;
            }
        });
    }

    public function parent()   { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function codes()    { return $this->hasMany(AgentReferralCode::class); }

    public function isParent(): bool { return is_null($this->parent_id); }

    /** このエージェントが管理するコード文字列の配列 */
    public function getCodeStrings(): array
    {
        return $this->codes->pluck('code')->toArray();
    }

    /** 子を含む全コード文字列（親エージェント用） */
    public function getAllCodeStrings(): array
    {
        $codes = $this->getCodeStrings();
        foreach ($this->children as $child) {
            $codes = array_merge($codes, $child->getCodeStrings());
        }
        return $codes;
    }

    /** キャンペーンのreferral_feeに対して自分が受け取る報酬 */
    public function rewardFor(int $campaignFee): int
    {
        return match($campaignFee) {
            500  => 500,
            1000 => 1000,
            default => 0,
        };
    }

    /** 子エージェントが受け取る報酬（親が設定） */
    public function childRewardFor(int $campaignFee): int
    {
        return match($campaignFee) {
            500  => (int) $this->child_reward_500,
            1000 => (int) $this->child_reward_1000,
            default => 0,
        };
    }

    public function portalUrl(): string
    {
        return route('portal.login', $this->access_token);
    }
}
