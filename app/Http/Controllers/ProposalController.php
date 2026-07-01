<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationStatusLog;
use App\Models\CampaignDailySlot;
use App\Models\LineMessageJob;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProposalController extends Controller
{
    // 打診ページ表示
    public function confirm(string $token): View|\Illuminate\Http\Response
    {
        $application = Application::where('proposal_token', $token)
            ->with(['campaign', 'user'])
            ->first();

        if (!$application) {
            return response()->view('proposals.expired', [], 410);
        }

        // PR打診の自動キャンセル（invited_end_at が過ぎた場合）
        if ($application->status === 'line_contacted'
            && $application->isPrIfCampaign()
            && !$application->invited_at
            && $application->invited_end_at
            && now()->gte($application->invited_end_at)
        ) {
            $application->update([
                'status'               => 'cancelled',
                'proposal_answered_at' => now(),
                'proposal_answer'      => 'expired',
            ]);
            ApplicationStatusLog::create([
                'application_id' => $application->id,
                'from_status'    => 'line_contacted',
                'to_status'      => 'cancelled',
                'changed_by'     => null,
                'memo'           => 'PR打診期限を過ぎたため自動キャンセル',
            ]);
            return response()->view('proposals.expired', compact('application'), 410);
        }

        // 実施案内日時が過ぎていたら自動キャンセル
        if ($application->status === 'line_contacted'
            && $application->invited_at
            && now()->gte($application->invited_at)
        ) {
            $application->update([
                'status'               => 'cancelled',
                'proposal_answered_at' => now(),
                'proposal_answer'      => 'expired',
            ]);
            ApplicationStatusLog::create([
                'application_id' => $application->id,
                'from_status'    => 'line_contacted',
                'to_status'      => 'cancelled',
                'changed_by'     => null,
                'memo'           => '実施案内日時までに回答なし・自動キャンセル',
            ]);
            return response()->view('proposals.expired', compact('application'), 410);
        }

        // 案内日時・期限を過ぎたリンクはステータス問わず無効
        if ($application->invited_at && now()->gte($application->invited_at)) {
            return response()->view('proposals.expired', compact('application'), 410);
        }
        if ($application->isPrIfCampaign() && !$application->invited_at
            && $application->invited_end_at && now()->gte($application->invited_end_at)
        ) {
            return response()->view('proposals.expired', compact('application'), 410);
        }

        if (!in_array($application->status, ['line_contacted', 'scheduled'])) {
            return response()->view('proposals.expired', compact('application'), 410);
        }

        $isPrIf = $application->isPrIfCampaign();

        return view('proposals.confirm', compact('application', 'isPrIf'));
    }

    // はい → 予約中
    public function acceptYes(Request $request, string $token): RedirectResponse
    {
        $application = Application::where('proposal_token', $token)
            ->with(['campaign', 'user'])
            ->firstOrFail();

        if ($application->status !== 'line_contacted') {
            return redirect()->route('proposals.confirm', $token);
        }

        $updateData = [
            'status'               => 'scheduled',
            'reserved_at'          => now(),
            'proposal_answered_at' => now(),
            'proposal_answer'      => 'yes',
            'is_re_proposal'       => false,
        ];

        // PR打診（invited_atなし）の場合は今すぐ実施確定
        if ($application->isPrIfCampaign() && !$application->invited_at) {
            $updateData['invited_at'] = now();
        }

        $application->update($updateData);

        ApplicationStatusLog::create([
            'application_id' => $application->id,
            'from_status'    => 'line_contacted',
            'to_status'      => 'scheduled',
            'changed_by'     => null,
            'memo'           => '打診ページからユーザーが承諾',
        ]);

        $application->refresh();
        if ($application->invited_at) {
            $this->createMonitorGuideJob($application);
        }

        return redirect()->route('proposals.complete', $token);
    }

    // いいえ → 候補日時表示
    public function declineNo(string $token): View
    {
        $application = Application::where('proposal_token', $token)
            ->with(['campaign', 'user'])
            ->firstOrFail();

        $isPrIf = $application->isPrIfCampaign();

        $slots = $this->generateTimeSlots($application->user, null, $application);

        return view('proposals.no_options', compact('application', 'slots', 'isPrIf'));
    }

    // いいえ → 候補日時を選択
    public function selectSlot(Request $request, string $token): RedirectResponse
    {
        $request->validate([
            'slot_start' => 'required|date',
            'slot_end'   => 'nullable|date',
        ]);

        $application = Application::where('proposal_token', $token)
            ->with(['campaign', 'user'])
            ->firstOrFail();

        if ($application->status !== 'line_contacted') {
            return redirect()->route('proposals.confirm', $token);
        }

        $application->update([
            'status'               => 'scheduled',
            'reserved_at'          => now(),
            'proposal_answered_at' => now(),
            'proposal_answer'      => 'yes',
            'invited_at'           => $request->slot_start,
            'invited_end_at'       => $request->slot_end,
        ]);

        ApplicationStatusLog::create([
            'application_id' => $application->id,
            'from_status'    => 'line_contacted',
            'to_status'      => 'scheduled',
            'changed_by'     => null,
            'memo'           => '打診ページから別日程を選択して承諾',
        ]);

        $application->refresh();
        $this->createMonitorGuideJob($application);

        return redirect()->route('proposals.complete', $token);
    }

    // いいえ → キャンセル（再応募必要）
    public function cancel(string $token): View
    {
        $application = Application::where('proposal_token', $token)
            ->with('user')
            ->firstOrFail();

        if ($application->status === 'line_contacted') {
            $application->update([
                'status'               => 'cancelled',
                'proposal_answered_at' => now(),
                'proposal_answer'      => 'no',
            ]);

            ApplicationStatusLog::create([
                'application_id' => $application->id,
                'from_status'    => 'line_contacted',
                'to_status'      => 'cancelled',
                'changed_by'     => null,
                'memo'           => '打診ページからユーザーがキャンセル',
            ]);
        }

        return view('proposals.cancelled', compact('application'));
    }

    // 完了ページ
    public function complete(string $token): View
    {
        $application = Application::where('proposal_token', $token)
            ->with(['campaign'])
            ->firstOrFail();

        return view('proposals.complete', compact('application'));
    }

    // 間違えた → 打診中に戻す
    public function revert(string $token): RedirectResponse
    {
        $application = Application::where('proposal_token', $token)->firstOrFail();

        if ($application->status !== 'scheduled') {
            return redirect()->route('proposals.confirm', $token);
        }

        $application->update([
            'status'               => 'line_contacted',
            'reserved_at'          => null,
            'proposal_answered_at' => null,
            'proposal_answer'      => null,
            'invited_at'           => null,
            'invited_end_at'       => null,
        ]);

        // pending の monitor_guide / reminder ジョブをキャンセル
        LineMessageJob::where('application_id', $application->id)
            ->whereIn('send_type', ['monitor_guide', 'reminder'])
            ->where('status', 'pending')
            ->update(['status' => 'canceled']);

        ApplicationStatusLog::create([
            'application_id' => $application->id,
            'from_status'    => 'scheduled',
            'to_status'      => 'line_contacted',
            'changed_by'     => null,
            'memo'           => '打診ページからユーザーが「間違えた」',
        ]);

        return redirect()->route('proposals.confirm', $token);
    }

    // 案内文・リマインドジョブ作成
    private function createMonitorGuideJob(Application $application): void
    {
        $campaign = $application->campaign;
        $user     = $application->user;

        $guideMsg = $campaign->monitor_invite_message
            ?? "【モニターご案内】\n{$campaign->title}\n\n実施時間になりました。モニターを開始してください。";

        LineMessageJob::create([
            'application_id' => $application->id,
            'user_id'        => $application->user_id,
            'campaign_id'    => $application->campaign_id,
            'line_user_id'   => $user?->line_user_id,
            'send_type'      => 'monitor_guide',
            'message_body'   => $guideMsg,
            'send_at'        => $application->invited_at,
            'status'         => 'pending',
        ]);

        if ($application->invited_end_at) {
            $endMsg = $campaign->monitor_end_message
                ?? "【モニター終了】\n{$campaign->title}\n\nモニター時間が終了しました。ご報告をお願いします。";

            LineMessageJob::create([
                'application_id' => $application->id,
                'user_id'        => $application->user_id,
                'campaign_id'    => $application->campaign_id,
                'line_user_id'   => $user?->line_user_id,
                'send_type'      => 'reminder',
                'message_body'   => $endMsg,
                'send_at'        => $application->invited_end_at,
                'status'         => 'pending',
            ]);
        }
    }

    // available_times から候補スロットを生成（$minStart 以降3日分・枠かぶりチェック付き）
    private function generateTimeSlots(\App\Models\User $user, ?Carbon $minStart = null, ?Application $application = null): array
    {
        $slotMap = [
            '10:00〜13:00' => ['10:00', '13:00'],
            '14:00〜17:00' => ['14:00', '17:00'],
            '18:00〜20:00' => ['18:00', '20:00'],
            '21:00〜24:00' => ['21:00', '24:00'],
        ];

        $availableTimes = $user->available_times ?? [];
        if (empty($availableTimes) || in_array('いつでもOK', $availableTimes)) {
            $availableTimes = array_keys($slotMap);
        }

        $dayNames = ['日', '月', '火', '水', '木', '金', '土'];

        // 開始日：minStart の日付 or 明日、どちらか遅い方
        $startDate = Carbon::tomorrow()->startOfDay();
        if ($minStart && $minStart->copy()->startOfDay()->gt($startDate)) {
            $startDate = $minStart->copy()->startOfDay();
        }

        $campaignId = $application?->campaign_id;

        // 枠かぶりチェック用データを事前取得
        $activeStatuses = ['line_contacted', 'scheduled', 'confirming', 'completed', 'reported', 'approved', 'point_granted'];

        // 3日分の日付を収集
        $dates = [];
        for ($d = 0; $d < 3; $d++) {
            $dates[] = $startDate->copy()->addDays($d)->toDateString();
        }

        // 同案件・同日の既存予約を一括取得（同じ invited_at の件数チェック用）
        $bookedSlots = collect();
        // 同日の合計件数チェック用
        $dailyBookedCounts = [];
        // 日次目標を取得
        $dailyPlannedCounts = [];

        if ($campaignId) {
            $existingApps = Application::where('campaign_id', $campaignId)
                ->where('id', '!=', ($application?->id ?? 0))
                ->whereIn('status', $activeStatuses)
                ->whereNotNull('invited_at')
                ->whereIn(DB::raw('DATE(invited_at)'), $dates)
                ->get(['id', 'invited_at', 'invited_end_at', 'status']);

            // invited_at ごとの件数（同一スロット重複チェック用）
            $bookedSlots = $existingApps->groupBy(fn($a) => $a->invited_at->format('Y-m-d H:i'));

            // 日付ごとの合計（daily target チェック用）
            foreach ($existingApps as $a) {
                $dk = $a->invited_at->toDateString();
                $dailyBookedCounts[$dk] = ($dailyBookedCounts[$dk] ?? 0) + 1;
            }

            // CampaignDailySlot の planned_count を取得
            $dailySlots = CampaignDailySlot::where('campaign_id', $campaignId)
                ->whereIn('target_date', $dates)
                ->get();
            foreach ($dailySlots as $ds) {
                $dailyPlannedCounts[$ds->target_date->toDateString()] = $ds->planned_count;
            }
        }

        $slots = [];

        for ($d = 0; $d < 3; $d++) {
            $date = $startDate->copy()->addDays($d);
            $dateStr = $date->toDateString();
            $dayLabel = $date->format('m/d') . '(' . $dayNames[$date->dayOfWeek] . ')';

            // 日次目標チェック：planned_count が設定されていてすでに上限に達している場合は日ごとスキップ
            $plannedCount = $dailyPlannedCounts[$dateStr] ?? null;
            $dailyBooked  = $dailyBookedCounts[$dateStr] ?? 0;
            if ($plannedCount !== null && $dailyBooked >= $plannedCount) {
                continue;
            }

            foreach ($availableTimes as $timeValue) {
                $range = $slotMap[$timeValue] ?? null;
                if (!$range) continue;

                $slotStart = Carbon::parse($dateStr . ' ' . $range[0] . ':00');

                // 48h制限チェック
                if ($minStart && $slotStart->lt($minStart)) continue;

                // 同一スロット重複チェック（同じ案件・同じ開始時間が既に埋まっている）
                $slotKey = $slotStart->format('Y-m-d H:i');
                if ($bookedSlots->has($slotKey)) continue;

                $endHour = $range[1] === '24:00' ? '23:59' : $range[1];
                $slots[] = [
                    'label' => "{$dayLabel} {$timeValue}",
                    'start' => $dateStr . ' ' . $range[0] . ':00',
                    'end'   => $dateStr . ' ' . $endHour . ':00',
                ];
            }
        }

        return $slots;
    }
}
