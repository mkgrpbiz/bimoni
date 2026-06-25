<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationFormResponse;
use App\Models\Campaign;
use App\Models\CampaignBonus;
use App\Models\FormField;
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
            ->pluck('status', 'campaign_id');

        $now = now();
        $activeBonuses = CampaignBonus::with('campaign')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->get()
            ->keyBy('campaign_id');

        return view('member.campaigns.index', compact('campaigns', 'campaignsByType', 'appliedIds', 'activeBonuses'));
    }

    public function show(Campaign $campaign): View|RedirectResponse
    {
        if ($campaign->status !== 'published') {
            abort(404);
        }

        $user        = Auth::guard('liff')->user();
        $application = Application::where('user_id', $user->id)
            ->where('campaign_id', $campaign->id)
            ->first();
        $appFields = FormField::forType('application')->visible()->get();

        return view('member.campaigns.show', compact('campaign', 'application', 'appFields'));
    }

    public function apply(Request $request, Campaign $campaign): RedirectResponse
    {
        if ($campaign->status !== 'published') {
            abort(404);
        }

        $user = Auth::guard('liff')->user();

        // 重複応募チェック
        $exists = Application::where('user_id', $user->id)
            ->where('campaign_id', $campaign->id)
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

        $application = Application::create([
            'user_id'                 => $user->id,
            'campaign_id'             => $campaign->id,
            'status'                  => 'pending',
            'applied_at'              => $now,
            'bonus_amount'            => $activeBonus?->bonus_amount,
            'continuation_wish'       => $hasContinuation ? $request->continuation_wish : null,
            'purchase_available_times' => $request->purchase_available_times,
        ]);

        // 応募上限チェック → 上限到達で自動一時停止
        if ($campaign->capacity !== null) {
            $appCount = Application::where('campaign_id', $campaign->id)->count();
            if ($appCount >= $campaign->capacity) {
                $campaign->update(['status' => 'paused']);
            }
        }

        return redirect()->route('member.campaigns.show', $campaign)
            ->with('success', '応募が完了しました！');
    }
}
