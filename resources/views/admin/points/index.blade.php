@extends('layouts.admin')

@section('title', '協力金管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">協力金管理</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- 月フィルター --}}
<form method="GET" class="bg-white rounded-lg shadow p-3 mb-4 flex flex-wrap gap-3 items-end">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div>
        <label class="block text-xs text-gray-500 mb-1">月</label>
        <input type="month" name="month" value="{{ $month->format('Y-m') }}"
               class="border rounded px-2 py-1 text-sm">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.points.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded text-sm hover:bg-gray-500">リセット</a>
</form>

{{-- 先月・当月合計カード --}}
<div class="grid grid-cols-2 gap-4 mb-4">
    <div class="bg-white rounded-lg shadow px-5 py-4">
        <p class="text-xs text-gray-400 mb-1">先月 協力金合計</p>
        <p class="text-2xl font-bold text-gray-600">¥{{ number_format($prevTotal) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $month->copy()->subMonth()->format('Y年n月') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow px-5 py-4 border-l-4 border-pink-400">
        <p class="text-xs text-gray-400 mb-1">当月 協力金合計</p>
        <p class="text-2xl font-bold text-pink-600">¥{{ number_format($currentTotal) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $month->format('Y年n月') }}</p>
    </div>
</div>

{{-- タブ & アクション --}}
<div class="bg-white rounded-lg shadow mb-4 px-4 pt-3 pb-3">
    <div class="flex items-center justify-between flex-wrap gap-3">
        {{-- ステータスタブ --}}
        <div class="flex gap-1">
            <a href="{{ route('admin.points.index', ['month' => $month->format('Y-m'), 'tab' => 'pending']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                      {{ $tab === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                予約待ち
                <span class="ml-1 text-xs">¥{{ number_format($pendingTotal) }}</span>
            </a>
            <a href="{{ route('admin.points.index', ['month' => $month->format('Y-m'), 'tab' => 'reserved']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                      {{ $tab === 'reserved' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                予約済
                <span class="ml-1 text-xs">¥{{ number_format($reservedTotal) }}</span>
            </a>
        </div>

        {{-- アクションボタン --}}
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.points.csv', ['month' => $month->format('Y-m')]) }}"
               class="bg-gray-500 text-white px-4 py-2 rounded text-sm hover:bg-gray-600">CSV出力</a>
            @if($tab === 'pending' && $pendingTotal > 0)
            <form method="POST" action="{{ route('admin.points.mark_reserved') }}"
                  onsubmit="return confirm('{{ $month->format('Y年n月') }}の予約待ちをすべて予約済にしますか？')">
                @csrf @method('PATCH')
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded text-sm hover:bg-green-600">
                    → 予約済にする
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

{{-- ユーザー別一覧テーブル --}}
<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm whitespace-nowrap">
        <thead class="bg-gray-50 text-gray-700">
            <tr>
                <th class="px-4 py-3 text-left">ユーザーID</th>
                <th class="px-4 py-3 text-left">ユーザー名</th>
                <th class="px-4 py-3 text-right">件数</th>
                <th class="px-4 py-3 text-right">協力金合計</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($userSummary as $row)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono text-xs text-gray-600">
                    {{ $row['user']?->bimoni_user_id ?? '-' }}
                </td>
                <td class="px-4 py-3 font-medium text-gray-800">
                    {{ $row['user']?->name ?? '-' }}
                </td>
                <td class="px-4 py-3 text-right text-gray-500">{{ $row['count'] }}件</td>
                <td class="px-4 py-3 text-right font-bold {{ $row['total'] > 0 ? 'text-pink-600' : 'text-gray-400' }}">
                    ¥{{ number_format($row['total']) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                    {{ $month->format('Y年n月') }}の{{ $tab === 'pending' ? '予約待ち' : '予約済' }}データはありません
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($userSummary->isNotEmpty())
        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
            <tr>
                <td colspan="3" class="px-4 py-3 text-right text-sm font-medium text-gray-600">合計</td>
                <td class="px-4 py-3 text-right font-bold text-pink-600">
                    ¥{{ number_format($userSummary->sum('total')) }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
@endsection
