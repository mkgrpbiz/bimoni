<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Campaign;
use App\Models\CampaignBonus;
use App\Models\LineMessageJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        $user      = Auth::guard('liff')->user();
        $campaigns = Campaign::where('status', 'published')
            ->with('category')
            ->orderBy('sort_order')
            ->latest()
            ->get();

        $campaignsByType = $campaigns->groupBy('campaign_type');

        $appliedIds = Application::where('user_id', $user->id)
            ->whereIn('campaign_id', $campaigns->pluck('id'))
            ->whereNotIn('status', ['cancelled'])
            ->pluck('status', 'campaign_id');

        $now = now();
        $activeBonuses = CampaignBonus::with('campaign')
            ->whereHas('campaign', fn($q) => $q->where('status', 'published'))
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->get()
            ->keyBy('campaign_id');

        $scheduledApps = Application::where('user_id', $user->id)
            ->where('status', 'scheduled')
            ->whereNotNull('invited_at')
            ->where('invited_at', '>', $now)
            ->with('campaign:id,title')
            ->orderBy('invited_at')
            ->get();

        return view('member.campaigns.index', compact('campaigns', 'campaignsByType', 'appliedIds', 'activeBonuses', 'scheduledApps'));
    }

    public function show(Campaign $campaign): View|RedirectResponse
    {
        $user        = Auth::guard('liff')->user();
        $application = Application::where('user_id', $user->id)
            ->where('campaign_id', $campaign->id)
            ->whereNotIn('status', ['cancelled'])
            ->first();

        // 非公開案件は応募歴がある場合のみ閲覧可（キャンセル済みの応募歴も含む）
        if ($campaign->status !== 'published' && !$application) {
            $hasAnyApplication = Application::where('user_id', $user->id)
                ->where('campaign_id', $campaign->id)
                ->exists();
            if (!$hasAnyApplication) {
                abort(404);
            }
        }

        $now = now();
        $activeBonus = \App\Models\CampaignBonus::where('campaign_id', $campaign->id)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->first();

        return view('member.campaigns.show', compact('campaign', 'application', 'activeBonus'));
    }

    public function apply(Request $request, Campaign $campaign): RedirectResponse
    {
        if ($campaign->status !== 'published') {
            abort(404);
        }

        $user = Auth::guard('liff')->user();

        // 重複応募チェック（キャンセルは再応募可）
        $exists = Application::where('user_id', $user->id)
            ->where('campaign_id', $campaign->id)
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($exists) {
            return redirect()->route('member.campaigns.show', $campaign)
                ->with('error', 'すでに応募済みです。');
        }

        $hasContinuation = $campaign->continuation_cooperation_fee || $campaign->recurring_purchase_fee;

        $rules = [
            'purchase_available_times'   => 'required|array|min:1',
            'purchase_available_times.*' => 'string|max:50',
        ];
        if ($hasContinuation) {
            $rules['continuation_wish'] = 'required|in:希望,不可';
        }
        $request->validate($rules);

        $now         = now();
        $activeBonus = CampaignBonus::where('campaign_id', $campaign->id)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->first();

        $fields = [
            'status'                   => 'pending',
            'applied_at'               => $now,
            'bonus_amount'             => $activeBonus?->bonus_amount,
            'continuation_wish'        => $hasContinuation ? $request->continuation_wish : null,
            'purchase_available_times' => $request->purchase_available_times,
            'selected_at'              => null, 'line_contacted_at'   => null,
            'sounded_at'               => null, 'schedule_confirmed_at' => null,
            'reserved_at'              => null, 'monitoring_confirmed_at' => null,
            'completed_at'             => null, 'reported_at'         => null,
            'approved_at'              => null, 'invited_at'          => null,
            'invited_end_at'           => null, 'proposal_token'      => null,
            'proposal_answered_at'     => null, 'proposal_answer'     => null,
            'proposal_sent_at'         => null, 'continuation_token'  => null,
            'continuation_response'    => null, 'continuation_responded_at' => null,
            'notes'                    => null,
        ];

        $cancelled = Application::where('user_id', $user->id)
            ->where('campaign_id', $campaign->id)
            ->where('status', 'cancelled')
            ->first();

        if ($cancelled) {
            $cancelled->update($fields);
            // 前回応募時のLINEジョブ（打診等）をキャンセルして再応募後に残らないようにする
            LineMessageJob::where('application_id', $cancelled->id)
                ->whereIn('status', ['pending', 'sent'])
                ->update(['status' => 'canceled']);
            $application = $cancelled;
        } else {
            $application = Application::create(array_merge($fields, [
                'user_id'     => $user->id,
                'campaign_id' => $campaign->id,
            ]));
        }

        // 応募上限チェック → 上限到達で自動一時停止
        if ($campaign->capacity !== null) {
            $appCount = Application::where('campaign_id', $campaign->id)->count();
            if ($appCount >= $campaign->capacity) {
                $campaign->update(['status' => 'paused']);
            }
        }

        return redirect()->route('member.campaigns.complete')
            ->with('applied_pr_media', $campaign->pr_media);
    }

    public function complete(): \Illuminate\View\View
    {
        $prMedia = session('applied_pr_media');
        return view('member.campaigns.complete', compact('prMedia'));
    }
}
