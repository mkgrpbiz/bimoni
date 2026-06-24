@extends('layouts.admin')

@section('title', '案件管理')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold text-gray-800">案件管理</h1>
    <a href="{{ route('admin.campaigns.create') }}"
       class="bg-pink-500 text-white px-4 py-2 rounded hover:bg-pink-600 text-sm">
        ＋ 新規案件登録
    </a>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- フィルター --}}
<form method="GET" class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-700 mb-1">キーワード</label>
        <input type="text" name="q" value="{{ request('q') }}"
               class="border rounded px-2 py-1.5 text-sm w-40" placeholder="案件名">
    </div>
    <div>
        <label class="block text-xs text-gray-700 mb-1">ステータス</label>
        <select name="status" class="border rounded px-2 py-1.5 text-sm">
            <option value="">すべて</option>
            <option value="draft"     @selected(request('status') === 'draft')>下書き</option>
            <option value="published" @selected(request('status') === 'published')>公開中</option>
            <option value="paused"    @selected(request('status') === 'paused')>一時停止</option>
            <option value="closed"    @selected(request('status') === 'closed')>終了</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-700 mb-1">種別</label>
        <select name="campaign_type" class="border rounded px-2 py-1.5 text-sm">
            <option value="">すべて</option>
            <option value="experience" @selected(request('campaign_type') === 'experience')>体験</option>
            <option value="product"    @selected(request('campaign_type') === 'product')>商品</option>
            <option value="recovery"   @selected(request('campaign_type') === 'recovery')>回収</option>
        </select>
    </div>
    <button type="submit" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.campaigns.index') }}" class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">リセット</a>
</form>

{{-- 案件一覧（ドラッグ&ドロップ） --}}
<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-800 text-xs">
            <tr>
                <th class="px-3 py-3 w-6"></th>{{-- ドラッグハンドル --}}
                <th class="px-4 py-3 text-left">案件名</th>
                <th class="px-3 py-3 text-left">PR媒体</th>
                <th class="px-3 py-3 text-left">種別</th>
                <th class="px-3 py-3 text-left">ステータス</th>
                <th class="px-3 py-3 text-right">初回費</th>
                <th class="px-3 py-3 text-right">継続費</th>
                <th class="px-3 py-3 text-right">協力金</th>
                <th class="px-3 py-3 text-center">操作</th>
            </tr>
        </thead>
        <tbody id="sortable-campaigns" class="divide-y">
            @forelse($campaigns as $campaign)
            <tr class="hover:bg-gray-50 cursor-default" data-id="{{ $campaign->id }}">
                <td class="px-3 py-3 text-center cursor-grab text-gray-800 drag-handle select-none">⠿</td>
                <td class="px-4 py-3 font-medium max-w-xs">
                    <a href="{{ route('admin.campaigns.show', $campaign) }}"
                       class="font-medium text-pink-600 hover:text-pink-800 hover:underline">{{ $campaign->title }}</a>
                </td>
                <td class="px-3 py-3 text-gray-700">
                    {{ $campaign->getPrMediaLabel() }}
                </td>
                <td class="px-3 py-3 text-gray-700">{{ $campaign->getTypeLabel() }}</td>
                <td class="px-3 py-3">
                    @php
                    $sc = match($campaign->status) {
                        'published' => 'bg-green-500 text-white',
                        'draft'     => 'bg-yellow-500 text-white',
                        'paused'    => 'bg-orange-500 text-white',
                        'closed'    => 'bg-gray-500 text-white',
                        default     => 'bg-gray-500 text-white',
                    };
                    @endphp
                    <span class="px-2 py-0.5 rounded text-xs {{ $sc }}">{{ $campaign->getStatusLabel() }}</span>
                </td>
                <td class="px-3 py-3 text-right">¥{{ number_format($campaign->initial_purchase_fee ?? 0) }}</td>
                <td class="px-3 py-3 text-right">¥{{ number_format($campaign->recurring_purchase_fee ?? 0) }}</td>
                <td class="px-3 py-3 text-right">¥{{ number_format($campaign->cooperation_fee ?? 0) }}</td>
                <td class="px-3 py-3">
                    <div class="flex gap-1 justify-center flex-wrap">
                        <a href="{{ route('admin.campaigns.applications', $campaign) }}"
                           class="text-xs bg-pink-500 text-white px-2 py-1 rounded hover:bg-pink-600">応募者</a>
                        <a href="{{ route('admin.campaigns.daily_slots.index', $campaign) }}"
                           class="text-xs bg-pink-500 text-white px-2 py-1 rounded hover:bg-pink-600">日別</a>
                        <a href="{{ route('admin.campaigns.edit', $campaign) }}"
                           class="text-xs bg-pink-500 text-white px-2 py-1 rounded hover:bg-pink-600">編集</a>
                        <form method="POST" action="{{ route('admin.campaigns.duplicate', $campaign) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="text-xs bg-pink-500 text-white px-2 py-1 rounded hover:bg-pink-600">複製</button>
                        </form>
                        <form method="POST" action="{{ route('admin.campaigns.destroy', $campaign) }}"
                              class="inline" onsubmit="return confirm('削除しますか？')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="text-xs bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">削除</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="px-4 py-8 text-center text-gray-700">案件がありません</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $campaigns->links() }}</div>

{{-- SortableJS --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const tbody = document.getElementById('sortable-campaigns');
if (tbody) {
    Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function() {
            const ids = [...tbody.querySelectorAll('tr[data-id]')].map(r => r.dataset.id);
            fetch('{{ route('admin.campaigns.reorder') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ ids }),
            });
        },
    });
}
</script>
@endsection


