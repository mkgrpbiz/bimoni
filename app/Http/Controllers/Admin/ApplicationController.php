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
                $q->where('name', 'like', '%'.$request->q.'%')
                  ->orWhere('name_kana', 'like', '%'.$request->q.'%')
            );
        }

        $applications = $query->paginate(30)->withQueryString();

        // 他案件状況・48h制限の計算
        $userIds = $applications->pluck('user_id')->unique();

        // 同ユーザーの全関連応募を取得
        $allUserApps = Application::with('campaign:id,title')
            ->whereIn('user_id', $userIds)
            ->where(function ($q) {
                // invited_end_at未設定の進行中ステータス
                $q->where(function ($q2) {
                    $q2->whereIn('status', ['line_contacted', 'scheduled', 'confirming'])
                       ->whereNull('invited_end_at');
                // invited_end_at設定済み → 48h以内のものだけ
                })->orWhere('invited_end_at', '>=', now()->subHours(48));
            })
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

        return view('admin.applications.index', compact('applications', 'campaigns', 'campaignStatus', 'tabCounts'));
    }

    public function campaignIndex(Campaign $campaign, Request $request): View
    {
        $query = $campaign->applications()->with(['user', 'statusLogs.changedBy', 'lineMessageJobs'])->latest('applied_at');

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

        $slots = CampaignDailySlot::where('campaign_id', $campaign->id)
            ->whereIn('target_date', [$today->toDateString(), $tomorrow->toDateString(), $dayAfter->toDateString()])
            ->get()
            ->keyBy(fn($s) => $s->target_date->toDateString());

        $completedApps = $campaign->applications()->where('status', 'completed')->with('user')->get();
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

        return view('admin.applications.campaign_index', compact('campaign', 'applications', 'summary', 'allCampaigns', 'tabCounts', 'campaignStatus'));
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
            'invited_end_at' => 'nullable|date|after_or_equal:invited_at',
        ]);

        // 打診中への変更処理
        if ($request->status === 'line_contacted') {
            // 48h・ロックチェック
            $others = $this->getOtherApplicationsMap(
                collect([$application->user_id]),
                $application->campaign_id
            )->get($application->user_id, collect());

            if ($application->isLocked($others)) {
                return back()->with('error', 'このユーザーは現在打診不可の状態です（他案件対応中または48時間制限）。');
            }

            // invited_at を保存（打診時に設定）
            if ($request->filled('invited_at')) {
                $application->update([
                    'invited_at'     => $request->invited_at,
                    'invited_end_at' => $request->invited_end_at,
                ]);
            }

            // 打診トークンを生成（初回のみ）
            if (!$application->proposal_token) {
                $application->update(['proposal_token' => Str::random(64)]);
            }
            $application->refresh();

            // 打診 LINE ジョブ作成
            $proposalUrl  = route('proposals.confirm', $application->proposal_token);
            $invitedLabel = $application->invited_at
                ? $application->invited_at->format('m月d日 H:i')
                  . ($application->invited_end_at ? '〜' . $application->invited_end_at->format('H:i') : '')
                : '（日時未設定）';

            $proposalMsg = "【モニターご案内】\n"
                . $application->campaign->title . "\n\n"
                . "実施予定日時: {$invitedLabel}\n\n"
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
        if ($application->continuation_wish !== '希望') {
            return back()->with('error', '継続希望が「希望」の応募者のみ送信できます。');
        }

        if (!$application->continuation_token) {
            $application->update(['continuation_token' => Str::random(64)]);
            $application->refresh();
        }

        $campaign = $application->campaign;
        $acceptUrl  = route('continuation.accept', $application->continuation_token);
        $declineUrl = route('continuation.decline', $application->continuation_token);

        $msg = "【継続購入のご案内】\n"
            . $campaign->title . "\n\n"
            . "継続購入についてのご希望をお聞かせください。\n\n"
            . "✅ 継続購入可能\n" . $acceptUrl . "\n\n"
            . "❌ 継続購入不可\n" . $declineUrl;

        $lineService->sendPush($application->user_id, $msg, 'continuation_request', $application->id);

        return back()->with('success', '継続依頼LINEを送信しました。');
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

    // ユーザーIDリストに対して、指定案件以外の進行中応募を一括取得
    private function getOtherApplicationsMap(Collection $userIds, int $currentCampaignId): Collection
    {
        return Application::with('campaign:id,title')
            ->whereIn('user_id', $userIds)
            ->where('campaign_id', '!=', $currentCampaignId)
            ->where(function ($q) {
                // invited_end_at未設定の進行中ステータス
                $q->where(function ($q2) {
                    $q2->whereIn('status', ['line_contacted', 'scheduled', 'confirming'])
                       ->whereNull('invited_end_at');
                // invited_end_at設定済み → 48h以内のものだけ
                })->orWhere('invited_end_at', '>=', now()->subHours(48));
            })
            ->get()
            ->groupBy('user_id');
    }
}
