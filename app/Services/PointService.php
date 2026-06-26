<?php

namespace App\Services;

use App\Models\Application;
use App\Models\MonitorReport;
use App\Models\Point;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PointService
{
    public function grantForReport(MonitorReport $report): void
    {
        $application = $report->application;
        $campaign    = $application->campaign;
        $user        = $application->user;

        // 実際の支払い額 = モニター経費 + 協力金(+○円部分)
        $amount = ($report->purchase_amount ?? 0) + ($campaign->cooperation_fee ?? 0);

        DB::transaction(function () use ($application, $campaign, $user, $amount) {
            Point::create([
                'user_id'        => $user->id,
                'type'           => 'earn',
                'amount'         => $amount,
                'reason'         => "案件「{$campaign->title}」モニター協力金",
                'application_id' => $application->id,
                'granted_by'     => Auth::guard('web')->id(),
                'created_at'     => now(),
            ]);

            $user->increment('point_balance', $amount);

            $application->update([
                'status'      => 'point_granted',
                'approved_at' => $application->approved_at ?? now(),
            ]);
        });
    }

    public function adjust(int $userId, int $amount, string $reason): void
    {
        DB::transaction(function () use ($userId, $amount, $reason) {
            Point::create([
                'user_id'    => $userId,
                'type'       => 'adjust',
                'amount'     => $amount,
                'reason'     => $reason,
                'granted_by' => Auth::guard('web')->id(),
                'created_at' => now(),
            ]);

            if ($amount > 0) {
                \App\Models\User::find($userId)?->increment('point_balance', $amount);
            } else {
                \App\Models\User::find($userId)?->decrement('point_balance', abs($amount));
            }
        });
    }
}
