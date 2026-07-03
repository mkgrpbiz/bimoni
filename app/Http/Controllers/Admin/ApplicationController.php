<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationStatusLog;
use App\Models\Campaign;
use App\Models\CampaignDailySlot;
use App\Models\LineMessageJob;
use App\Models\User;
use App\Services\LineMessagingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $campaignStatus = $request->input('status', 'published');

        $query = Application::with(['user', 'campaign', 'lineMessageJobs'])
            ->whereHas('campaign', fn($q) => $q->where('status', $campaignStatus))
            ->latest('applied_at');

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }
        if ($request->filled('q')) {
            $query->whereHas('user', fn($q) =>
                $q->where('bimoni_user_id', 'like', '%'.$request->q.'%')
                  ->orWhere('line_display_name', 'like', '%'.$request->q.'%')
                  ->orWhere('name', 'like', '%'.$request->q.'%')
                  ->orWhere('name_kana', 'like', '%'.$request->q.'%')
            );
        }

        $applications = $query->paginate(30)->withQueryString();

        // 他案件状況・48h制限の計算
        $userIds = $applications->pluck('user_id')->unique();

        // 同ユーザーの全関連応募を取得（進行中 + 48h以内に終了した案件）
        // PR+IF案件は他案件状況に影響しないため除外
        $cutoff = now()->subHours(48);
        $allUserApps = Application::with('campaign:id,title,campaign_type,pr_media')
            ->whereIn('user_id', $userIds)
            ->where(function ($q) use ($cutoff) {
                $q->whereIn('status', ['line_contacted', 'scheduled', 'confirming'])
                  ->orWhere(function ($q2) use ($cutoff) {
                      $q2->whereIn('status', ['completed', 'reported', 'approved', 'point_granted'])
                         ->where(fn($q3) => $q3->where('invited_end_at', '>=', $cutoff)
                                               ->orWhere('invited_at', '>=', $cutoff));
                  });
            })
            ->whereHas('campaign', fn($q) => $q->where(
                fn($q2) => $q2->where('campaign_type', '!=', 'pr')->orWhere('pr_media', '!=', 'IF')
            ))
            ->get()
            ->groupBy('user_id');

        $applications->getCollection()->transform(function (Application $app) use ($allUserApps) {
            // 自分自身は除いた他案件
            $others = $allUserApps->get($app->user_id, collect())->filter(fn($o) => $o->id !== $app->id);
            $app->other_applications = $others;
            $app->unlock_at = $app->getUnlockAt($others);
            $app->is_locked = $app->isLocked($others);
            return $app;
        });

        $tabCounts = $this->getTabCounts();
        $campaigns = Campaign::orderBy('title')->get();

        // アラート: 翌日未達成打診
        $tomorrowDate  = now()->addDay()->toDateString();
        $activeStatuses = ['line_contacted', 'scheduled', 'confirming', 'completed', 'reported', 'approved', 'point_granted'];
        $tomorrowSlots = CampaignDailySlot::where('target_date', $tomorrowDate)
            ->where('planned_count', '>', 0)
            ->with('campaign:id,title')
            ->get();
        $tomorrowUnderAlerts = collect();
        foreach ($tomorrowSlots as $slot) {
            $booked = Application::where('campaign_id', $slot->campaign_id)
                ->whereIn('status', $activeStatuses)
                ->whereNotNull('invited_at')
                ->whereDate('invited_at', $tomorrowDate)
                ->count();
            if ($booked < $slot->planned_count) {
                $tomorrowUnderAlerts->push(['slot' => $slot, 'booked' => $booked, 'planned' => $slot->planned_count]);
            }
        }

        // アラート: 未達成目標継続率（実施完了以上のデータで計算）
        $contCampaigns = Campaign::whereNotNull('continuation_rate')->where('continuation_rate', '>', 0)->get();
        $contCompletedStatuses = ['completed', 'reported', 'approved', 'point_granted'];
        $contStats = Application::whereIn('campaign_id', $contCampaigns->pluck('id'))
            ->whereIn('status', $contCompletedStatuses)
            ->selectRaw('campaign_id, COUNT(*) as total, SUM(continuation_response = "possible") as ok_count')
            ->groupBy('campaign_id')
            ->get()->keyBy('campaign_id');
        $continuationRateAlerts = $contCampaigns->filter(function ($c) use ($contStats) {
            $s = $contStats->get($c->id);
            return $s && $s->total > 0 && ($s->ok_count / $s->total * 100) < $c->continuation_rate;
        })->map(function ($c) use ($contStats) {
            $s = $contStats->get($c->id);
            return ['campaign' => $c, 'actual' => round($s->ok_count / $s->total * 100), 'target' => (int) $c->continuation_rate];
        })->values();

        return view('admin.applications.index', compact(
            'applications', 'campaigns', 'campaignStatus', 'tabCounts',
            'tomorrowUnderAlerts', 'continuationRateAlerts'
        ));
    }

    public function campaignIndex(Campaign $campaign, Request $request): View
    {
        $query = $campaign->applications()->with(['user', 'statusLogs.changedBy', 'lineMessageJobs'])
            ->orderByRaw("CASE WHEN status IN ('completed','cancelled') THEN 1 ELSE 0 END ASC")
            ->orderByRaw("CASE WHEN status IN ('completed','cancelled') THEN applied_at END DESC")
            ->orderBy('applied_at', 'asc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $query->whereHas('user', fn($q) =>
                $q->where('name', 'like', '%'.$request->q.'%')
                  ->orWhere('name_kana', 'like', '%'.$request->q.'%')
            );
        }

        $applications = $query->paginate(50)->withQueryString();

        // 全ユーザーIDを収集して他案件情報を一括取得
        $userIds = $applications->pluck('user_id')->unique();
        $otherApplicationsMap = $this->getOtherApplicationsMap($userIds, $campaign->id);

        // 各応募に対して 48h 制限・他案件ステータスを付与
        $applications->getCollection()->transform(function (Application $app) use ($otherApplicationsMap) {
            $others = $otherApplicationsMap->get($app->user_id, collect());
            $app->other_applications = $others;
            $app->unlock_at = $app->getUnlockAt($others);
            $app->is_locked = $app->isLocked($others);
            return $app;
        });

        // ヘッダー集計
        $today    = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $dayAfter = Carbon::today()->addDays(2);

        $targetDates = [$today->toDateString(), $tomorrow->toDateString(), $dayAfter->toDateString()];

        $slots = CampaignDailySlot::where('campaign_id', $campaign->id)
            ->whereIn('target_date', $targetDates)
            ->get()
            ->keyBy(fn($s) => $s->target_date->toDateString());

        // 実際のステータス別件数を動的集計（PR打診はinvited_atがnullのためCOALESCEで対応）
        $appCounts = $campaign->applications()
            ->whereIn('status', ['line_contacted', 'scheduled', 'confirming', 'completed', 'reported', 'approved', 'point_granted'])
            ->where(function ($q) use ($targetDates) {
                $q->whereIn(DB::raw('DATE(invited_at)'), $targetDates)
                  ->orWhereIn(DB::raw('DATE(invited_end_at)'), $targetDates);
            })
            ->selectRaw('DATE(COALESCE(invited_at, invited_end_at)) as inv_date, status, COUNT(*) as cnt')
            ->groupBy(DB::raw('DATE(COALESCE(invited_at, invited_end_at))'), 'status')
            ->get()
            ->groupBy('inv_date');

        foreach ($targetDates as $dateStr) {
            $slot = $slots->get($dateStr);
            if (!$slot) continue;
            $dayCounts = $appCounts->get($dateStr, collect());
            $slot->invited_count   = $dayCounts->where('status', 'line_contacted')->sum('cnt');
            $slot->reserved_count  = $dayCounts->where('status', 'scheduled')->sum('cnt');
            $slot->completed_count = $dayCounts->filter(fn($r) => in_array($r->status, ['confirming', 'completed', 'reported', 'approved', 'point_granted']))->sum('cnt');
        }

        $completedApps = $campaign->applications()->whereIn('status', ['completed', 'reported', 'approved', 'point_granted'])->with('user')->get();
        $summary = [
            'today'    => $slots->get($today->toDateString()),
            'tomorrow' => $slots->get($tomorrow->toDateString()),
            'day_after' => $slots->get($dayAfter->toDateString()),
            'completed_male'   => $completedApps->filter(fn($a) => $a->user?->gender === 'male')->count(),
            'completed_female' => $completedApps->filter(fn($a) => $a->user?->gender === 'female')->count(),
            'total_completed'  => $completedApps->count(),
            'target_male_ratio'   => $campaign->target_male_ratio,
            'target_female_ratio' => $campaign->target_female_ratio,
            'continuation_ok_count' => $completedApps->where('continuation_response', 'possible')->count(),
        ];

        $allCampaigns   = Campaign::orderBy('title')->get(['id', 'title', 'status']);
        $tabCounts      = $this->getTabCounts();
        $campaignStatus = $campaign->status;

        // アラート: 翌日未達成打診
        $tomorrowDate2  = now()->addDay()->toDateString();
        $activeStatuses2 = ['line_contacted', 'scheduled', 'confirming', 'completed', 'reported', 'approved', 'point_granted'];
        $tomorrowSlots2 = CampaignDailySlot::where('target_date', $tomorrowDate2)
            ->where('planned_count', '>', 0)
            ->with('campaign:id,title')
            ->get();
        $tomorrowUnderAlerts = collect();
        foreach ($tomorrowSlots2 as $slot) {
            $booked = Application::where('campaign_id', $slot->campaign_id)
                ->whereIn('status', $activeStatuses2)
                ->whereNotNull('invited_at')
                ->whereDate('invited_at', $tomorrowDate2)
                ->count();
            if ($booked < $slot->planned_count) {
                $tomorrowUnderAlerts->push(['slot' => $slot, 'booked' => $booked, 'planned' => $slot->planned_count]);
            }
        }

        // アラート: 未達成目標継続率（実施完了以上のデータで計算）
        $contCampaigns2 = Campaign::whereNotNull('continuation_rate')->where('continuation_rate', '>', 0)->get();
        $contCompletedStatuses2 = ['completed', 'reported', 'approved', 'point_granted'];
        $contStats2 = Application::whereIn('campaign_id', $contCampaigns2->pluck('id'))
            ->whereIn('status', $contCompletedStatuses2)
            ->selectRaw('campaign_id, COUNT(*) as total, SUM(continuation_response = "possible") as ok_count')
            ->groupBy('campaign_id')
            ->get()->keyBy('campaign_id');
        $continuationRateAlerts = $contCampaigns2->filter(function ($c) use ($contStats2) {
            $s = $contStats2->get($c->id);
            return $s && $s->total > 0 && ($s->ok_count / $s->total * 100) < $c->continuation_rate;
        })->map(function ($c) use ($contStats2) {
            $s = $contStats2->get($c->id);
            return ['campaign' => $c, 'actual' => round($s->ok_count / $s->total * 100), 'target' => (int) $c->continuation_rate];
        })->values();

        return view('admin.applications.campaign_index', compact(
            'campaign', 'applications', 'summary', 'allCampaigns', 'tabCounts', 'campaignStatus',
            'tomorrowUnderAlerts', 'continuationRateAlerts'
        ));
    }

    public function show(Application $application): View
    {
        $application->load(['user', 'campaign', 'schedules.proposedBy', 'statusLogs.changedBy']);

        $others = $this->getOtherApplicationsMap(
            collect([$application->user_id]),
            $application->campaign_id
        )->get($application->user_id, collect());

        $application->other_applications = $others;
        $application->unlock_at = $application->getUnlockAt($others);
        $application->is_locked = $application->isLocked($others);

        return view('admin.applications.show', compact('application'));
    }

    public function updateStatus(Request $request, Application $application): RedirectResponse
    {
        $request->validate([
            'status'         => 'required|in:line_contacted,scheduled,confirming,completed,reported,approved,point_granted,cancelled',
            'memo'           => 'nullable|string|max:500',
            'invited_at'     => 'nullable|date',
            'invited_end_at' => 'nullable|date',
        ]);

        // 打診中への変更処理
        if ($request->status === 'line_contacted') {
            $application->loadMissing('campaign');
            $isPrIf = $application->isPrIfCampaign();

            if (!$isPrIf) {
                // 通常案件：ロック・48h制限チェック
                $others = $this->getOtherApplicationsMap(
                    collect([$application->user_id]),
                    $application->campaign_id
                )->get($application->user_id, collect());

                if ($application->isLocked($others)) {
                    return back()->with('error', 'このユーザーは現在打診不可の状態です（他案件対応中）。');
                }

                if ($request->filled('invited_at')) {
                    $earliest = $application->getEarliestNextInviteAt($others);
                    if ($earliest && Carbon::parse($request->invited_at)->lt($earliest)) {
                        return back()->with('error', $earliest->format('m/d H:i') . '〜打診可能です。');
                    }
                }
            }

            // 案内日時を保存
            if ($request->filled('invited_at')) {
                $application->update([
                    'invited_at'     => $request->invited_at,
                    'invited_end_at' => $request->invited_end_at,
                ]);
            } elseif ($isPrIf && $request->filled('invited_end_at')) {
                // PR打診：締め切り日時のみ設定（invited_atはユーザー確認後にセット）
                $application->update(['invited_end_at' => $request->invited_end_at]);
            }

            // 打診トークンを生成（再打診時は新しいトークンで上書き→旧リンクを無効化）
            $application->update(['proposal_token' => Str::random(64)]);
            $application->refresh();

            // 打診 LINE ジョブ作成
            $proposalUrl = route('proposals.confirm', $application->proposal_token);

            if ($isPrIf && !$application->invited_at && $application->invited_end_at) {
                // PR打診メッセージ
                $dayNames     = ['日', '月', '火', '水', '木', '金', '土'];
                $deadlineLabel = $application->invited_end_at->format('m月d日(')
                    . $dayNames[$application->invited_end_at->dayOfWeek]
                    . $application->invited_end_at->format(') H:i');
                $proposalMsg = "【PR打診のご案内】\n"
                    . $application->campaign->title . "\n\n"
                    . "実施期限: 今から{$deadlineLabel}まで\n\n"
                    . "期限内に実施可能な方は下記URLから今すぐご回答ください。\n"
                    . $proposalUrl . "\n\n"
                    . "別日程をご希望の場合もURLから日程調整が可能です。";
            } else {
                // 通常打診メッセージ
                $invitedLabel = $application->invited_at
                    ? $application->invited_at->format('m月d日 H:i')
                      . ($application->invited_end_at ? '〜' . $application->invited_end_at->format('H:i') : '')
                    : '（日時未設定）';

                $proposalMsg = "【モニターご案内】\n"
                    . $application->campaign->title . "\n\n"
                    . "実施案内日時: {$invitedLabel}\n\n"
                    . "以下のURLよりご回答をお願いします。\n"
                    . $proposalUrl . "\n\n"
                    . "※こちら受信時間から実施案内日時までに回答がない場合、自動キャンセルになり再度応募していただく必要があります。\n"
                    . "上記の時間が難しい場合、別日程調整より都合の良い時間に予約お願いいたします。";
            }

            LineMessageJob::create([
                'application_id' => $application->id,
                'user_id'        => $application->user_id,
                'campaign_id'    => $application->campaign_id,
                'line_user_id'   => $application->user?->line_user_id,
                'send_type'      => 'proposal',
                'message_body'   => $proposalMsg,
                'send_at'        => now(),
                'status'         => 'pending',
            ]);
        }

        // 予約中に管理者側から直接移行する場合も案内日時を保存
        if ($request->status === 'scheduled' && $request->filled('invited_at')) {
            $application->update([
                'invited_at'     => $request->invited_at,
                'invited_end_at' => $request->invited_end_at,
            ]);
        }

        $adminId = auth('web')->id();
        $application->changeStatus($request->status, $adminId, $request->memo);

        return back()->with('success', 'ステータスを更新しました。');
    }

    public function updateNotes(Request $request, Application $application): RedirectResponse
    {
        $request->validate(['notes' => 'nullable|string']);
        $application->update(['notes' => $request->notes]);
        return back()->with('success', 'メモを保存しました。');
    }

    public function updateInviteSchedule(Request $request, Application $application): RedirectResponse
    {
        $request->validate([
            'invited_at'              => 'nullable|date',
            'invited_end_at'          => 'nullable|date',
            'continuation_invite_date' => 'nullable|date',
        ]);

        $application->update($request->only(['invited_at', 'invited_end_at', 'continuation_invite_date']));
        return back()->with('success', '案内日時を保存しました。');
    }

    public function sendContinuationRequest(Application $application, LineMessagingService $lineService): RedirectResponse
    {
        $application->loadMissing('campaign');

        // 再送時も新しいトークンで上書き → 旧リンクを無効化
        $application->update(['continuation_token' => Str::random(64), 'continuation_response' => null, 'continuation_responded_at' => null]);
        $application->refresh();

        $campaign   = $application->campaign;
        $confirmUrl = route('continuation.confirm', $application->continuation_token);

        $msg = "【継続購入のご案内】\n"
            . $campaign->title . "\n\n"
            . "継続購入についてのご希望をお聞かせください。\n\n"
            . "以下のURLよりご回答をお願いします。\n"
            . $confirmUrl;

        $lineService->sendPush($application->user_id, $msg, 'continuation_request', $application->id);

        return back()->with('success', '継続依頼LINEを送信しました。');
    }

    // 打診予約一覧
    public function proposalReservationIndex(Request $request): View
    {
        $query = Application::with(['user', 'campaign:id,title,campaign_type,pr_media', 'lineMessageJobs'])
            ->whereIn('status', ['line_contacted', 'scheduled', 'confirming'])
            ->orderByDesc('invited_at');

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }
        if ($request->filled('q')) {
            $query->whereHas('user', fn($q) =>
                $q->where('bimoni_user_id', 'like', '%'.$request->q.'%')
                  ->orWhere('line_display_name', 'like', '%'.$request->q.'%')
                  ->orWhere('name', 'like', '%'.$request->q.'%')
                  ->orWhere('name_kana', 'like', '%'.$request->q.'%')
            );
        }

        $applications = $query->paginate(50)->withQueryString();

        // 他案件状況・ロック情報を付与
        $userIds = $applications->getCollection()->pluck('user_id')->filter()->unique();
        $campaignId = $request->filled('campaign_id') ? (int)$request->campaign_id : null;
        $otherApplicationsMap = $userIds->isNotEmpty()
            ? $this->getOtherApplicationsMap($userIds, $campaignId ?? 0)
            : collect();

        $applications->getCollection()->transform(function (Application $app) use ($otherApplicationsMap) {
            $others = $otherApplicationsMap->get($app->user_id, collect());
            $app->other_applications = $others;
            $app->unlock_at  = $app->getUnlockAt($others);
            $app->is_locked  = $app->isLocked($others);
            return $app;
        });

        $dismissed = session('dismissed_alerts', []);

        // アラート1: 同案件・同時刻ダブルブッキング（今後のみ）
        $duplicateAlerts = Application::whereIn('status', ['line_contacted', 'scheduled', 'confirming'])
            ->whereNotNull('invited_at')
            ->where('invited_at', '>=', now())
            ->select('campaign_id', 'invited_at', \Illuminate\Support\Facades\DB::raw('COUNT(*) as cnt'))
            ->groupBy('campaign_id', 'invited_at')
            ->having('cnt', '>', 1)
            ->with('campaign:id,title')
            ->get()
            ->filter(fn($d) => !($dismissed['dup_' . $d->campaign_id . '_' . \Carbon\Carbon::parse($d->invited_at)->timestamp] ?? false));

        // アラート2: 同案件・日次目標件数オーバー（active予約のみカウント）
        $overCapacityAlerts = collect();
        $activeStatuses = ['line_contacted', 'scheduled', 'confirming'];
        $dailySlots = CampaignDailySlot::where('target_date', '>=', now()->toDateString())
            ->where('target_date', '<=', now()->addDays(7)->toDateString())
            ->where('planned_count', '>', 0)
            ->get();

        foreach ($dailySlots as $slot) {
            $overKey = 'over_' . $slot->campaign_id . '_' . $slot->target_date->toDateString();
            if ($dismissed[$overKey] ?? false) continue;
            $bookedCount = Application::where('campaign_id', $slot->campaign_id)
                ->whereIn('status', $activeStatuses)
                ->whereNotNull('invited_at')
                ->whereDate('invited_at', $slot->target_date)
                ->count();
            if ($bookedCount > $slot->planned_count) {
                $overCapacityAlerts->push([
                    'slot'        => $slot,
                    'booked'      => $bookedCount,
                    'planned'     => $slot->planned_count,
                    'dismiss_key' => $overKey,
                ]);
            }
        }

        $campaigns = Campaign::orderBy('title')->get(['id', 'title']);

        return view('admin.proposal_reservations.index', compact(
            'applications', 'campaigns', 'duplicateAlerts', 'overCapacityAlerts'
        ));
    }

    // 再打診送信
    public function sendReProposal(Request $request, Application $application): RedirectResponse
    {
        $request->validate([
            'invited_at'     => 'nullable|date',
            'invited_end_at' => 'nullable|date',
            'memo'           => 'nullable|string|max:500',
        ]);

        if (!in_array($application->status, ['line_contacted', 'scheduled'])) {
            return back()->with('error', '打診中または予約中の応募のみ再打診できます。');
        }

        $application->loadMissing(['campaign', 'user']);

        // 進行中のモニター案内・リマインドジョブをキャンセル
        LineMessageJob::where('application_id', $application->id)
            ->whereIn('send_type', ['monitor_guide', 'reminder'])
            ->where('status', 'pending')
            ->update(['status' => 'canceled']);

        $prevStatus = $application->status;

        // 案内日時・ステータスリセット
        $updateData = [
            'status'               => 'line_contacted',
            'reserved_at'          => null,
            'proposal_answered_at' => null,
            'proposal_answer'      => null,
            'invited_at'           => $request->invited_at,
            'invited_end_at'       => $request->invited_end_at,
            'is_re_proposal'       => true,
        ];

        // PR打診（invited_atなし、invited_end_atのみ）
        $isPrIf = $application->isPrIfCampaign();
        if ($isPrIf && !$request->filled('invited_at') && $request->filled('invited_end_at')) {
            $updateData['invited_at'] = null;
        }

        $application->update($updateData);

        if (!$application->proposal_token) {
            $application->update(['proposal_token' => Str::random(64)]);
        }
        $application->refresh();

        ApplicationStatusLog::create([
            'application_id' => $application->id,
            'from_status'    => $prevStatus,
            'to_status'      => 'line_contacted',
            'changed_by'     => auth('web')->id(),
            'memo'           => '再打診送信' . ($request->memo ? '：' . $request->memo : ''),
        ]);

        // 再打診LINEメッセージ
        $proposalUrl = route('proposals.confirm', $application->proposal_token);

        if ($isPrIf && !$application->invited_at && $application->invited_end_at) {
            $dayNames      = ['日', '月', '火', '水', '木', '金', '土'];
            $deadlineLabel = $application->invited_end_at->format('m月d日(')
                . $dayNames[$application->invited_end_at->dayOfWeek]
                . $application->invited_end_at->format(') H:i');
            $invitedLabel  = '今から' . $deadlineLabel . 'まで';
        } else {
            $invitedLabel = $application->invited_at
                ? $application->invited_at->format('m月d日 H:i')
                  . ($application->invited_end_at ? '〜' . $application->invited_end_at->format('H:i') : '')
                : '（日時未設定）';
        }

        $proposalMsg = "【再打診のご案内】\n"
            . $application->campaign->title . "\n\n"
            . "申し訳ございませんが、ご指定の時間にご案内が出来なくなりました。\n"
            . "再度日程調整をお願いいたします。\n\n"
            . "新しい案内日時: {$invitedLabel}\n\n"
            . "以下のURLよりご回答をお願いします。\n"
            . $proposalUrl;

        LineMessageJob::create([
            'application_id' => $application->id,
            'user_id'        => $application->user_id,
            'campaign_id'    => $application->campaign_id,
            'line_user_id'   => $application->user?->line_user_id,
            'send_type'      => 'proposal',
            'message_body'   => $proposalMsg,
            'send_at'        => now(),
            'status'         => 'pending',
        ]);

        return back()->with('success', '再打診を送信しました。');
    }

    private function getTabCounts(): \Illuminate\Support\Collection
    {
        return collect([
            'published' => Application::whereHas('campaign', fn($q) => $q->where('status', 'published'))->count(),
            'paused'    => Application::whereHas('campaign', fn($q) => $q->where('status', 'paused'))->count(),
            'closed'    => Application::whereHas('campaign', fn($q) => $q->where('status', 'closed'))->count(),
            'draft'     => Application::whereHas('campaign', fn($q) => $q->where('status', 'draft'))->count(),
        ]);
    }

    // ユーザーIDリストに対して、指定案件以外の関連応募を一括取得
    // PR+IF案件は他案件状況・次回案内可能の対象外のため除外
    private function getOtherApplicationsMap(Collection $userIds, int $currentCampaignId): Collection
    {
        $cutoff = now()->subHours(48);

        return Application::with('campaign:id,title,campaign_type,pr_media')
            ->whereIn('user_id', $userIds)
            ->where('campaign_id', '!=', $currentCampaignId)
            ->where(function ($q) use ($cutoff) {
                $q->whereIn('status', ['line_contacted', 'scheduled', 'confirming'])
                  ->orWhere(function ($q2) use ($cutoff) {
                      $q2->whereIn('status', ['completed', 'reported', 'approved', 'point_granted'])
                         ->where(fn($q3) => $q3->where('invited_end_at', '>=', $cutoff)
                                               ->orWhere('invited_at', '>=', $cutoff));
                  });
            })
            ->whereHas('campaign', fn($q) => $q->where(
                fn($q2) => $q2->where('campaign_type', '!=', 'pr')->orWhere('pr_media', '!=', 'IF')
            ))
            ->get()
            ->groupBy('user_id');
    }
}
