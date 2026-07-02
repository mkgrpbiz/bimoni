<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\MonitorReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PortalService
{
    public static function agent(): Agent
    {
        return Agent::with(['codes', 'children.codes', 'parent'])
            ->findOrFail(session('portal_agent_id'));
    }

    /** エージェントが管理するコード文字列（子含む場合あり） */
    public static function codes(Agent $agent, bool $includeChildren = true): array
    {
        $codes = $agent->codes->pluck('code')->toArray();
        if ($includeChildren && !$agent->parent_id) {
            foreach ($agent->children as $child) {
                $codes = array_merge($codes, $child->codes->pluck('code')->toArray());
            }
        }
        return $codes;
    }

    /** コードに紐づくユーザーを取得 */
    public static function users(array $codes): Collection
    {
        return User::whereIn('referred_by_code', $codes)->whereNotNull('profile_completed_at')->orderByDesc('created_at')->get();
    }

    /** 承認済み報告を取得 */
    public static function approvedReports(array $codes, ?Carbon $month = null): Collection
    {
        $userIds = User::whereIn('referred_by_code', $codes)->pluck('id');

        $query = MonitorReport::with(['user:id,name,name_kana,referred_by_code', 'campaign:id,title,cooperation_fee,referral_fee'])
            ->whereIn('user_id', $userIds)
            ->where('status', 'approved');

        if ($month) {
            $query->whereBetween('created_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ]);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * 報酬計算
     * 親: campaign.referral_fee がそのまま親の総取り分
     * 子: parent.child_reward_{fee} が子の取り分、差額が親の純利益
     */
    public static function calcReward(Agent $agent, MonitorReport $report): int
    {
        $fee = (int) ($report->campaign?->referral_fee ?? 0);
        if ($fee === 0) return 0;

        if ($agent->parent_id) {
            // 子: 親が設定した子への支払い額
            return $agent->parent?->childRewardFor($fee) ?? 0;
        }

        // 親: campaign.referral_fee そのまま受け取る
        return $fee;
    }
}
