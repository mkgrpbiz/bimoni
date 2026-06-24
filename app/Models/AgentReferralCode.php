<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentReferralCode extends Model
{
    protected $fillable = ['agent_id', 'code', 'label'];

    protected static function booted(): void
    {
        static::creating(function (self $arc) {
            if (empty($arc->code)) {
                do {
                    $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
                } while (static::where('code', $code)->exists());
                $arc->code = $code;
            }
        });
    }

    public function agent() { return $this->belongsTo(Agent::class); }

    public function users()
    {
        return User::where('referred_by_code', $this->code);
    }
}
