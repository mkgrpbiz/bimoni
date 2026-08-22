<?php

namespace App\Services;

use App\Models\MonitorReport;
use App\Models\UserReferralReward;
use App\Models\UserReferralSetting;

class UserReferralService
{
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

        $setting = UserReferralSetting::current();
        if (!$setting->enabled) {
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
            'amount'             => $setting->reward_amount,
            'payment_status'     => 'pending',
        ]);
    }
}
