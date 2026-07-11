@extends('layouts.admin')

@section('title', '解約方法管理')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold text-gray-800">解約方法管理</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- 表示・非表示タブ --}}
@php
$tabs = [
    '1' => ['label' => '表示',   'color' => 'bg-green-500'],
    '0' => ['label' => '非表示', 'color' => 'bg-gray-500'],
];
@endphp
<div class="flex border-b border-gray-200 mb-4">
    @foreach($tabs as $key => $tab)
    @php $count = $visibleCounts->get($key === '1' ? 1 : 0, 0); @endphp
    <a href="{{ route('admin.cancellation_settings.index', array_merge(request()->except(['visible', 'page']), ['visible' => $key])) }}"
       class="flex items-center gap-1.5 px-5 py-2.5 text-sm font-medium border-b-2 transition-colors
              {{ $visible === $key
                  ? 'border-pink-500 text-pink-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        {{ $tab['label'] }}
        <span class="text-xs font-bold px-1.5 py-0.5 rounded-full text-white {{ $tab['color'] }}">
            {{ $count }}
        </span>
    </a>
    @endforeach
</div>

<form method="GET" class="bg-white rounded-lg shadow p-3 mb-4 flex flex-wrap gap-3 items-end">
    <input type="hidden" name="visible" value="{{ $visible }}">
    <div>
        <label class="block text-xs text-gray-700 mb-1">キーワード</label>
        <input type="text" name="q" value="{{ request('q') }}"
               class="border rounded px-2 py-1.5 text-sm w-48" placeholder="案件名">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.cancellation_settings.index', ['visible' => $visible]) }}"
       class="text-sm text-gray-500 hover:text-gray-700 py-1.5">リセット</a>
</form>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-800 text-xs">
            <tr>
                <th class="px-4 py-3 text-left">案件名</th>
                <th class="px-3 py-3 text-left">電話番号</th>
                <th class="px-3 py-3 text-left">マイページ</th>
                <th class="px-3 py-3 text-left">メールアドレス</th>
                <th class="px-3 py-3 text-center">設定状況</th>
                <th class="px-3 py-3 text-center">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($campaigns as $campaign)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium max-w-xs truncate">
                    <a href="{{ route('admin.cancellation_settings.edit', $campaign) }}"
                       class="font-medium text-pink-600 hover:text-pink-800 hover:underline">{{ $campaign->title }}</a>
                </td>
                <td class="px-3 py-3 text-gray-700">{{ $campaign->cancellation_phone ?: '-' }}</td>
                <td class="px-3 py-3 text-gray-700 max-w-xs truncate">{{ $campaign->cancellation_mypage_url ?: '-' }}</td>
                <td class="px-3 py-3 text-gray-700">{{ $campaign->cancellation_email ?: '-' }}</td>
                <td class="px-3 py-3 text-center">
                    @if($campaign->hasCancellationInfo())
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">設定済み</span>
                    @else
                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">未設定</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-center">
                    <form method="POST" action="{{ route('admin.cancellation_settings.toggle_visible', $campaign) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="text-xs px-3 py-1 rounded
                                    {{ $campaign->cancellation_visible
                                        ? 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                                        : 'bg-pink-500 text-white hover:bg-pink-600' }}">
                            {{ $campaign->cancellation_visible ? '非表示にする' : '表示にする' }}
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-400">案件がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $campaigns->links() }}</div>
@endsection
