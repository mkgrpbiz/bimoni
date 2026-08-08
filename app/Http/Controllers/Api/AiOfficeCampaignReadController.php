<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;

/**
 * Read-only bridge for AI OFFICE's 全体テンプレート (whole-campaign template)
 * feature: lets AI OFFICE clone an existing,正常に運用されている BIMONI
 * campaign's full field set into its own storage once, ahead of time —
 * never on a per-incoming-case basis (see AI_OFFICE連携 doc). Deliberately
 * does NOT touch Admin\CampaignController; this is a separate, additive,
 * read-only surface (same trade-off already accepted for
 * AiOfficeCampaignDraftController).
 *
 * course_settings_enabled/course_normal_name/course_normal_percentage and
 * the campaign_courses relation are intentionally excluded — コース指定設定
 * is out of scope for 全体テンプレート by explicit instruction.
 * gross_profit/cooperation_fee_formula/continuation_cooperation_fee_formula
 * are also excluded: these are values BIMONI recalculates server-side
 * (Admin\CampaignController::recalculateGrossProfit()/applyCooperationFormula(),
 * both private), so returning stale copies here would be misleading.
 * `category_id` is also excluded (2026-08-09) — effectively unused on the
 * BIMONI side today, so it was dropped from AI OFFICE's managed field set
 * entirely rather than adding a categories-list endpoint for it.
 *
 * This CLONEABLE_FIELDS list must be kept in manual sync with AI OFFICE's
 * `App\Support\CampaignFieldDefinitions::keys()` — separate repositories,
 * no shared code, so any field added/removed on one side needs the same
 * change made here by hand.
 */
class AiOfficeCampaignReadController extends Controller
{
    /**
     * @var list<string>
     */
    private const CLONEABLE_FIELDS = [
        'title', 'campaign_type', 'pr_media', 'thumbnail',
        'description', 'requirements', 'notes',
        'cancellation_info', 'cancellation_method', 'cancellation_phone',
        'cancellation_hours', 'cancellation_mypage_url', 'cancellation_email', 'cancellation_visible',
        'monitor_guide', 'link', 'monitor_video', 'monitor_video_thumbnail',
        'monitor_invite_message', 'monitor_end_message',
        'product_name', 'product_price',
        'cooperation_fee', 'continuation_cooperation_fee',
        'continuation_condition',
        'referral_fee', 'campaign_unit_price',
        'initial_purchase_fee', 'recurring_purchase_fee',
        'continuation_rate', 'closing_date', 'payment_timing',
        'collection_info', 'collection_requirement', 'collection_available', 'collection_count_judgment',
        'target_gender_ratio', 'target_male_ratio', 'target_female_ratio',
        'capacity', 'solicitation_target',
        'application_start_at', 'application_end_at',
    ];

    public function index(): JsonResponse
    {
        $campaigns = Campaign::query()
            ->select(['id', 'title', 'status', 'campaign_type', 'updated_at'])
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get();

        return response()->json(['campaigns' => $campaigns]);
    }

    public function show(Campaign $campaign): JsonResponse
    {
        $fields = [];
        foreach (self::CLONEABLE_FIELDS as $key) {
            $value = $campaign->{$key};
            $fields[$key] = $value instanceof \Illuminate\Support\Carbon ? $value->format('Y-m-d') : $value;
        }

        return response()->json([
            'id' => $campaign->id,
            'title' => $campaign->title,
            'fields' => $fields,
        ]);
    }
}
