@extends('layouts.admin')

@section('title', '紹介報酬管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">紹介報酬管理</h1>
</div>

<form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">月</label>
        <input type="month" name="month" value="{{ $month->format('Y-m') }}"
               class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">紹介コード検索</label>
        <input type="text" name="code" value="{{ request('code') }}" placeholder="例: AB1234"
               class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm w-32">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.referrals.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded text-sm hover:bg-gray-600">リセット</a>
</form>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-2 px-4 py-3 text-sm text-gray-700 dark:text-gray-400">
    {{ $month->format('Y年n月') }} 締め ／ 支払い: {{ $month->copy()->addMonth()->endOfMonth()->format('Y年n月末') }}
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm whitespace-nowrap">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">紹介コード</th>
                <th class="px-4 py-3 text-left">紹介者</th>
                <th class="px-4 py-3 text-right">登録人数</th>
                <th class="px-4 py-3 text-right">応募数</th>
                <th class="px-4 py-3 text-right">報告数</th>
                <th class="px-4 py-3 text-right">全否認数</th>
                <th class="px-4 py-3 text-right">支払い予定額</th>
                <th class="px-4 py-3 text-center">詳細</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($summary as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 font-mono font-bold text-gray-800 dark:text-gray-200">
                    {{ $row['code'] }}
                </td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                    {{ $row['referrer']->name ?? '（未登録）' }}
                </td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">{{ $row['registered'] }}</td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">{{ $row['applications'] }}</td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">{{ $row['reports'] }}</td>
                <td class="px-4 py-3 text-right">
                    @if($row['all_denied'] > 0)
                        <span class="text-red-500 font-medium">{{ $row['all_denied'] }}</span>
                    @else
                        <span class="text-gray-700 dark:text-gray-500">0</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-medium
                    {{ $row['expected_pay'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-500' }}">
                    ¥{{ number_format($row['expected_pay']) }}
                </td>
                <td class="px-4 py-3 text-center">
                    <a href="{{ route('admin.referrals.show', ['code' => $row['code'], 'month' => $month->format('Y-m')]) }}"
                       class="bg-pink-500 text-white text-xs px-3 py-1 rounded hover:bg-pink-600">詳細</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-700 dark:text-gray-500">
                    {{ $month->format('Y年n月') }}の紹介データがありません
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
