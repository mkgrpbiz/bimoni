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
                <th class="px-3 py-2 text-center whitespace-nowrap">添付画像</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">ステータス</th>
                <th class="px-3 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($reports as $report)
            @php $user = $report->user; @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 whitespace-nowrap text-gray-700">{{ $report->created_at->format('m/d H:i') }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $user?->erme_respondent_id ?? '-' }}</td>
                <td class="px-3 py-2 font-mono text-gray-700">{{ $user?->referred_by_code ?? '-' }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $user?->line_display_name ?? '-' }}</td>
                <td class="px-3 py-2 font-medium whitespace-nowrap">{{ $user?->name ?? '（未登録）' }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $user?->name_kana ?? '-' }}</td>
                <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $report->campaign->title ?? '-' }}</td>
                <td class="px-3 py-2 text-right whitespace-nowrap font-medium text-pink-600">
                    @if($report->campaign?->cooperation_fee)
                        ¥{{ number_format($report->campaign->cooperation_fee) }}
                    @else
                        -
                    @endif
                </td>
                <td class="px-3 py-2 text-center">
                    @php $imgs = $report->images; @endphp
                    @if($imgs->isNotEmpty())
                        <button type="button"
                                onclick="openImgModal({{ json_encode($imgs->pluck('path')->map(fn($p) => asset('storage/'.$p))->values()) }})"
                                class="bg-blue-500 text-white px-2 py-0.5 rounded hover:bg-blue-600 text-xs whitespace-nowrap">
                            画像確認 ({{ $imgs->count() }})
                        </button>
                    @else
                        <span class="text-gray-400">なし</span>
                    @endif
                </td>
                <td class="px-3 py-2 whitespace-nowrap">
                    <span class="px-1.5 py-0.5 rounded text-xs {{ $report->getStatusColor() }}">
                        {{ $report->getStatusLabel() }}
                    </span>
                </td>
                <td class="px-3 py-2 text-right">
                    <a href="{{ route('admin.reports.show', $report) }}"
                       class="bg-pink-500 text-white px-2 py-1 rounded hover:bg-pink-600 text-xs">確認</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="px-4 py-8 text-center text-gray-700">報告がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $reports->links() }}</div>

{{-- 画像確認モーダル --}}
<div id="imgModal"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.75);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:16px;max-width:600px;width:calc(100% - 32px);max-height:90vh;overflow-y:auto;position:relative;">
        <button onclick="document.getElementById('imgModal').style.display='none'"
                style="position:absolute;top:10px;right:12px;background:none;border:none;font-size:20px;cursor:pointer;color:#374151;">✕</button>
        <p class="font-bold text-gray-700 mb-3 text-sm">添付画像</p>
        <div id="imgModalGrid" class="grid grid-cols-2 gap-3"></div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openImgModal(urls) {
    var grid = document.getElementById('imgModalGrid');
    grid.innerHTML = '';
    urls.forEach(function(url) {
        var a = document.createElement('a');
        a.href = url;
        a.target = '_blank';
        var img = document.createElement('img');
        img.src = url;
        img.style.cssText = 'width:100%;border-radius:8px;object-fit:cover;aspect-ratio:4/3;';
        a.appendChild(img);
        grid.appendChild(a);
    });
    document.getElementById('imgModal').style.display = 'flex';
}
</script>
@endpush
