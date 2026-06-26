@extends('layouts.admin')

@section('title', '紹介報酬管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">紹介報酬管理</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-800 rounded px-4 py-2 mb-4">{{ session('success') }}</div>
@endif

<form method="GET" class="bg-white rounded-lg shadow p-3 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">月</label>
        <input type="month" name="month" value="{{ $month->format('Y-m') }}"
               class="border rounded px-2 py-1 text-sm">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.referrals.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded text-sm hover:bg-gray-500">リセット</a>
</form>

<div class="bg-white rounded-lg shadow mb-2 px-4 py-3 text-sm text-gray-600">
    {{ $month->format('Y年n月') }} 締め ／ 支払い: {{ $month->copy()->addMonth()->endOfMonth()->format('Y年n月末') }}
</div>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm whitespace-nowrap">
        <thead class="bg-gray-50 text-gray-700">
            <tr>
                <th class="px-4 py-3 text-left">代理店名</th>
                <th class="px-4 py-3 text-left">紹介コード</th>
                <th class="px-4 py-3 text-right">登録人数</th>
                <th class="px-4 py-3 text-right">応募数</th>
                <th class="px-4 py-3 text-right">報告数(¥500)</th>
                <th class="px-4 py-3 text-right">報告数(¥1000)</th>
                <th class="px-4 py-3 text-right">全否認数</th>
                <th class="px-4 py-3 text-right">紹介報酬合計</th>
                <th class="px-4 py-3 text-center">ステータス</th>
                <th class="px-4 py-3 text-center">詳細</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($summary as $row)
            <tr class="hover:bg-gray-50 {{ $row['pay_status'] === 'done' ? 'opacity-60' : '' }}">
                <td class="px-4 py-3 font-medium text-gray-800">
                    {{ $row['agent']->name }}
                    @foreach($row['agent']->children as $child)
                        <span class="block text-xs text-gray-400">└ {{ $child->name }}</span>
                    @endforeach
                </td>
                <td class="px-4 py-3 font-mono text-xs text-gray-600">
                    @foreach($row['codes'] as $code)
                        <span class="block">{{ $code }}</span>
                    @endforeach
                </td>
                <td class="px-4 py-3 text-right">{{ $row['registered'] }}</td>
                <td class="px-4 py-3 text-right">{{ $row['applications'] }}</td>
                <td class="px-4 py-3 text-right">{{ $row['reports_by_fee']->get(500)?->count() ?? 0 }}</td>
                <td class="px-4 py-3 text-right">{{ $row['reports_by_fee']->get(1000)?->count() ?? 0 }}</td>
                <td class="px-4 py-3 text-right">
                    @if($row['all_denied'] > 0)
                        <span class="text-red-500 font-medium">{{ $row['all_denied'] }}</span>
                    @else
                        <span class="text-gray-400">0</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-bold {{ $row['expected_pay'] > 0 ? 'text-green-600' : 'text-gray-400' }}">
                    ¥{{ number_format($row['expected_pay']) }}
                </td>
                <td class="px-4 py-3 text-center">
                    @if($row['pay_status'] === 'done')
                        <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-1 rounded font-medium">処理済</span>
                        <form method="POST" action="{{ route('admin.referrals.mark_pending') }}" class="mt-1">
                            @csrf @method('PATCH')
                            <input type="hidden" name="agent_id" value="{{ $row['agent']->id }}">
                            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                            <button type="submit" class="text-xs text-gray-400 hover:text-gray-600 underline">戻す</button>
                        </form>
                    @else
                        <span class="inline-block bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded font-medium">処理待ち</span>
                        @if($row['expected_pay'] > 0)
                        <form method="POST" action="{{ route('admin.referrals.mark_done') }}" class="mt-1"
                              onsubmit="return confirm('{{ $row['agent']->name }}を処理済みにしますか？')">
                            @csrf @method('PATCH')
                            <input type="hidden" name="agent_id" value="{{ $row['agent']->id }}">
                            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                            <button type="submit" class="text-xs text-green-600 hover:text-green-800 underline">処理済みにする</button>
                        </form>
                        @endif
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    <a href="{{ route('admin.agents.show', $row['agent']) }}"
                       class="bg-pink-500 text-white text-xs px-3 py-1 rounded hover:bg-pink-600">詳細</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="px-4 py-8 text-center text-gray-400">
                    {{ $month->format('Y年n月') }}の紹介データがありません
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
