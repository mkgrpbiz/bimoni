<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'user_id', 'campaign_id', 'status', 'line_contact_status',
        'applied_at', 'selected_at', 'line_contacted_at', 'schedule_confirmed_at',
        'completed_at', 'reported_at', 'approved_at', 'notes', 'imported_from',
    ];

    protected function casts(): array
    {
        return [
            'applied_at'            => 'datetime',
            'selected_at'           => 'datetime',
            'line_contacted_at'     => 'datetime',
            'schedule_confirmed_at' => 'datetime',
            'completed_at'          => 'datetime',
            'reported_at'           => 'datetime',
            'approved_at'           => 'datetime',
        ];
    }

    public function user()      { return $this->belongsTo(User::class); }
    public function campaign()  { return $this->belongsTo(Campaign::class); }
    public function schedules() { return $this->hasMany(ApplicationSchedule::class); }
    public function report()    { return $this->hasOne(MonitorReport::class); }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending'        => '審査中',
            'selected'       => '当選',
            'rejected'       => '落選',
            'line_contacted' => 'LINE案内済',
            'scheduled'      => '日程確定',
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
            'completed'      => 'bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-300',
            'reported'       => 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
            'approved'       => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
            'point_granted'  => 'bg-green-200 text-green-800 dark:bg-green-800 dark:text-green-200',
            'cancelled'      => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
            default          => 'bg-gray-100 text-gray-500',
        };
    }
}
