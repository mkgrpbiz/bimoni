@extends('layouts.admin')
@section('title', '回収管理')
@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold text-gray-800">回収管理</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- ステータスタブ --}}
@php
$tabs = [
    'pending'  => ['label' => '承認待ち', 'color' => 'bg-yellow-500'],
    'approved' => ['label' => '承認',     'color' => 'bg-green-500'],
    'rejected' => ['label' => '差戻し',   'color' => 'bg-red-500'],
];
@endphp
<div class="flex items-center justify-between border-b border-gray-200 mb-4">
    <div class="flex">
        @foreach($tabs as $key => $tab)
        <a href="{{ route('admin.collection_reports.index', ['status' => $key, 'q' => request('q')]) }}"
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
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-700 text-xs">
            <tr>
                <th class="px-4 py-3 text-left whitespace-nowrap">報告日時</th>
                <th class="px-3 py-3 text-left whitespace-nowrap">ユーザーID</th>
                <th class="px-3 py-3 text-left whitespace-nowrap">登録コード</th>
                <th class="px-3 py-3 text-left whitespace-nowrap">LINE表示名</th>
                <th class="px-3 py-3 text-left whitespace-nowrap">名前</th>
                <th class="px-3 py-3 text-left whitespace-nowrap">フリガナ</th>
                <th class="px-3 py-3 text-right whitespace-nowrap">商品数</th>
                <th class="px-3 py-3 text-left whitespace-nowrap">到着予定日</th>
                <th class="px-3 py-3 text-left whitespace-nowrap">追跡番号</th>
                <th class="px-3 py-3 text-center whitespace-nowrap">ステータス</th>
                <th class="px-3 py-3 text-center whitespace-nowrap">詳細</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($reports as $report)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $report->created_at->format('m/d H:i') }}</td>
                <td class="px-3 py-3 text-gray-600 text-xs">{{ $report->user->bimoni_user_id ?? '-' }}</td>
                <td class="px-3 py-3 font-mono text-xs text-pink-600">{{ $report->user->referred_by_code ?? '-' }}</td>
                <td class="px-3 py-3 max-w-xs truncate">{{ $report->user->line_display_name ?? '-' }}</td>
                <td class="px-3 py-3 font-medium">{{ $report->user->name ?? '-' }}</td>
                <td class="px-3 py-3 text-gray-600">{{ $report->user->name_kana ?? '-' }}</td>
                <td class="px-3 py-3 text-right font-bold">{{ $report->item_count }}</td>
                <td class="px-3 py-3 whitespace-nowrap">{{ $report->estimated_arrival_date?->format('m/d') ?? '-' }}</td>
                <td class="px-3 py-3 font-mono text-xs">{{ $report->tracking_number ?? '-' }}</td>
                <td class="px-3 py-3 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $report->getStatusColor() }}">
                        {{ $report->getStatusLabel() }}
                    </span>
                </td>
                <td class="px-3 py-3 text-center">
                    <a href="{{ route('admin.collection_reports.show', $report) }}"
                       class="text-xs bg-pink-500 text-white px-3 py-1 rounded hover:bg-pink-600">
                        詳細
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="px-4 py-8 text-center text-gray-400">回収報告がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $reports->appends(request()->query())->links() }}</div>

@endsection
