@extends('layouts.admin')

@section('title', '日別件数管理')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold text-gray-800">日別件数管理</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm whitespace-pre-wrap">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4 text-sm">{{ $errors->first() }}</div>
@endif

{{-- TSVインポート --}}
<div class="bg-white rounded-lg shadow p-4 mb-5">
    <h2 class="font-bold text-gray-700 mb-3 text-sm">CSV一括インポート</h2>
    <p class="text-xs text-gray-500 mb-3">
        ヘッダー行（「案件名」または「商品名」列＋日付列）、以降に案件名と日別件数が入ったCSVをアップロードしてください。<br>
        案件名は管理画面のタイトルと一致（または部分一致）する必要があります。
    </p>
    <form method="POST" action="{{ route('admin.daily_slots.import') }}" enctype="multipart/form-data"
          class="flex items-center gap-3">
        @csrf
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="file" name="tsv_file" accept=".csv,.txt" required
               class="border rounded px-2 py-1.5 text-sm">
        <button type="submit"
                class="bg-pink-500 text-white px-4 py-1.5 rounded text-sm hover:bg-pink-600">
            インポート
        </button>
    </form>
</div>

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
    <a href="{{ route('admin.daily_slots.index', ['status' => $key]) }}"
       class="flex items-center gap-1.5 px-5 py-2.5 text-sm font-medium border-b-2 transition-colors
              {{ $status === $key
                  ? 'border-pink-500 text-pink-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        {{ $tab['label'] }}
        <span class="text-xs font-bold px-1.5 py-0.5 rounded-full text-white {{ $tab['color'] }}">
            {{ $statusCounts->get($key, 0) }}
        </span>
    </a>
    @endforeach
</div>

{{-- 案件一覧テーブル --}}
<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-800 text-xs">
            <tr>
                <th class="px-4 py-3 text-left">案件名</th>
                <th class="px-3 py-3 text-right whitespace-nowrap">今月目標</th>
                <th class="px-3 py-3 text-right whitespace-nowrap">本日（{{ now()->format('m/d') }}）</th>
                <th class="px-3 py-3 text-right whitespace-nowrap">明日（{{ now()->addDay()->format('m/d') }}）</th>
                <th class="px-3 py-3 text-right whitespace-nowrap">明後日（{{ now()->addDays(2)->format('m/d') }}）</th>
                <th class="px-3 py-3 text-center">詳細</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($campaigns as $campaign)
            @php
                $campaignSlots = $slots->get($campaign->id, collect());
                $monthTotal    = $campaignSlots->sum('planned_count');
                $todaySlot     = $campaignSlots->get($today);
                $tomorrowSlot  = $campaignSlots->get($tomorrow);
                $dayAfterSlot  = $campaignSlots->get($dayAfter);
            @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">{{ $campaign->title }}</td>
                <td class="px-3 py-3 text-right font-bold">{{ number_format($monthTotal) }}</td>
                <td class="px-3 py-3 text-right {{ $todaySlot ? 'text-pink-600 font-bold' : 'text-gray-400' }}">
                    {{ $todaySlot ? $todaySlot->planned_count : '-' }}
                </td>
                <td class="px-3 py-3 text-right {{ $tomorrowSlot ? 'text-gray-800' : 'text-gray-400' }}">
                    {{ $tomorrowSlot ? $tomorrowSlot->planned_count : '-' }}
                </td>
                <td class="px-3 py-3 text-right {{ $dayAfterSlot ? 'text-gray-800' : 'text-gray-400' }}">
                    {{ $dayAfterSlot ? $dayAfterSlot->planned_count : '-' }}
                </td>
                <td class="px-3 py-3 text-center">
                    <a href="{{ route('admin.campaigns.daily_slots.index', $campaign) }}"
                       class="text-xs bg-pink-500 text-white px-3 py-1 rounded hover:bg-pink-600">
                        詳細・編集
                    </a>
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
@endsection
