<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class UserMatcher
{
    // 名前・フリガナはスペース除去して正規化（インポートデータと登録データのスペース有無の差を吸収）
    private static function normalize(string $key, string $value): string
    {
        if (in_array($key, ['name', 'name_kana'], true)) {
            return preg_replace('/[\s\x{3000}]+/u', '', $value);
        }
        return $value;
    }

    // 名前・フリガナ・生年月日・メールのうち一致した項目数
    public static function score(array $a, array $b): int
    {
        $score = 0;
        foreach (['name', 'name_kana', 'birthdate', 'email'] as $key) {
            if (!empty($a[$key]) && !empty($b[$key]) && self::normalize($key, $a[$key]) === self::normalize($key, $b[$key])) {
                $score++;
            }
        }
        return $score;
    }

    public static function fields(User $u): array
    {
        return [
            'name'      => $u->name,
            'name_kana' => $u->name_kana,
            'birthdate' => $u->birthdate?->format('Y-m-d'),
            'email'     => $u->email ? strtolower($u->email) : null,
        ];
    }

    // 候補の中で3項目以上一致がただ1件だけ突出している場合のみ返す（自動紐付け用）
    public static function findUniqueTopMatch(Collection $candidates, array $target, int $threshold = 3): ?User
    {
        $scored = $candidates
            ->map(fn (User $u) => ['user' => $u, 'score' => self::score(self::fields($u), $target)])
            ->filter(fn ($pair) => $pair['score'] >= $threshold);

        if ($scored->isEmpty()) {
            return null;
        }

        $top = $scored->max('score');
        $atTop = $scored->filter(fn ($pair) => $pair['score'] === $top);

        return $atTop->count() === 1 ? $atTop->first()['user'] : null;
    }

    // 候補全件にスコアを付けて降順で返す（管理画面の候補表示用）
    public static function scoredCandidates(Collection $candidates, array $target, int $minScore = 1): Collection
    {
        return $candidates
            ->map(function (User $u) use ($target) {
                $u->match_score = self::score(self::fields($u), $target);
                return $u;
            })
            ->filter(fn ($u) => $u->match_score >= $minScore)
            ->sortByDesc('match_score')
            ->values();
    }
}
