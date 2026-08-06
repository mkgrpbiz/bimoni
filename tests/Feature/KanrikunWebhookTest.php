<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KanrikunWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function textEventPayload(string $messageId = 'msg-1', ?string $userId = 'U-test-1'): array
    {
        $source = $userId ? ['type' => 'user', 'userId' => $userId] : ['type' => 'group', 'groupId' => 'G-test-1'];

        return [
            'destination' => 'xxx',
            'events' => [[
                'type' => 'message',
                'timestamp' => now()->valueOf(),
                'webhookEventId' => 'evt-'.$messageId,
                'source' => $source,
                'message' => ['id' => $messageId, 'type' => 'text', 'text' => '新規案件お願いします.'],
            ]],
        ];
    }

    private function signedPost(array $payload, ?string $secret = 'test-secret')
    {
        config(['services.kanrikun.channel_secret' => $secret]);
        $body = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));

        return $this->call('POST', '/webhooks/kanrikun', [], [], [], [
            'HTTP_X-Line-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    public function test_rejects_request_with_missing_signature(): void
    {
        config(['services.kanrikun.channel_secret' => 'test-secret']);

        $response = $this->postJson('/webhooks/kanrikun', $this->textEventPayload());

        $response->assertStatus(401);
    }

    public function test_rejects_request_with_invalid_signature(): void
    {
        config(['services.kanrikun.channel_secret' => 'test-secret']);
        $body = json_encode($this->textEventPayload());
        $wrongSignature = base64_encode(hash_hmac('sha256', $body, 'wrong-secret', true));

        $response = $this->call('POST', '/webhooks/kanrikun', [], [], [], [
            'HTTP_X-Line-Signature' => $wrongSignature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $response->assertStatus(401);
    }

    public function test_accepts_valid_signed_text_message_and_stores_it(): void
    {
        $response = $this->signedPost($this->textEventPayload());

        $response->assertOk();
        $this->assertDatabaseHas('kanrikun_messages', [
            'line_message_id' => 'msg-1',
            'text_body' => '新規案件お願いします.',
        ]);
        $this->assertDatabaseHas('kanrikun_contacts', ['line_user_id' => 'U-test-1']);
    }

    public function test_duplicate_line_message_id_is_not_stored_twice(): void
    {
        $payload = $this->textEventPayload();

        $this->signedPost($payload)->assertOk();
        $this->signedPost($payload)->assertOk();

        $this->assertDatabaseCount('kanrikun_messages', 1);
    }

    public function test_image_message_triggers_content_fetch_and_relay(): void
    {
        Http::fake([
            'api-data.line.me/*' => Http::response('fake-binary', 200, ['Content-Type' => 'image/jpeg']),
            'office.mkgrp.biz/*' => Http::response(['status' => 'ok'], 200),
        ]);
        Storage::fake('public');
        config([
            'services.kanrikun.channel_access_token' => 'token',
            'services.kanrikun.relay_url' => 'https://office.mkgrp.biz/api/kanrikun/messages',
            'services.kanrikun.relay_token' => 'x',
        ]);

        $payload = [
            'destination' => 'xxx',
            'events' => [[
                'type' => 'message',
                'timestamp' => now()->valueOf(),
                'webhookEventId' => 'evt-msg-2',
                'source' => ['type' => 'user', 'userId' => 'U-test-2'],
                'message' => ['id' => 'msg-2', 'type' => 'image'],
            ]],
        ];

        $this->signedPost($payload)->assertOk();

        $this->assertDatabaseHas('kanrikun_messages', ['line_message_id' => 'msg-2', 'message_type' => 'image']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'office.mkgrp.biz'));
    }

    public function test_relay_failure_does_not_affect_webhook_response(): void
    {
        Http::fake(['office.mkgrp.biz/*' => Http::response([], 500)]);
        config([
            'services.kanrikun.relay_url' => 'https://office.mkgrp.biz/api/kanrikun/messages',
            'services.kanrikun.relay_token' => 'x',
        ]);

        $response = $this->signedPost($this->textEventPayload());

        $response->assertOk();
        $this->assertDatabaseHas('kanrikun_messages', [
            'line_message_id' => 'msg-1',
            'relayed_to_ai_office_at' => null,
        ]);
    }

    public function test_retry_command_resends_unrelayed_messages(): void
    {
        Http::fake(['office.mkgrp.biz/*' => Http::response([], 500)]);
        config([
            'services.kanrikun.relay_url' => 'https://office.mkgrp.biz/api/kanrikun/messages',
            'services.kanrikun.relay_token' => 'x',
        ]);
        $this->signedPost($this->textEventPayload())->assertOk();
        $this->assertDatabaseHas('kanrikun_messages', ['relayed_to_ai_office_at' => null]);

        Http::fake(['office.mkgrp.biz/*' => Http::response(['status' => 'ok'], 200)]);
        Artisan::call('kanrikun:retry-relay');

        $this->assertDatabaseMissing('kanrikun_messages', ['relayed_to_ai_office_at' => null]);
    }

    public function test_group_message_without_user_id_is_stored_under_anonymous_contact(): void
    {
        $payload = $this->textEventPayload(userId: null);

        $response = $this->signedPost($payload);

        $response->assertOk();
        $this->assertDatabaseHas('kanrikun_messages', ['line_message_id' => 'msg-1']);
        $this->assertDatabaseHas('kanrikun_contacts', [
            'line_group_id' => 'G-test-1',
            'is_anonymous_group_sender' => true,
        ]);
    }
}
