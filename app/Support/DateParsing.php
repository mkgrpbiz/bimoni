<?php

namespace App\Support;

use Carbon\Carbon;

class DateParsing
{
    // "Y-m"形式のクエリパラメータ（月選択フォーム等）をCarbonの月初日に変換する。
    // Carbon::createFromFormat('Y-m', ...)は日付部分を「今日の日」で補完するため、
    // 31日に30日までの月（9月等）を指定すると翌月に繰り上がってしまうバグがある
    // （例: 10/31に'2026-09'を渡すと2026-10-01になる。本番で代理店ポータルの報告管理が
    // 9月だけ表示できない障害として発覚、2026-10-31修正）。年月を分けてcreateFromDateで
    // 1日固定にすることでこの繰り上がりを防ぐ
    public static function monthStart(?string $raw): Carbon
    {
        if ($raw && preg_match('/^(\d{4})-(\d{1,2})$/', $raw, $m)) {
            return Carbon::createFromDate((int)$m[1], (int)$m[2], 1)->startOfMonth();
        }
        return Carbon::now()->startOfMonth();
    }
}
