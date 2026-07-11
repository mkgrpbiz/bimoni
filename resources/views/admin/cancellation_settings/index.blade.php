@extends('layouts.admin')

@section('title', '解約方法管理')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold text-gray-800">解約方法管理</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<form method="GET" class="bg-white rounded-lg shadow p-3 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-700 mb-1">キーワード</label>
        <input type="text" name="q" value="{{ request('q') }}"
               class="border rounded px-2 py-1.5 text-sm w-48" placeholder="案件名">
    </div>
    <div>
        <label class="block text-xs text-gray-700 mb-1">設定状況</label>
        <select name="filled" class="border rounded px-2 py-1.5 text-sm">
            <option value="">すべて</option>
            <option value="1" @selected(request('filled') === '1')>設定済み</option>
            <option value="0" @selected(request('filled') === '0')>未設定</option>
        </select>
    </div>
    <button type="submit" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.cancellation_settings.index') }}"
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
                <td class="px-4 py-3 font-medium max-w-xs truncate">{{ $campaign->title }}</td>
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
                    <a href="{{ route('admin.cancellation_settings.edit', $campaign) }}"
                       class="text-xs bg-pink-500 text-white px-3 py-1 rounded hover:bg-pink-600">編集</a>
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
