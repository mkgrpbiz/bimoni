<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    protected $fillable = [
        'form_type', 'field_key', 'label', 'description', 'type', 'is_required', 'is_visible',
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
    public function scopeForType($query, string $type) { return $query->where('form_type', $type); }

    public static function generateKey(): string
    {
        do {
            $key = 'f_' . \Illuminate\Support\Str::random(8);
        } while (static::where('field_key', $key)->exists());
        return $key;
    }
}
