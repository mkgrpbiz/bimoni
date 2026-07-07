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
                $next = $max ? (int) substr($max, 3) + 1 : 10001;
                $user->bimoni_user_id = 'BMN' . str_pad($next, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    protected $fillable = [
        'bimoni_user_id',
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
        'transfer_registered_at',
        'new_register_confirmed_at',
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
            'transfer_registered_at' => 'datetime',
            'new_register_confirmed_at' => 'datetime',
        ];
    }

    public function applications()   { return $this->hasMany(Application::class); }
    public function points()         { return $this->hasMany(Point::class); }
    public function pointExchanges() { return $this->hasMany(PointExchange::class); }
    public function monitorReports() { return $this->hasMany(MonitorReport::class); }
}
