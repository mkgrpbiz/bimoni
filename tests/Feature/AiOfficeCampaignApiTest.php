<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignCourse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiOfficeCampaignApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ai_office.token' => 'test-token']);
    }

    public function test_campaigns_index_requires_a_valid_token(): void
    {
        $this->getJson('/api/ai-office/bimoni/campaigns')->assertStatus(401);
        $this->withToken('wrong')->getJson('/api/ai-office/bimoni/campaigns')->assertStatus(401);
    }

    public function test_campaigns_index_lists_campaigns(): void
    {
        Campaign::create(['title' => '案件A', 'campaign_type' => 'experience', 'status' => 'published']);
        Campaign::create(['title' => '案件B', 'campaign_type' => 'product', 'status' => 'draft']);

        $response = $this->withToken('test-token')->getJson('/api/ai-office/bimoni/campaigns');

        $response->assertOk();
        $titles = collect($response->json('campaigns'))->pluck('title');
        $this->assertCount(2, $titles);
        $this->assertTrue($titles->contains('案件A'));
        $this->assertTrue($titles->contains('案件B'));
    }

    public function test_campaigns_show_returns_full_cloneable_field_set_and_excludes_course_settings(): void
    {
        $campaign = Campaign::create([
            'title' => '購入モニター標準',
            'campaign_type' => 'experience',
            'status' => 'published',
            'description' => '案件内容の説明文',
            'referral_fee' => 500,
            'initial_purchase_fee' => 3000,
            'course_settings_enabled' => true,
            'course_normal_name' => '通常コース',
            'course_normal_percentage' => 80,
        ]);
        CampaignCourse::create([
            'campaign_id' => $campaign->id,
            'name' => '定期コース',
            'initial_purchase_fee' => 2000,
            'course_type' => '継続',
            'percentage' => 20,
        ]);

        $response = $this->withToken('test-token')->getJson("/api/ai-office/bimoni/campaigns/{$campaign->id}");

        $response->assertOk();
        $fields = $response->json('fields');

        $this->assertSame('案件内容の説明文', $fields['description']);
        $this->assertSame(500, $fields['referral_fee']);
        $this->assertSame(3000, $fields['initial_purchase_fee']);

        // コース指定設定は複製対象から除外（全体テンプレート機能の明示的なスコープ外）
        $this->assertArrayNotHasKey('course_settings_enabled', $fields);
        $this->assertArrayNotHasKey('course_normal_name', $fields);
        $this->assertArrayNotHasKey('course_normal_percentage', $fields);
        $this->assertArrayNotHasKey('courses', $fields);

        // BIMONI側で再計算される値も含めない
        $this->assertArrayNotHasKey('gross_profit', $fields);
        $this->assertArrayNotHasKey('cooperation_fee_formula', $fields);
    }

    public function test_draft_endpoint_still_accepts_the_original_six_field_payload(): void
    {
        $response = $this->withToken('test-token')->postJson('/api/ai-office/bimoni/campaigns/draft', [
            'title' => '新規案件',
            'campaign_type' => 'experience',
            'description' => '説明',
            'notes' => '注意事項',
            'monitor_guide' => '案内文',
            'referral_fee' => 500,
        ]);

        $response->assertOk();
        $campaign = Campaign::findOrFail($response->json('campaign_id'));
        $this->assertSame('draft', $campaign->status);
        $this->assertSame(500, $campaign->referral_fee);
    }

    public function test_draft_endpoint_accepts_the_expanded_field_set_from_a_campaign_template(): void
    {
        $response = $this->withToken('test-token')->postJson('/api/ai-office/bimoni/campaigns/draft', [
            'title' => '全体テンプレート由来の案件',
            'campaign_type' => 'product',
            'initial_purchase_fee' => 4000,
            'recurring_purchase_fee' => 2000,
            'collection_requirement' => '回収必須',
            'collection_available' => true,
            'target_male_ratio' => 30,
            'target_female_ratio' => 70,
            'monitor_invite_message' => '案内メッセージ',
        ]);

        $response->assertOk();
        $campaign = Campaign::findOrFail($response->json('campaign_id'));
        $this->assertSame('draft', $campaign->status);
        $this->assertSame(4000, $campaign->initial_purchase_fee);
        $this->assertSame('回収必須', $campaign->collection_requirement);
        $this->assertTrue($campaign->collection_available);
        $this->assertSame(30, $campaign->target_male_ratio);
    }

    public function test_draft_endpoint_ignores_course_and_computed_fields_even_if_sent(): void
    {
        $response = $this->withToken('test-token')->postJson('/api/ai-office/bimoni/campaigns/draft', [
            'title' => '不正な項目を含む案件',
            'campaign_type' => 'experience',
            'course_settings_enabled' => true,
            'gross_profit' => 99999,
        ]);

        $response->assertOk();
        $campaign = Campaign::findOrFail($response->json('campaign_id'));
        $this->assertFalse((bool) $campaign->course_settings_enabled);
        $this->assertNull($campaign->gross_profit);
    }
}
