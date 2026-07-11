<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuideStep extends Model
{
    protected $fillable = [
        'guide_section_id', 'title', 'description', 'sub_text', 'image', 'sort_order', 'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function section()
    {
        return $this->belongsTo(GuideSection::class, 'guide_section_id');
    }
}
