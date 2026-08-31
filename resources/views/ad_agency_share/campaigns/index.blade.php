@extends('layouts.ad_agency_share')

@section('title', '案件一覧')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-5">案件一覧</h1>

{{-- ステータスタブ --}}
@php
$tabs = [
    'published' => ['label' => '公開中',   'color' => 'bg-green-500'],
    'paused'    => ['label' => '募集停止', 'color' => 'bg-orange-500'],
    'closed'    => ['label' => '案内終了', 'color' => 'bg-gray-500'],
];
@endphp
<div class="flex border-b border-gray-200 mb-4">
    @foreach($tabs as $key => $tab)
    @php $count = $statusCounts->get($key, 0); @endphp
    <a href="{{ route('ad_agency_share.campaigns', array_merge(request()->except(['status', 'page']), ['status' => $key])) }}"
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
    <a href="{{ route('ad_agency_share.campaigns', ['status' => $status]) }}"
       class="text-sm text-gray-500 hover:text-gray-700 py-1.5">リセット</a>
</form>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-800 text-xs">
            <tr>
                <th class="px-4 py-3 text-left">案件名</th>
                <th class="px-3 py-3 text-left">ステータス</th>
                <th class="px-3 py-3 text-left">PR媒体</th>
                <th class="px-3 py-3 text-left">種別</th>
                <th class="px-3 py-3 text-right">応募総数</th>
                <th class="px-3 py-3 text-right bg-pink-50">応募残数</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($campaigns as $campaign)
            <tr class="even:bg-gray-50">
                <td class="px-4 py-3 font-medium max-w-xs text-gray-800">{{ $campaign->title }}</td>
                <td class="px-3 py-3">
                    <span class="px-1.5 py-0.5 rounded text-xs
                        {{ match($campaign->status) {
                            'published' => 'border border-green-300 text-green-700 bg-green-50',
                            'paused'    => 'border border-orange-300 text-orange-700 bg-orange-50',
                            'closed'    => 'border border-gray-300 text-gray-600 bg-gray-50',
                            default     => 'border border-yellow-300 text-yellow-700 bg-yellow-50',
                        } }}">
                        {{ match($campaign->status) {
                            'published' => '公開中',
                            'paused'    => '募集停止',
                            'closed'    => '案内終了',
                            default     => $campaign->status,
                        } }}
                    </span>
                </td>
                <td class="px-3 py-3 text-gray-700">{{ $campaign->getPrMediaLabel() }}</td>
                <td class="px-3 py-3 text-gray-700">{{ $campaign->getTypeLabel() }}</td>
                <td class="px-3 py-3 text-right text-gray-700">{{ number_format($campaign->applications_total_count) }}件</td>
                <td class="px-3 py-3 text-right text-gray-700 bg-pink-50">{{ number_format($campaign->applications_pending_count) }}件</td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-700">案件がありません</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $campaigns->links() }}</div>
@endsection
