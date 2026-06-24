@extends('layouts.admin')

@section('title', '紹介報酬詳細')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.referrals.index', ['month' => $month->format('Y-m')]) }}"
       class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">← 紹介報酬管理</a>
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">紹介コード: {{ $code }}</h1>
</div>

{{-- 月切り替え --}}
<form method="GET" class="flex gap-3 items-end mb-4">
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">月</label>
        <input type="month" name="month" value="{{ $month->format('Y-m') }}"
               class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">表示</button>
</form>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    {{-- 紹介者情報 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">紹介者</h2>
        <dl class="text-sm space-y-1.5">
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28">ユーザーID</dt>
                <dd class="font-mono text-gray-800 dark:text-gray-200">{{ $referrer->bimoni_user_id ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28">名前</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">{{ $referrer->name ?? '（未登録）' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28">紹介コード</dt>
                <dd class="font-mono font-bold text-pink-600 dark:text-pink-400">{{ $code }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28">総紹介人数</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">{{ $referredUsers->count() }} 人</dd>
            </div>
        </dl>
    </div>

    {{-- 当月サマリー --}}
    @php
        $eligibleUserIds = $reports->where('status', 'approved')->pluck('user_id')->unique();
        $expectedPay = $reports->where('status', 'approved')
            ->whereIn('user_id', $eligibleUserIds->all())
            ->sum(fn($r) => $r->campaign?->referral_fee ?? 0);
    @endphp
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">{{ $month->format('Y年n月') }} サマリー</h2>
        <dl class="text-sm space-y-1.5">
            <div class="flex justify-between">
                <dt class="text-gray-700 dark:text-gray-400">承認済み報告数</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">{{ $reports->count() }} 件</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-700 dark:text-gray-400">支払い予定額</dt>
                <dd class="font-medium text-green-600 dark:text-green-400">¥{{ number_format($expectedPay) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-700 dark:text-gray-400">支払い日</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $month->copy()->addMonth()->endOfMonth()->format('Y年n月末') }}</dd>
            </div>
        </dl>
    </div>
</div>

{{-- 被紹介者一覧 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-6">
    <div class="px-5 py-3 border-b dark:border-gray-700">
        <h2 class="font-bold text-gray-700 dark:text-gray-200">紹介登録者一覧</h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">登録日</th>
                <th class="px-4 py-3 text-left">ユーザーID</th>
                <th class="px-4 py-3 text-left">名前</th>
                <th class="px-4 py-3 text-left">当月報告状況</th>
                <th class="px-4 py-3 text-right">紹介報酬</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @foreach($referredUsers as $ru)
            @php
                $userReports = $reports->where('user_id', $ru->id);
                $hasApproved = $userReports->where('status', 'approved')->isNotEmpty();
                $referralPay = $hasApproved
                    ? $userReports->where('status', 'approved')->sum(fn($r) => $r->campaign?->referral_fee ?? 0)
                    : 0;
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-400">{{ $ru->created_at?->format('Y/m/d') }}</td>
                <td class="px-4 py-3 font-mono text-xs text-gray-800 dark:text-gray-200">{{ $ru->bimoni_user_id }}</td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ $ru->name ?? '（未登録）' }}</td>
                <td class="px-4 py-3">
                    @if($userReports->isEmpty())
                        <span class="text-xs text-gray-700 dark:text-gray-500">当月報告なし</span>
                    @elseif($hasApproved)
                        <span class="text-xs bg-green-500 text-white px-2 py-0.5 rounded">承認あり</span>
                    @else
                        <span class="text-xs bg-red-500 text-white px-2 py-0.5 rounded">全否認</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-medium {{ $referralPay > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-500' }}">
                    ¥{{ number_format($referralPay) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- 当月承認報告詳細 --}}
@if($reports->isNotEmpty())
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <div class="px-5 py-3 border-b dark:border-gray-700">
        <h2 class="font-bold text-gray-700 dark:text-gray-200">{{ $month->format('Y年n月') }} 承認済み報告詳細</h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">報告日</th>
                <th class="px-4 py-3 text-left">ユーザー名</th>
                <th class="px-4 py-3 text-left">モニター名</th>
                <th class="px-4 py-3 text-right">協力金</th>
                <th class="px-4 py-3 text-right">紹介報酬</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @foreach($reports as $report)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-400">{{ $report->created_at->format('Y/m/d') }}</td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ $report->user?->name ?? '-' }}</td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ $report->campaign?->title ?? '-' }}</td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">
                    ¥{{ number_format($report->campaign?->cooperation_fee ?? 0) }}
                </td>
                <td class="px-4 py-3 text-right font-medium text-green-600 dark:text-green-400">
                    ¥{{ number_format($report->campaign?->referral_fee ?? 0) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
