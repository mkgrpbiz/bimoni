<?php

namespace App\Services;

use App\Models\MonitorReport;
use App\Models\UserReferralReward;

class UserReferralService
{
    // 紹介報酬額（紹介された人の初回報告承認時、1回のみ）
    public const REWARD_AMOUNT = 1000;

    // 紹介された側の初回報告承認時に、紹介した側へ1回きりの紹介報酬を発生させる
    // （2回目以降の報告や、紹介コードが未設定のユーザーは対象外）
    public function grantForApprovedReport(MonitorReport $report): void
    {
        if ($report->purchase_type !== 'initial') {
            return;
        }

        $user = $report->user;
        if (!$user || !$user->referred_by_user_id) {
            return;
        }

        // referred_user_id にユニーク制約があるため、2件目以降は作成自体が失敗して二重付与にならない
        if (UserReferralReward::where('referred_user_id', $user->id)->exists()) {
            return;
        }

        UserReferralReward::create([
            'referrer_user_id'   => $user->referred_by_user_id,
            'referred_user_id'   => $user->id,
            'monitor_report_id'  => $report->id,
            'amount'             => self::REWARD_AMOUNT,
            'payment_status'     => 'pending',
        ]);
    }
}
