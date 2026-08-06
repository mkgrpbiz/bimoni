<?php

namespace App\Services;

use App\Models\KanrikunContact;
use App\Models\KanrikunMessage;
use Illuminate\Support\Carbon;

/**
 * Ingests a single LINE webhook event for BIMONI管理君's channel: resolves
 * (or creates) the sending contact, stores the message idempotently on
 * line_message_id, then relays it to AI OFFICE. Group messages without a
 * source.userId (LINE doesn't always expose this) are not dropped — they're
 * bucketed under a per-group anonymous contact instead.
 */
class KanrikunMessageIngestService
{
    public function __construct(
        private KanrikunContentFetcher $contentFetcher,
        private KanrikunRelayService $relay,
    ) {}

    public function handle(array $event): void
    {
        if (($event['type'] ?? null) !== 'message') {
            return;
        }

        $message = $event['message'] ?? [];
        if (! isset($message['id']) || KanrikunMessage::where('line_message_id', $message['id'])->exists()) {
            return;
        }

        $contact = $this->resolveContact($event['source'] ?? []);
        if (! $contact) {
            return;
        }

        $attrs = [
            'kanrikun_contact_id' => $contact->id,
            'line_message_id' => $message['id'],
            'line_event_id' => $event['webhookEventId'] ?? null,
            'source_type' => $event['source']['type'] ?? 'user',
            'line_group_id' => $event['source']['groupId'] ?? $event['source']['roomId'] ?? null,
            'message_type' => $this->mapType($message['type'] ?? 'unsupported'),
            'line_sent_at' => Carbon::createFromTimestampMs($event['timestamp']),
            'raw_payload' => $event,
        ];

        $attrs = match ($message['type'] ?? null) {
            'text' => $attrs + ['text_body' => $message['text'] ?? null],
            'sticker' => $attrs + [
                'sticker_package_id' => $message['packageId'] ?? null,
                'sticker_id' => $message['stickerId'] ?? null,
            ],
            'image', 'file', 'video' => $attrs + $this->contentFetcher->fetchAndStore($message),
            default => $attrs,
        };

        $record = KanrikunMessage::create($attrs);

        $this->relay->push($record);
    }

    private function resolveContact(array $source): ?KanrikunContact
    {
        $lineUserId = $source['userId'] ?? null;

        if ($lineUserId) {
            $contact = KanrikunContact::firstOrCreate(
                ['line_user_id' => $lineUserId],
                ['first_seen_at' => now(), 'last_seen_at' => now()]
            );
            $contact->update(['last_seen_at' => now()]);

            return $contact;
        }

        $groupId = $source['groupId'] ?? $source['roomId'] ?? null;
        if (! $groupId) {
            // 1:1でuserIdもグループIDも取れないケース（想定外）。取りこぼしよりログを優先。
            return null;
        }

        $contact = KanrikunContact::firstOrCreate(
            ['line_group_id' => $groupId, 'is_anonymous_group_sender' => true],
            [
                'display_name' => 'グループ参加者（送信者不明）',
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]
        );
        $contact->update(['last_seen_at' => now()]);

        return $contact;
    }

    private function mapType(string $lineType): string
    {
        return in_array($lineType, ['text', 'image', 'file', 'sticker', 'video', 'audio', 'location'], true)
            ? $lineType
            : 'unsupported';
    }
}
