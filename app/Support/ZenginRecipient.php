<?php

namespace App\Support;

use App\Models\User;

class ZenginRecipient
{
    public static function key(?User $user): ?string
    {
        if (!$user || !$user->bank_code || !$user->bank_account_number) {
            return null;
        }
        $name = self::normalizeName($user->bank_account_name);
        if ($name === '') {
            return null;
        }
        return self::bankCode($user) . '|' . self::branchCode($user) . '|' . self::account($user) . '|' . $name;
    }

    public static function bankCode(User $user): string
    {
        return str_pad(self::digitsOnly($user->bank_code), 4, '0', STR_PAD_LEFT);
    }

    public static function branchCode(User $user): string
    {
        return str_pad(self::digitsOnly($user->bank_branch_code), 3, '0', STR_PAD_LEFT);
    }

    public static function account(User $user): string
    {
        return str_pad(self::digitsOnly($user->bank_account_number), 7, '0', STR_PAD_LEFT);
    }

    public static function name(User $user): string
    {
        return self::normalizeName($user->bank_account_name);
    }

    public static function digitsOnly(?string $value): string
    {
        return preg_replace('/\D/', '', $value ?? '') ?? '';
    }

    // スプレッドシート運用時に実際に使っていたGASマクロと同じ正規化ロジック
    public static function normalizeName(?string $value): string
    {
        $s = trim($value ?? '');

        $s = str_replace(['　', ' '], '', $s);

        $s = preg_replace_callback('/[\x{FF21}-\x{FF3A}\x{FF41}-\x{FF5A}\x{FF10}-\x{FF19}]/u', function ($m) {
            return mb_chr(mb_ord($m[0], 'UTF-8') - 0xFEE0, 'UTF-8');
        }, $s);

        $s = str_replace(
            ['‐', '‑', '‒', '–', '—', '―', 'ー', '－', '−', '・', '（', '）', '．', '／'],
            ['ｰ', 'ｰ', 'ｰ', 'ｰ', 'ｰ', 'ｰ', 'ｰ', 'ｰ', 'ｰ', '･', '(', ')', '.', '/'],
            $s
        );

        $s = mb_convert_kana($s, 'k', 'UTF-8');

        return preg_replace('/[^0-9A-Za-z\x{FF66}-\x{FF9F}().\/-]/u', '', $s) ?? '';
    }
}
