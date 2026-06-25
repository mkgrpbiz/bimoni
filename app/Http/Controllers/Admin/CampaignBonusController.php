<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignBonus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignBonusController extends Controller
{
    public function index(): View
    {
        $bonuses   = CampaignBonus::with('campaign')->orderByDesc('start_at')->get();
        $campaigns = Campaign::where('status', 'published')->orderBy('title')->get();

        return view('admin.campaign_bonuses.index', compact('bonuses', 'campaigns'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'campaign_id'  => 'required|exists:campaigns,id',
            'bonus_amount' => 'required|integer|min:1',
            'start_at'     => 'required|date',
            'end_at'       => 'required|date|after:start_at',
        ]);

        CampaignBonus::create($request->only('campaign_id', 'bonus_amount', 'start_at', 'end_at'));

        return back()->with('success', 'キャンペーンを登録しました。');
    }

    public function destroy(CampaignBonus $campaignBonus): RedirectResponse
    {
        $campaignBonus->delete();
        return back()->with('success', 'キャンペーンを削除しました。');
    }
}
