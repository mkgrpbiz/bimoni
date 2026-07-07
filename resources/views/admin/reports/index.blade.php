@extends('layouts.admin')

@section('title', '報告管理')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-6">報告管理</h1>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- ステータスタブ --}}
@php
$tabs = [
    'pending'  => ['label' => '承認待ち', 'color' => 'bg-yellow-500'],
    'approved' => ['label' => '承認',   'color' => 'bg-green-500'],
    'rejected' => ['label' => '差戻し', 'color' => 'bg-red-500'],
];
@endphp
<div class="flex items-center justify-between border-b border-gray-200 mb-4">
    <div class="flex">
        @foreach($tabs as $key => $tab)
        <a href="{{ route('admin.reports.index', ['status' => $key, 'q' => request('q')]) }}"
           class="flex items-center gap-1.5 px-5 py-2.5 text-sm font-medium border-b-2 transition-colors
                  {{ $status === $key ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            {{ $tab['label'] }}
            <span class="text-xs font-bold px-1.5 py-0.5 rounded-full text-white {{ $tab['color'] }}">
                {{ $counts->get($key, 0) }}
            </span>
        </a>
        @endforeach
    </div>
    <form method="GET" class="flex gap-2 items-end pb-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="q" value="{{ request('q') }}"
               class="border rounded px-2 py-1.5 text-sm w-48" placeholder="ユーザーID/LINE名/氏名/フリガナ">
        <button type="submit" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">検索</button>
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="bg-gray-50 text-gray-800">
            <tr>
                <th class="px-3 py-2 text-left whitespace-nowrap">報告日時</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">ユーザーID</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">登録コード</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">LINE表示名</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">名前</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">フリガナ</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">案件名</th>
                <th class="px-3 py-2 text-right whitespace-nowrap">モニター協力金</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">ステータス</th>
                <th class="px-3 py-2 text-center whitespace-nowrap">詳細</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($reports as $report)
            @php $user = $report->user; @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 whitespace-nowrap text-gray-700">{{ $report->created_at->format('m/d H:i') }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $user?->bimoni_user_id ?? '-' }}</td>
                <td class="px-3 py-2 font-mono text-gray-700">{{ $user?->referred_by_code ?? '-' }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $user?->line_display_name ?? '-' }}</td>
                <td class="px-3 py-2 font-medium whitespace-nowrap">{{ $user?->name ?? '（未登録）' }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $user?->name_kana ?? '-' }}</td>
                <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $report->campaign?->title ?? '-' }}</td>
                <td class="px-3 py-2 text-right whitespace-nowrap font-medium text-pink-600">
                    @php
                        $coopFee = $report->purchase_type === 'continuation'
                            ? ($report->campaign?->continuation_cooperation_fee ?? 0)
                            : ($report->campaign?->cooperation_fee ?? 0);
                        $total = ($report->purchase_amount ?? 0) + $coopFee + ($report->bonus_amount ?? 0) + ($report->adjustment_amount ?? 0);
                    @endphp
                    ¥{{ number_format($total) }}
                </td>
                <td class="px-3 py-2 whitespace-nowrap">
                    <div class="flex items-center gap-1.5">
                        <span class="px-1.5 py-0.5 rounded text-xs {{ $report->getStatusColor() }}">
                            {{ $report->getStatusLabel() }}
                        </span>
                        @if(in_array($report->status, ['approved', 'rejected']))
                        <form method="POST" action="{{ route('admin.reports.revert', $report) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    onclick="return confirm('承認待ちに戻しますか？')"
                                    class="text-xs text-gray-500 border border-gray-300 rounded px-1.5 py-0.5 hover:bg-gray-100">
                                戻す
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
                <td class="px-3 py-2 text-center">
                    <a href="{{ route('admin.reports.show', $report) }}"
                       class="bg-pink-500 text-white px-2 py-1 rounded hover:bg-pink-600 text-xs">詳細</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="px-4 py-8 text-center text-gray-700">報告がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $reports->links() }}</div>

@endsection
