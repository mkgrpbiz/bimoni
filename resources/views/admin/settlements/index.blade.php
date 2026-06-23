@extends('layouts.admin')

@section('title', '月末締め管理')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">月末締め管理</h1>

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- 月末締め実行 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 mb-6">
    <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-2">月末締め処理</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        承認済みの応募（{{ $pendingCount }}件）を対象に当月分の締め処理を実行します。翌月10日が支払日になります。
    </p>
    <form method="POST" action="{{ route('admin.settlements.close') }}" onsubmit="return confirm('月末締めを実行しますか？この操作は取り消せません。')">
        @csrf
        <button type="submit" class="bg-pink-600 text-white px-5 py-2 rounded hover:bg-pink-700 text-sm
            {{ $pendingCount === 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
            {{ $pendingCount === 0 ? 'disabled' : '' }}>
            当月分を締める
        </button>
    </form>
</div>

{{-- 締め履歴 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">締め月</th>
                <th class="px-4 py-3 text-left">ステータス</th>
                <th class="px-4 py-3 text-right">合計金額</th>
                <th class="px-4 py-3 text-left">支払予定日</th>
                <th class="px-4 py-3 text-left">締め日時</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($settlements as $s)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 font-medium dark:text-gray-200">{{ $s->settlement_month->format('Y年n月') }}</td>
                <td class="px-4 py-3">
                    @php
                        $color = match($s->status) {
                            'open'   => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
                            'closed' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                            'paid'   => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                        };
                    @endphp
                    <span class="px-2 py-0.5 rounded text-xs {{ $color }}">{{ $s->getStatusLabel() }}</span>
                </td>
                <td class="px-4 py-3 text-right font-medium dark:text-gray-200">¥{{ number_format($s->total_amount) }}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $s->payment_due_date->format('Y/m/d') }}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $s->closed_at?->format('Y/m/d H:i') ?? '-' }}</td>
                <td class="px-4 py-3 text-right flex gap-2 justify-end">
                    <a href="{{ route('admin.settlements.show', $s) }}" class="text-xs text-pink-600 dark:text-pink-400 hover:underline">詳細</a>
                    @if($s->status === 'closed')
                    <form method="POST" action="{{ route('admin.settlements.paid', $s) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-xs text-green-600 dark:text-green-400 hover:underline">支払済みにする</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">締め履歴がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $settlements->links() }}</div>
@endsection
