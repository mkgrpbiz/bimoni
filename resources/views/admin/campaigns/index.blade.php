@extends('layouts.admin')

@section('title', '案件管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">案件管理</h1>
    <a href="{{ route('admin.campaigns.create') }}"
       class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 text-sm">
        ＋ 新規案件登録
    </a>
</div>

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- フィルター --}}
<form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">キーワード</label>
        <input type="text" name="q" value="{{ request('q') }}"
               class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm w-40" placeholder="案件名">
    </div>
    <div>
        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ステータス</label>
        <select name="status" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
            <option value="">すべて</option>
            <option value="draft" @selected(request('status') === 'draft')>下書き</option>
            <option value="published" @selected(request('status') === 'published')>公開中</option>
            <option value="closed" @selected(request('status') === 'closed')>終了</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">種別</label>
        <select name="campaign_type" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
            <option value="">すべて</option>
            <option value="experience" @selected(request('campaign_type') === 'experience')>体験</option>
            <option value="product" @selected(request('campaign_type') === 'product')>商品</option>
            <option value="recovery" @selected(request('campaign_type') === 'recovery')>回収</option>
        </select>
    </div>
    <button type="submit" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">絞り込み</button>
    <a href="{{ route('admin.campaigns.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">リセット</a>
</form>

{{-- 案件一覧 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">案件名</th>
                <th class="px-4 py-3 text-left">種別</th>
                <th class="px-4 py-3 text-left">ステータス</th>
                <th class="px-4 py-3 text-right">協力金</th>
                <th class="px-4 py-3 text-right">募集人数</th>
                <th class="px-4 py-3 text-left">募集期間</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($campaigns as $campaign)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 font-medium">
                    <a href="{{ route('admin.campaigns.show', $campaign) }}" class="text-pink-600 dark:text-pink-400 hover:underline">
                        {{ $campaign->title }}
                    </a>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $campaign->getTypeLabel() }}</td>
                <td class="px-4 py-3">
                    @php
                        $statusColor = match($campaign->status) {
                            'published' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                            'draft'     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
                            'closed'    => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                        };
                    @endphp
                    <span class="px-2 py-0.5 rounded text-xs {{ $statusColor }}">
                        {{ $campaign->getStatusLabel() }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">¥{{ number_format($campaign->cooperation_fee) }}</td>
                <td class="px-4 py-3 text-right">{{ $campaign->capacity }}名</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                    {{ $campaign->application_start_at?->format('Y/m/d') ?? '-' }}
                    〜
                    {{ $campaign->application_end_at?->format('Y/m/d') ?? '-' }}
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex gap-2 justify-end flex-wrap">
                        <a href="{{ route('admin.campaigns.applications', $campaign) }}"
                           class="text-xs bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300 px-2 py-0.5 rounded hover:bg-purple-200">応募者を見る</a>
                        <a href="{{ route('admin.campaigns.daily_slots.index', $campaign) }}"
                           class="text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300 px-2 py-0.5 rounded hover:bg-indigo-200">日別件数</a>
                        <a href="{{ route('admin.campaigns.edit', $campaign) }}"
                           class="text-blue-600 dark:text-blue-400 hover:underline text-xs">編集</a>
                        <form method="POST" action="{{ route('admin.campaigns.destroy', $campaign) }}"
                              class="inline" onsubmit="return confirm('削除しますか？')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 dark:text-red-400 hover:underline text-xs">削除</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">案件がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $campaigns->links() }}</div>
@endsection
