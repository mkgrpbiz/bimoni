@extends('layouts.admin')

@section('title', $settlement->settlement_month->format('Y年n月') . '締め詳細')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.settlements.index') }}"
       class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">← 締め一覧</a>
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
        {{ $settlement->settlement_month->format('Y年n月') }}締め詳細
    </h1>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 mb-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
    <div>
        <p class="text-gray-700 dark:text-gray-400">ステータス</p>
        <p class="font-medium dark:text-gray-200">{{ $settlement->getStatusLabel() }}</p>
    </div>
    <div>
        <p class="text-gray-700 dark:text-gray-400">合計金額</p>
        <p class="font-bold text-pink-600 dark:text-pink-400 text-lg">¥{{ number_format($settlement->total_amount) }}</p>
    </div>
    <div>
        <p class="text-gray-700 dark:text-gray-400">支払予定日</p>
        <p class="font-medium dark:text-gray-200">{{ $settlement->payment_due_date->format('Y/m/d') }}</p>
    </div>
    <div>
        <p class="text-gray-700 dark:text-gray-400">締め件数</p>
        <p class="font-medium dark:text-gray-200">{{ $settlement->points->count() }}件</p>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">モニター</th>
                <th class="px-4 py-3 text-left">理由</th>
                <th class="px-4 py-3 text-right">金額</th>
                <th class="px-4 py-3 text-left">日時</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @foreach($settlement->points as $point)
            <tr>
                <td class="px-4 py-3 dark:text-gray-200">{{ $point->user->name ?? '-' }}</td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-400">{{ $point->reason ?? '-' }}</td>
                <td class="px-4 py-3 text-right font-medium text-green-600 dark:text-green-400">¥{{ number_format($point->amount) }}</td>
                <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-400">{{ $point->created_at->format('Y/m/d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

