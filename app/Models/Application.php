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
        'course_id',
        'lottery_order',
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
    public function course()          { return $this->belongsTo(CampaignCourse::class, 'course_id'); }
    public function schedules()       { return $this->hasMany(ApplicationSchedule::class); }
    public function report()          { return $this->hasOne(MonitorReport::class); }
    public function statusLogs()      { return $this->hasMany(ApplicationStatusLog::class)->orderBy('created_at'); }
    public function lineMessageJobs() { return $this->hasMany(LineMessageJob::class); }

    // statusが'completed'になった時点（管理画面の手動変更・CSVインポート・成果の手動追加、いずれの経路でも）で
    // 重複禁止商品側の未完了の応募を自動キャンセルする。changeStatus()経由だけでなく直接update()/create()する
    // 箇所（ImportService::importApplications()・ManualAdditionController::store()）もあるため、
    // 特定のコントローラ内だけでなくモデルイベントで一括フックする（2026-10-13追加）
    protected static function booted(): void
    {
        // 案内順を先着順（applied_at）ではなく完全ランダム（抽選）にするための値を、
        // 応募作成時に1回だけ割り当てる（2026-10-14追加）。毎回表示をシャッフルすると
        // スタッフ側の対応済み/未対応の管理が崩れるため、応募時点で値を固定する
        static::creating(function (Application $application) {
            if ($application->lottery_order === null) {
                $application->lottery_order = mt_rand() / mt_getrandmax();
            }
        });

        static::created(function (Application $application) {
            if ($application->status === 'completed') {
                $application->cancelConflictingDuplicateApplications();
            }
        });

        static::updated(function (Application $application) {
            if ($application->wasChanged('status') && $application->status === 'completed') {
                $application->cancelConflictingDuplicateApplications();
            }
        });
    }

    // 重複禁止商品として設定されている相手案件のうち、このユーザーがまだ実施完了していない
    // 応募（打診中・予約中・実施確認中など進行中のもの）を自動キャンセルする
    public function cancelConflictingDuplicateApplications(): void
    {
        $this->loadMissing('campaign');
        $prohibitedIds = $this->campaign?->duplicateProhibitedCampaigns->pluck('id') ?? collect();
        if ($prohibitedIds->isEmpty()) {
            return;
        }

        $conflicts = static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->whereIn('campaign_id', $prohibitedIds)
            ->whereNotIn('status', ['cancelled', 'completed', 'reported', 'approved', 'point_granted'])
            ->with('campaign:id,title')
            ->get();

        foreach ($conflicts as $conflict) {
            $conflict->lineMessageJobs()->where('status', 'pending')->update(['status' => 'canceled']);
            $conflict->changeStatus(
                'cancelled',
                null,
                "重複禁止商品「{$this->campaign->title}」が実施完了したため自動キャンセル"
            );
        }
    }

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

    // 案内日時ラベル（invited_at〜invited_end_at）。PR打診は日を跨ぐことが多いため、
    // 日付が変わる場合は終了側にも日付を表示する
    public function invitedLabel(string $dateFormat = 'n月j日'): ?string
    {
        if (!$this->invited_at) return null;

        $label = $this->invited_at->format("{$dateFormat} H:i");
        if ($this->invited_end_at) {
            $label .= '〜' . (
                $this->invited_end_at->isSameDay($this->invited_at)
                    ? $this->invited_end_at->format('H:i')
                    : $this->invited_end_at->format("{$dateFormat} H:i")
            );
        }
        return $label;
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
