<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralPaymentStatus extends Model
{
    protected $fillable = ['agent_id', 'year', 'month', 'status'];

    public function agent() { return $this->belongsTo(Agent::class); }

    public static function getStatus(int $agentId, int $year, int $month): string
    {
        return static::where(['agent_id' => $agentId, 'year' => $year, 'month' => $month])
            ->value('status') ?? 'pending';
    }

    public static function markDone(int $agentId, int $year, int $month): void
    {
        static::updateOrCreate(
            ['agent_id' => $agentId, 'year' => $year, 'month' => $month],
            ['status' => 'done']
        );
    }

    public static function markPending(int $agentId, int $year, int $month): void
    {
        static::updateOrCreate(
            ['agent_id' => $agentId, 'year' => $year, 'month' => $month],
            ['status' => 'pending']
        );
    }
}
