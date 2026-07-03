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

{{-- ステータスタブ --}}
@php
$tabs = [
    'published' => ['label' => '公開中',   'color' => 'bg-green-500'],
    'paused'    => ['label' => '一時停止', 'color' => 'bg-orange-500'],
    'closed'    => ['label' => '終了',     'color' => 'bg-gray-500'],
    'draft'     => ['label' => '下書き',   'color' => 'bg-yellow-500'],
];
@endphp
<div class="flex border-b border-gray-200 mb-4">
    @foreach($tabs as $key => $tab)
    @php $count = $statusCounts->get($key, 0); @endphp
    <a href="{{ route('admin.campaigns.index', array_merge(request()->except(['status', 'page']), ['status' => $key])) }}"
       class="flex items-center gap-1.5 px-5 py-2.5 text-sm font-medium border-b-2 transition-colors
              {{ $status === $key
                  ? 'border-pink-500 text-pink-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        {{ $tab['label'] }}
        <span class="text-xs font-bold px-1.5 py-0.5 rounded-full text-white {{ $tab['color'] }}">
            {{ $count }}
        </span>
    </a>
    @endforeach
</div>

{{-- サブフィルター --}}
<form method="GET" class="bg-white rounded-lg shadow p-3 mb-4 flex flex-wrap gap-3 items-end">
    <input type="hidden" name="status" value="{{ $status }}">
    <div>
        <label class="block text-xs text-gray-700 mb-1">キーワード</label>
        <input type="text" name="q" value="{{ request('q') }}"
               class="border rounded px-2 py-1.5 text-sm w-40" placeholder="案件名">
    </div>
    <div>
        <label class="block text-xs text-gray-700 mb-1">種別</label>
        <select name="campaign_type" class="border rounded px-2 py-1.5 text-sm">
            <option value="">すべて</option>
            <option value="experience" @selected(request('campaign_type') === 'experience')>体験モニター</option>
            <option value="product"    @selected(request('campaign_type') === 'product')>商品モニター</option>
            <option value="pr"         @selected(request('campaign_type') === 'pr')>PRモニター</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-700 mb-1">PR媒体</label>
        <select name="pr_media" class="border rounded px-2 py-1.5 text-sm">
            <option value="">すべて</option>
            <option value="AD"      @selected(request('pr_media') === 'AD')>AD</option>
            <option value="IF"      @selected(request('pr_media') === 'IF')>IF</option>
            <option value="LINE"    @selected(request('pr_media') === 'LINE')>LINE</option>
            <option value="monitor" @selected(request('pr_media') === 'monitor')>モニター</option>
        </select>
    </div>
    <button type="submit" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.campaigns.index', ['status' => $status]) }}"
       class="text-sm text-gray-500 hover:text-gray-700 py-1.5">リセット</a>
</form>

{{-- 案件一覧（ドラッグ&ドロップ） --}}
<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-800 text-xs">
            <tr>
                <th class="px-3 py-3 w-6"></th>
                <th class="px-4 py-3 text-left">案件名</th>
                <th class="px-3 py-3 text-left">ステータス</th>
                <th class="px-3 py-3 text-left">PR媒体</th>
                <th class="px-3 py-3 text-left">種別</th>
                <th class="px-3 py-3 text-right">初回費</th>
                <th class="px-3 py-3 text-right">継続費</th>
                <th class="px-3 py-3 text-right">初回協力金</th>
                <th class="px-3 py-3 text-right">継続協力金</th>
                <th class="px-3 py-3 text-center">操作</th>
            </tr>
        </thead>
        <tbody id="sortable-campaigns" class="divide-y">
            @forelse($campaigns as $campaign)
            <tr class="hover:bg-gray-50 cursor-default" data-id="{{ $campaign->id }}">
                <td class="px-3 py-3 text-center cursor-grab text-gray-800 drag-handle select-none">⠿</td>
                <td class="px-4 py-3 font-medium max-w-xs">
                    <a href="{{ route('admin.campaigns.edit', $campaign) }}"
                       class="font-medium text-pink-600 hover:text-pink-800 hover:underline">{{ $campaign->title }}</a>
                </td>
                <td class="px-3 py-3">
                    <form method="POST" action="{{ route('admin.campaigns.update_status', $campaign) }}">
                        @csrf @method('PATCH')
                        <select name="status" onchange="this.form.submit()"
                                class="border rounded px-1.5 py-0.5 text-xs
                                    {{ match($campaign->status) {
                                        'published' => 'border-green-300 text-green-700 bg-green-50',
                                        'paused'    => 'border-orange-300 text-orange-700 bg-orange-50',
                                        'closed'    => 'border-gray-300 text-gray-600 bg-gray-50',
                                        default     => 'border-yellow-300 text-yellow-700 bg-yellow-50',
                                    } }}">
                            <option value="published" @selected($campaign->status === 'published')>公開中</option>
                            <option value="paused"    @selected($campaign->status === 'paused')>一時停止</option>
                            <option value="closed"    @selected($campaign->status === 'closed')>終了</option>
                            <option value="draft"     @selected($campaign->status === 'draft')>下書き</option>
                        </select>
                    </form>
                </td>
                <td class="px-3 py-3 text-gray-700">{{ $campaign->getPrMediaLabel() }}</td>
                <td class="px-3 py-3 text-gray-700">{{ $campaign->getTypeLabel() }}</td>
                <td class="px-3 py-3 text-right">¥{{ number_format($campaign->initial_purchase_fee ?? 0) }}</td>
                <td class="px-3 py-3 text-right">¥{{ number_format($campaign->recurring_purchase_fee ?? 0) }}</td>
                <td class="px-3 py-3 text-right text-gray-600">+¥{{ number_format($campaign->cooperation_fee ?? 0) }}</td>
                <td class="px-3 py-3 text-right text-gray-600">
                    @if($campaign->continuation_cooperation_fee !== null)
                        +¥{{ number_format($campaign->continuation_cooperation_fee) }}
                    @else
                        <span class="text-gray-300">-</span>
                    @endif
                </td>
                <td class="px-3 py-3">
                    <div class="flex gap-1 justify-center flex-wrap">
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
            <tr><td colspan="10" class="px-4 py-8 text-center text-gray-700">案件がありません</td></tr>
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
