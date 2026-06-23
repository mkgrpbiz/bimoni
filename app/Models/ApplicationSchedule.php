<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationSchedule extends Model
{
    protected $fillable = [
        'application_id', 'proposed_dates', 'confirmed_datetime',
        'status', 'proposed_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'proposed_dates'     => 'array',
            'confirmed_datetime' => 'datetime',
        ];
    }

    public function application() { return $this->belongsTo(Application::class); }
    public function proposedBy()  { return $this->belongsTo(Admin::class, 'proposed_by'); }
}
