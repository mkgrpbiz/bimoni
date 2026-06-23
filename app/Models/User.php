<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'line_user_id',
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
}
