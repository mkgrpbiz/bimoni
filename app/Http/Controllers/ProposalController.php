<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationStatusLog;
use App\Models\LineMessageJob;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProposalController extends Controller
{
    // 打診ページ表示
    public function confirm(string $token): View|\Illuminate\Http\Response
    {
        $application = Application::where('proposal_token', $token)
            ->with(['campaign', 'user'])
            ->firstOrFail();

        if (!in_array($application->status, ['line_contacted', 'scheduled'])) {
            return response()->view('proposals.expired', compact('application'), 410);
        }

        return view('proposals.confirm', compact('application'));
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

        $application->update([
            'status'               => 'scheduled',
            'reserved_at'          => now(),
            'proposal_answered_at' => now(),
            'proposal_answer'      => 'yes',
        ]);

        ApplicationStatusLog::create([
            'application_id' => $application->id,
            'from_status'    => 'line_contacted',
            'to_status'      => 'scheduled',
            'changed_by'     => null,
            'memo'           => '打診ページからユーザーが承諾',
        ]);

        // 案内文 LINE ジョブ作成（invited_at が設定されている場合）
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

        $slots = $this->generateTimeSlots($application->user);

        return view('proposals.no_options', compact('application', 'slots'));
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
    public function cancel(string $token): RedirectResponse
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

    // available_times から直近の候補スロットを生成
    private function generateTimeSlots(\App\Models\User $user): array
    {
        // 固定の時間帯定義（value => [start, end]）
        $slotMap = [
            '10:00〜13:00' => ['10:00', '13:00'],
            '14:00〜17:00' => ['14:00', '17:00'],
            '18:00〜20:00' => ['18:00', '20:00'],
            '21:00〜24:00' => ['21:00', '24:00'],
        ];

        $availableTimes = $user->available_times ?? [];

        // いつでもOK の場合は全スロットを対象に
        if (in_array('いつでもOK', $availableTimes)) {
            $availableTimes = array_keys($slotMap);
        }

        $slots = [];
        $dayNames = ['日', '月', '火', '水', '木', '金', '土'];

        for ($d = 1; $d <= 3 && count($slots) < 4; $d++) {
            $date = Carbon::today()->addDays($d);
            $dayLabel = $date->format('m/d') . '(' . $dayNames[$date->dayOfWeek] . ')';

            foreach ($availableTimes as $timeValue) {
                if (count($slots) >= 4) break;
                $range = $slotMap[$timeValue] ?? null;
                if (!$range) continue;

                $endHour = $range[1] === '24:00' ? '23:59' : $range[1];
                $slots[] = [
                    'label' => "{$dayLabel} {$timeValue}",
                    'start' => $date->format('Y-m-d') . ' ' . $range[0] . ':00',
                    'end'   => $date->format('Y-m-d') . ' ' . $endHour . ':00',
                ];
            }
        }

        // available_times が未設定/マッチなしの場合は翌2日分で10:00〜13:00
        if (empty($slots)) {
            for ($d = 1; $d <= 2; $d++) {
                $date = Carbon::today()->addDays($d);
                $dayLabel = $date->format('m/d') . '(' . $dayNames[$date->dayOfWeek] . ')';
                $slots[] = [
                    'label' => "{$dayLabel} 10:00〜13:00",
                    'start' => $date->format('Y-m-d') . ' 10:00:00',
                    'end'   => $date->format('Y-m-d') . ' 13:00:00',
                ];
            }
        }

        return array_slice($slots, 0, 4);
    }
}
