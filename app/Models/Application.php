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
            'proposal_answered_at'    => 'datetime',
            'proposal_sent_at'        => 'datetime',
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

    // 48時間制限チェック（他案件のcompleted_at から計算）
    // 引数: 同一ユーザーの他案件応募コレクション
    public function getUnlockAt(?\Illuminate\Support\Collection $otherApplications = null): ?Carbon
    {
        if ($otherApplications === null) {
            return null;
        }
        $latest = $otherApplications
            ->whereNotNull('completed_at')
            ->sortByDesc('completed_at')
            ->first();

        if (!$latest || !$latest->completed_at) {
            return null;
        }

        $unlock = $latest->completed_at->addHours(48);
        return $unlock->isFuture() ? $unlock : null;
    }

    // ロック状態（打診不可）かどうか
    public function isLocked(?\Illuminate\Support\Collection $otherApplications = null): bool
    {
        // 自身が打診中・予約中・実施確認中
        if (in_array($this->status, ['line_contacted', 'scheduled', 'confirming'])) {
            return true;
        }
        // 他案件でのロック
        if ($otherApplications) {
            foreach ($otherApplications as $other) {
                if (in_array($other->status, ['line_contacted', 'scheduled', 'confirming'])) {
                    return true;
                }
                if ($other->completed_at && $other->completed_at->diffInHours(now()) < 48) {
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
            'selected'       => '当選',
            'rejected'       => '落選',
            'line_contacted' => '打診中',
            'scheduled'      => '予約中',
            'confirming'     => '実施確認中',
            'completed'      => '実施完了',
            'reported'       => '報告済',
            'approved'       => '承認済',
            'point_granted'  => '協力金付与済',
            'cancelled'      => 'キャンセル',
            default          => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending'        => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
            'selected'       => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
            'rejected'       => 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300',
            'line_contacted' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
            'scheduled'      => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300',
            'confirming'     => 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
            'completed'      => 'bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-300',
            'reported'       => 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
            'approved'       => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
            'point_granted'  => 'bg-green-200 text-green-800 dark:bg-green-800 dark:text-green-200',
            'cancelled'      => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
            default          => 'bg-gray-100 text-gray-500',
        };
    }
}
