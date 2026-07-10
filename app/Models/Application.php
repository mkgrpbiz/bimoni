<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'user_id', 'campaign_id', 'status', 'line_contact_status',
        'applied_at', 'selected_at', 'line_contacted_at', 'sounded_at',
        'schedule_confirmed_at', 'reserved_at', 'monitoring_confirmed_at',
        'completed_at', 'reported_at', 'approved_at',
        'invited_at', 'invited_end_at', 'continuation_invite_date',
        'proposal_token', 'proposal_answered_at', 'proposal_answer', 'proposal_sent_at',
        'notes', 'imported_from',
        'continuation_wish', 'purchase_available_times',
        'continuation_token', 'continuation_sent_at', 'continuation_response', 'continuation_responded_at',
        'form_image',
        'bonus_amount',
        'is_re_proposal',
    ];

    protected function casts(): array
    {
        return [
            'applied_at'              => 'datetime',
            'selected_at'             => 'datetime',
            'line_contacted_at'       => 'datetime',
            'sounded_at'              => 'datetime',
            'schedule_confirmed_at'   => 'datetime',
            'reserved_at'             => 'datetime',
            'monitoring_confirmed_at' => 'datetime',
            'completed_at'            => 'datetime',
            'reported_at'             => 'datetime',
            'approved_at'             => 'datetime',
            'invited_at'              => 'datetime',
            'invited_end_at'          => 'datetime',
            'continuation_invite_date' => 'date',
            'proposal_answered_at'        => 'datetime',
            'proposal_sent_at'            => 'datetime',
            'continuation_sent_at'        => 'datetime',
            'continuation_responded_at'   => 'datetime',
            'purchase_available_times'  => 'array',
            'is_re_proposal'            => 'boolean',
        ];
    }

    public function user()            { return $this->belongsTo(User::class); }
    public function campaign()        { return $this->belongsTo(Campaign::class); }
    public function schedules()       { return $this->hasMany(ApplicationSchedule::class); }
    public function report()          { return $this->hasOne(MonitorReport::class); }
    public function statusLogs()      { return $this->hasMany(ApplicationStatusLog::class)->orderBy('created_at'); }
    public function lineMessageJobs() { return $this->hasMany(LineMessageJob::class); }

    // ステータス変更を記録し、関連タイムスタンプを自動セット
    public function changeStatus(string $newStatus, ?int $adminId = null, ?string $memo = null): void
    {
        $oldStatus = $this->status;
        $data = ['status' => $newStatus];

        $data = array_merge($data, match($newStatus) {
            'selected'      => ['selected_at' => now()],
            'line_contacted' => ['line_contacted_at' => now(), 'sounded_at' => now(), 'line_contact_status' => 'sent'],
            'scheduled'     => ['reserved_at' => now()],
            'confirming'    => ['monitoring_confirmed_at' => now()],
            'completed'     => ['completed_at' => now()],
            'reported'      => ['reported_at' => now()],
            'approved'      => ['approved_at' => now()],
            default         => [],
        });

        $this->update($data);

        ApplicationStatusLog::create([
            'application_id' => $this->id,
            'from_status'    => $oldStatus,
            'to_status'      => $newStatus,
            'changed_by'     => $adminId,
            'memo'           => $memo,
        ]);
    }

    // PRモニター+IF媒体かどうか（ロック・48h制限の対象外判定）
    public function isPrIfCampaign(): bool
    {
        return $this->campaign?->campaign_type === 'pr' && $this->campaign?->pr_media === 'IF';
    }

    // 次回案内可能日時（案内終了時間+48h）※表示用・案内時間バリデーション用
    public function getEarliestNextInviteAt(?\Illuminate\Support\Collection $otherApplications = null): ?Carbon
    {
        // PR+IFは48h制限なし
        if ($this->isPrIfCampaign()) {
            return null;
        }

        if ($otherApplications === null) {
            return null;
        }

        $latest = $otherApplications
            ->filter(fn($a) => !$a->isPrIfCampaign())
            ->filter(fn($a) => $a->invited_end_at || $a->invited_at)
            ->sortByDesc(fn($a) => ($a->invited_end_at ?? $a->invited_at)->timestamp)
            ->first();

        $ref = $latest?->invited_end_at ?? $latest?->invited_at;
        if ($ref) {
            $earliest = $ref->copy()->addHours(48);
            if ($earliest->isFuture()) return $earliest;
        }
        return null;
    }

    // 後方互換エイリアス
    public function getUnlockAt(?\Illuminate\Support\Collection $otherApplications = null): ?Carbon
    {
        return $this->getEarliestNextInviteAt($otherApplications);
    }

    // ロック状態（打診不可）= 他案件が進行中のときのみ。48h制限は打診ではなく案内時間に適用
    public function isLocked(?\Illuminate\Support\Collection $otherApplications = null): bool
    {
        // PR+IFは他案件状況のロック対象外
        if ($this->isPrIfCampaign()) {
            return false;
        }

        // 自身が打診中・予約中・実施確認中
        if (in_array($this->status, ['line_contacted', 'scheduled', 'confirming'])) {
            return true;
        }
        // 他案件でステータスが進行中ならロック（PR+IFは除外）
        if ($otherApplications) {
            foreach ($otherApplications as $other) {
                if ($other->isPrIfCampaign()) continue;
                if (in_array($other->status, ['line_contacted', 'scheduled', 'confirming'])) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending'        => '応募',
            'line_contacted' => '打診中',
            'scheduled'      => '予約中',
            'confirming'     => '実施確認中',
            'completed'      => '実施完了',
            'cancelled'      => 'キャンセル',
            'reported',
            'approved',
            'point_granted'  => '実施完了',
            default          => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending'        => 'bg-yellow-500 text-white',
            'line_contacted' => 'bg-purple-500 text-white',
            'scheduled'      => 'bg-indigo-500 text-white',
            'confirming'     => 'bg-orange-500 text-white',
            'completed',
            'reported',
            'approved',
            'point_granted'  => 'bg-teal-500 text-white',
            'cancelled'      => 'bg-gray-500 text-white',
            default          => 'bg-gray-500 text-white',
        };
    }
}
