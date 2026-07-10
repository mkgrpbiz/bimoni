<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EndCancelSetting extends Model
{
    protected $fillable = ['send_start_hour', 'send_end_hour', 'message_template'];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'send_start_hour'  => 9,
            'send_end_hour'    => 22,
            'message_template' => "{{商品名}}のモニターご案内につきまして、誠に申し訳ございませんが、キャンペーン終了に伴い今回のご案内をキャンセルさせていただくこととなりました。\nまたの機会がございましたら、ぜひご参加をご検討ください。",
        ]);
    }
}
