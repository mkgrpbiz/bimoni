<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Campaign;
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
            ->latest()
            ->get();

        $appliedIds = Application::where('user_id', $user->id)
            ->whereIn('campaign_id', $campaigns->pluck('id'))
            ->pluck('status', 'campaign_id');

        return view('member.campaigns.index', compact('campaigns', 'appliedIds'));
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

        return view('member.campaigns.show', compact('campaign', 'application'));
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

        Application::create([
            'user_id'     => $user->id,
            'campaign_id' => $campaign->id,
            'status'      => 'pending',
            'applied_at'  => now(),
        ]);

        return redirect()->route('member.campaigns.show', $campaign)
            ->with('success', '応募が完了しました！');
    }
}
