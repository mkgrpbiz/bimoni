<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuideNote extends Model
{
    protected $fillable = [
        'guide_section_id', 'heading', 'body', 'style', 'sort_order',
    ];

    public function section()
    {
        return $this->belongsTo(GuideSection::class, 'guide_section_id');
    }

    // 本文を1行1項目として配列で返す（箇条書き表示用）
    public function getLinesAttribute(): array
    {
        return collect(explode("\n", $this->body))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
