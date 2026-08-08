<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Write bridge for AI OFFICE's 新着案件情報 workflow. Creates a status=draft
 * Campaign from whatever fields AI OFFICE's human-confirmed下書き contains.
 * Deliberately does NOT call/extract Admin\CampaignController — that
 * controller is live, untested, and ~380 lines; this endpoint uses its own
 * validation instead of risking a change there (accepted trade-off, see
 * CLAUDE.md AI OFFICE連携 section). `status` is always hardcoded to 'draft'
 * regardless of any request value, so this endpoint can never publish a
 * campaign.
 *
 * 2026-08-08(全体テンプレート機能): originally accepted only 6 fields; now
 * accepts the same field set AiOfficeCampaignReadController exposes for
 * cloning, so a 全体テンプレート applied in AI OFFICE actually reaches
 * BIMONI at draft-registration time instead of being re-typed by hand.
 * Requests that still send only the original 6 fields keep working
 * unchanged (all the added fields are nullable).
 *
 * gross_profit/cooperation_fee_formula/continuation_cooperation_fee_formula
 * are intentionally NOT accepted here — those are values BIMONI computes
 * server-side (Admin\CampaignController's private
 * recalculateGrossProfit()/applyCooperationFormula()); a drafted campaign
 * gets them correctly filled in the first time staff open and save it in
 * the normal edit screen. course_settings_enabled and the course_ prefixed
 * fields / courses relation are also out of scope (コース指定設定 is
 * excluded from 全体テンプレート by design). `category_id` is also not
 * accepted (2026-08-09, unused on the BIMONI side today).
 *
 * 2026-08-09(解約方法管理下書き): every AI-OFFICE-created campaign is always
 * created with cancellation_draft=true, regardless of what AI OFFICE sends
 * for cancellation_visible — a campaign AI OFFICE just drafted hasn't been
 * reviewed by BIMONI staff on the 解約方法管理 screen yet, so it always
 * starts in that screen's draft bucket for them to check before publishing.
 */
class AiOfficeCampaignDraftController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'campaign_type' => 'required|in:experience,product,pr',
            'pr_media' => 'nullable|in:AD,IF,LINE,monitor',
            'thumbnail' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'notes' => 'nullable|string',
            'cancellation_info' => 'nullable|string',
            'cancellation_method' => 'nullable|string',
            'cancellation_phone' => 'nullable|string|max:255',
            'cancellation_hours' => 'nullable|string|max:255',
            'cancellation_mypage_url' => 'nullable|string|max:255',
            'cancellation_email' => 'nullable|string|max:255',
            'cancellation_visible' => 'nullable|boolean',
            'monitor_guide' => 'nullable|string',
            'link' => 'nullable|string|max:500',
            'monitor_video' => 'nullable|string|max:255',
            'monitor_video_thumbnail' => 'nullable|string|max:255',
            'monitor_invite_message' => 'nullable|string',
            'monitor_end_message' => 'nullable|string',
            'product_name' => 'nullable|string|max:255',
            'product_price' => 'nullable|integer|min:0',
            'cooperation_fee' => 'nullable|integer|min:0',
            'continuation_cooperation_fee' => 'nullable|integer|min:0',
            'continuation_condition' => 'nullable|in:2回前提,3回前提',
            'referral_fee' => 'nullable|integer|in:0,500,1000',
            'campaign_unit_price' => 'nullable|integer|min:0',
            'initial_purchase_fee' => 'nullable|integer|min:0',
            'recurring_purchase_fee' => 'nullable|integer|min:0',
            'continuation_rate' => 'nullable|numeric|min:0|max:100',
            'closing_date' => 'nullable|in:20日,25日,月末',
            'payment_timing' => 'nullable|in:翌月末,翌々月末',
            'collection_info' => 'nullable|string',
            'collection_requirement' => 'nullable|in:回収必須,回収不要',
            'collection_available' => 'nullable|boolean',
            'collection_count_judgment' => 'nullable|integer|in:1,2,3',
            'target_gender_ratio' => 'nullable|string|max:50',
            'target_male_ratio' => 'nullable|integer|min:0|max:100',
            'target_female_ratio' => 'nullable|integer|min:0|max:100',
            'capacity' => 'nullable|integer|min:1',
            'solicitation_target' => 'nullable|integer|min:0',
            'application_start_at' => 'nullable|date',
            'application_end_at' => 'nullable|date|after_or_equal:application_start_at',
        ]);

        $campaign = Campaign::create([...$validated, 'status' => 'draft', 'cancellation_draft' => true]);

        return response()->json([
            'campaign_id' => $campaign->id,
            'campaign_url' => route('admin.campaigns.edit', $campaign),
        ]);
    }
}
