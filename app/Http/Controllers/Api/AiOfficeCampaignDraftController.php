<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Write bridge for AI OFFICE's 新着案件情報 workflow (Slice2 Phase4): creates
 * a status=draft Campaign from the minimal field set a human has already
 * confirmed in AI OFFICE. Deliberately does NOT call/extract
 * Admin\CampaignController — that controller is live, untested, and ~380
 * lines; this endpoint uses its own minimal validation instead of risking a
 * change there (accepted trade-off, see CLAUDE.md AI OFFICE連携 section).
 * `status` is always hardcoded to 'draft' regardless of any request value,
 * so this endpoint can never publish a campaign.
 */
class AiOfficeCampaignDraftController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'campaign_type' => 'required|in:experience,product,pr',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'monitor_guide' => 'nullable|string',
            'referral_fee' => 'nullable|integer|in:0,500,1000',
        ]);

        $campaign = Campaign::create([
            'title' => $validated['title'],
            'campaign_type' => $validated['campaign_type'],
            'status' => 'draft',
            'description' => $validated['description'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'monitor_guide' => $validated['monitor_guide'] ?? null,
            'referral_fee' => $validated['referral_fee'] ?? 0,
        ]);

        return response()->json([
            'campaign_id' => $campaign->id,
            'campaign_url' => route('admin.campaigns.edit', $campaign),
        ]);
    }
}
