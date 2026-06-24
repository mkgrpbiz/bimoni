<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (empty($user->bimoni_user_id)) {
                $max = static::whereNotNull('bimoni_user_id')
                    ->orderByDesc('bimoni_user_id')
                    ->value('bimoni_user_id');
                $next = $max ? (int) substr($max, 3) + 1 : 1001;
                $user->bimoni_user_id = 'BMN' . str_pad($next, 8, '0', STR_PAD_LEFT);
            }
            if (empty($user->referral_code)) {
                do {
                    $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
                } while (static::where('referral_code', $code)->exists());
                $user->referral_code = $code;
            }
        });
    }

    protected $fillable = [
        'bimoni_user_id',
        'referral_code',
        'referred_by_code',
        'line_user_id',
        'line_display_name',
        'erme_respondent_id',
        'name',
        'name_kana',
        'gender',
        'birthdate',
        'area',
        'phone',
        'email',
        'available_times',
        'wants_continuation',
        'point_balance',
        'status',
        'profile_completed_at',
        'imported_from',
        'bank_name',
        'bank_code',
        'bank_branch_name',
        'bank_branch_code',
        'bank_account_type',
        'bank_account_number',
        'bank_account_name',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'available_times'     => 'array',
            'birthdate'           => 'date',
            'profile_completed_at' => 'datetime',
        ];
    }

    public function applications()   { return $this->hasMany(Application::class); }
    public function points()         { return $this->hasMany(Point::class); }
    public function pointExchanges() { return $this->hasMany(PointExchange::class); }
    public function monitorReports() { return $this->hasMany(MonitorReport::class); }
    public function referrals()      { return $this->hasMany(self::class, 'referred_by_code', 'referral_code'); }
}
