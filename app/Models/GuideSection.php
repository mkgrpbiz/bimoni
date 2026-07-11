<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuideSection extends Model
{
    protected $fillable = [
        'guide_page_id', 'title', 'intro_text', 'sort_order', 'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function page()
    {
        return $this->belongsTo(GuidePage::class, 'guide_page_id');
    }

    public function notes()
    {
        return $this->hasMany(GuideNote::class)->orderBy('sort_order');
    }

    public function steps()
    {
        return $this->hasMany(GuideStep::class)->orderBy('sort_order');
    }
}
