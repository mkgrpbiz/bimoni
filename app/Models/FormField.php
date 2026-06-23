<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    protected $fillable = [
        'field_key', 'label', 'type', 'is_required', 'is_visible',
        'options', 'sort_order', 'is_system', 'maps_to',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_visible'  => 'boolean',
            'is_system'   => 'boolean',
            'options'     => 'array',
        ];
    }

    public function scopeVisible($query) { return $query->where('is_visible', true)->orderBy('sort_order'); }
}
