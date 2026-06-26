@extends('layouts.admin')

@section('title', '協力金支払管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">協力金支払管理</h1>
    <button onclick="document.getElementById('adjust-form').classList.toggle('hidden')"
            class="bg-gray-500 text-white px-3 py-1.5 rounded text-sm hover:bg-gray-600">手動調整</button>
</div>

{{-- 手動調整フォーム（折りたたみ） --}}
<div id="adjust-form" class="hidden bg-white dark:bg-gray-800 rounded-lg shadow p-5 mb-5">
    <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-4">手動ポイント調整</h2>
    <form method="POST" action="{{ route('admin.points.adjust') }}" class="flex flex-wrap gap-3 items-end">
        @csrf
        <div>
            <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">ユーザーID（例: BMN00100001）</label>
            <input type="text" name="bimoni_user_id" required placeholder="BMN00100001"
                   value="{{ old('bimoni_user_id') }}"
                   class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm w-44 @error('bimoni_user_id') border-red-400 @enderror">
            @error('bimoni_user_id')<p class="text-red-500 text-xs mt-1 w-full">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">金額（マイナスも可）</label>
            <input type="number" name="amount" required placeholder="例: 500 または -200"
                   value="{{ old('amount') }}"
                   class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm w-36 @error('amount') border-red-400 @enderror">
            @error('amount')<p class="text-red-500 text-xs mt-1 w-full">{{ $message }}</p>@enderror
        </div>
        <div class="flex-1 min-w-40">
            <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">理由</label>
            <input type="text" name="reason" required placeholder="調整理由"
                   value="{{ old('reason') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm @error('reason') border-red-400 @enderror">
            @error('reason')<p class="text-red-500 text-xs mt-1 w-full">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">実行</button>
    </form>
</div>

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- 月次フィルター & 検索 --}}
<form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">月</label>
        <input type="month" name="month" value="{{ $month->format('Y-m') }}"
               class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">ユーザーID検索</label>
        <input type="text" name="bimoni_user_id" value="{{ request('bimoni_user_id') }}"
               placeholder="BMN00100001"
               class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm w-40">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.points.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded text-sm hover:bg-gray-600">リセット</a>
</form>

{{-- サマリー & アクション --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4 flex flex-wrap items-center gap-6">
    <div>
        <span class="text-xs text-gray-700 dark:text-gray-400">{{ $month->format('Y年n月') }} 合計</span>
        <p class="text-xl font-bold text-gray-800 dark:text-gray-100">¥{{ number_format($totalAmount) }}</p>
    </div>
    <div>
        <span class="text-xs text-gray-700 dark:text-gray-400">支払待ち</span>
        <p class="text-xl font-bold text-red-500">¥{{ number_format($pendingAmount) }}</p>
    </div>
    <div class="ml-auto flex gap-2 flex-wrap items-end">
        <a href="{{ route('admin.points.csv', ['month' => $month->format('Y-m')]) }}"
           class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">CSV出力</a>
        <form method="GET" action="{{ route('admin.points.zengin') }}" class="flex items-end gap-2">
            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
            <div>
                <label class="block text-xs text-gray-500 mb-1">振込日</label>
                <input type="date" name="transfer_date" required
                       value="{{ now()->format('Y-m-d') }}"
                       class="border rounded px-2 py-1.5 text-sm">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">全銀出力</button>
        </form>
        @if($pendingAmount > 0)
        <form method="POST" action="{{ route('admin.points.mark_paid') }}"
              onsubmit="return confirm('{{ $month->format('Y年n月') }}の未払いをすべて支払済みにしますか？')">
            @csrf @method('PATCH')
            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
            <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded text-sm hover:bg-gray-600">
                今月分を支払済みにする
            </button>
        </form>
        @endif
    </div>
</div>

{{-- テーブル --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">日時</th>
                <th class="px-4 py-3 text-left">ユーザーID</th>
                <th class="px-4 py-3 text-left">ユーザー名</th>
                <th class="px-4 py-3 text-left">ステータス</th>
                <th class="px-4 py-3 text-left">モニター名</th>
                <th class="px-4 py-3 text-right">協力金</th>
                <th class="px-4 py-3 text-right">CP</th>
                <th class="px-4 py-3 text-right">合計</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($reports as $report)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-400 whitespace-nowrap">
                    {{ $report->created_at->format('Y/m/d') }}
                </td>
                <td class="px-4 py-3 font-mono text-xs text-gray-800 dark:text-gray-200 whitespace-nowrap">
                    {{ $report->user?->bimoni_user_id ?? '-' }}
                </td>
                <td class="px-4 py-3 dark:text-gray-200">{{ $report->user?->name ?? '-' }}</td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded
                        {{ $report->payment_status === 'paid' ? 'bg-green-500 text-white' : 'bg-yellow-500 text-white' }}">
                        {{ $report->payment_status === 'paid' ? '支払済' : '支払待ち' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ $report->campaign?->title ?? '-' }}</td>
                <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-gray-200">
                    ¥{{ number_format($report->campaign?->cooperation_fee ?? 0) }}
                </td>
                <td class="px-4 py-3 text-right text-red-500 font-medium">
                    @if($report->application?->bonus_amount)
                        +¥{{ number_format($report->application->bonus_amount) }}
                    @else
                        <span class="text-gray-300">-</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-bold text-gray-800 dark:text-gray-200">
                    ¥{{ number_format(($report->campaign?->cooperation_fee ?? 0) + ($report->application?->bonus_amount ?? 0)) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-700 dark:text-gray-500">
                    {{ $month->format('Y年n月') }}の承認済み報告はありません
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

