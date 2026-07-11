<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuidePage extends Model
{
    protected $fillable = [
        'slug', 'title', 'hero_image', 'cta_label', 'cta_url', 'sort_order', 'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function sections()
    {
        return $this->hasMany(GuideSection::class)->orderBy('sort_order');
    }
}
