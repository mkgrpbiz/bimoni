@extends('layouts.portal')
@section('title', '報酬管理')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-lg font-bold text-gray-800">報酬管理</h1>
    <div class="flex gap-1">
        <a href="?mode=all{{ $childId ? '&child_id='.$childId : '' }}"
           class="px-3 py-1.5 rounded text-sm {{ $mode === 'all' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 border' }}">累計</a>
        <a href="?mode=month{{ $childId ? '&child_id='.$childId : '' }}"
           class="px-3 py-1.5 rounded text-sm {{ $mode === 'month' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 border' }}">月次</a>
    </div>
</div>

{{-- 子フィルター（親のみ） --}}
@if(!$agent->parent_id && $agent->children->count())
<div class="flex gap-2 mb-4 flex-wrap">
    <a href="?mode={{ $mode }}" class="px-3 py-1 rounded text-xs border {{ !$childId ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-700' }}">全体</a>
    @foreach($agent->children as $child)
    <a href="?mode={{ $mode }}&child_id={{ $child->id }}"
       class="px-3 py-1 rounded text-xs border {{ $childId == $child->id ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700' }}">
        {{ $child->name }}
    </a>
    @endforeach
</div>
@endif

{{-- コードフィルター --}}
@if($codeOptions->count() > 1)
<form method="GET" class="flex gap-2 items-center mb-4">
    <input type="hidden" name="mode" value="{{ $mode }}">
    @if($childId)<input type="hidden" name="child_id" value="{{ $childId }}">@endif
    @if($mode === 'month' && $month)<input type="hidden" name="month" value="{{ $month->format('Y-m') }}">@endif
    <select name="code_filter" onchange="this.form.submit()"
            class="border rounded px-2 py-1.5 text-sm flex-1">
        <option value="">全コード</option>
        @foreach($codeOptions as $code => $label)
        <option value="{{ $code }}" @selected($codeFilter === $code)>{{ $label }}</option>
        @endforeach
    </select>
    @if($codeFilter)
    <a href="?mode={{ $mode }}{{ $childId ? '&child_id='.$childId : '' }}{{ $mode === 'month' && $month ? '&month='.$month->format('Y-m') : '' }}"
       class="text-xs text-gray-500 hover:text-gray-700 shrink-0">✕ 解除</a>
    @endif
</form>
@endif

@if($mode === 'month')
<form method="GET" class="flex gap-2 items-center mb-4">
    <input type="hidden" name="mode" value="month">
    @if($childId)<input type="hidden" name="child_id" value="{{ $childId }}">@endif
    @if($codeFilter)<input type="hidden" name="code_filter" value="{{ $codeFilter }}">@endif
    <input type="month" name="month" value="{{ $month->format('Y-m') }}"
           class="border rounded px-2 py-1.5 text-sm flex-1">
    <button type="submit" class="bg-gray-800 text-white px-4 py-1.5 rounded text-sm shrink-0">表示</button>
</form>

{{-- 2ヶ月ブロック --}}
<div class="grid grid-cols-2 gap-3 mb-5">
    @foreach($block as $b)
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 mb-1">{{ $b['month']->format('Y年n月') }}分</p>
        @if($isCombinedParentView)
        <p class="text-xs text-gray-500">全体紹介報酬</p>
        <p class="text-lg font-bold text-gray-800">¥{{ number_format($b['total']) }}</p>
        <p class="text-xs text-gray-500 mt-1">子支払総額</p>
        <p class="text-lg font-bold text-pink-600">¥{{ number_format($b['child_payout']) }}</p>
        @else
        <p class="text-xl font-bold text-gray-800">¥{{ number_format($b['total']) }}</p>
        @endif
        <p class="text-xs text-gray-400 mt-1">支払予定: {{ $b['pay_date']->format('n月末') }}</p>
    </div>
    @endforeach
</div>
@endif

{{-- PC: テーブル --}}
<div class="hidden md:block bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-700">
            <tr>
                <th class="px-4 py-3 text-left">案件名</th>
                <th class="px-4 py-3 text-right">件数</th>
                @if($isCombinedParentView)
                <th class="px-4 py-3 text-right">全体紹介報酬</th>
                <th class="px-4 py-3 text-right">子支払総額</th>
                @else
                <th class="px-4 py-3 text-right">紹介単価</th>
                @if($targetAgent->parent_id && !$agent->parent_id)
                <th class="px-4 py-3 text-right">差額</th>
                @endif
                <th class="px-4 py-3 text-right">合計</th>
                @endif
                <th class="px-4 py-3 text-center">全否認</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($campaignGroups as $row)
            @php $isAllDenied = $row['count'] === 0 && $row['all_denied'] > 0; @endphp
            <tr class="hover:bg-gray-50 {{ $isAllDenied ? 'opacity-50' : '' }}">
                <td class="px-4 py-3 text-gray-800">{{ $row['campaign']?->title ?? '-' }}</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ $row['count'] }}件</td>
                @if($isCombinedParentView)
                <td class="px-4 py-3 text-right font-bold text-gray-800">¥{{ number_format($isAllDenied ? 0 : $row['total']) }}</td>
                <td class="px-4 py-3 text-right font-bold text-pink-600">¥{{ number_format($isAllDenied ? 0 : $row['child_payout']) }}</td>
                @else
                <td class="px-4 py-3 text-right text-gray-700">¥{{ number_format($row['reward']) }}</td>
                @if($targetAgent->parent_id && !$agent->parent_id)
                <td class="px-4 py-3 text-right text-gray-500 text-xs">(差額 ¥{{ number_format($row['diff']) }})</td>
                @endif
                <td class="px-4 py-3 text-right font-bold text-gray-800">
                    ¥{{ number_format($isAllDenied ? 0 : $row['total']) }}
                </td>
                @endif
                <td class="px-4 py-3 text-center">
                    @if($row['all_denied'] > 0)
                        <span class="text-xs bg-gray-800 text-white px-2 py-0.5 rounded">全否認</span>
                    @else
                        <span class="text-xs text-gray-300">-</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">データがありません</td></tr>
            @endforelse
        </tbody>
        @if($campaignGroups->isNotEmpty())
        <tfoot class="bg-gray-50">
            <tr>
                @if($isCombinedParentView)
                <td colspan="2" class="px-4 py-3 text-right font-bold text-gray-700">合計</td>
                <td class="px-4 py-3 text-right font-bold text-gray-800">¥{{ number_format($grandTotal) }}</td>
                <td class="px-4 py-3 text-right font-bold text-pink-600">¥{{ number_format($grandChildPayout) }}</td>
                @else
                <td colspan="{{ ($targetAgent->parent_id && !$agent->parent_id) ? 4 : 3 }}" class="px-4 py-3 text-right font-bold text-gray-700">合計</td>
                <td class="px-4 py-3 text-right font-bold text-gray-800">¥{{ number_format($grandTotal) }}</td>
                @endif
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

{{-- スマホ: カード --}}
<div class="md:hidden space-y-3">
    @forelse($campaignGroups as $row)
    @php $isAllDenied = $row['count'] === 0 && $row['all_denied'] > 0; @endphp
    <div class="bg-white rounded-lg shadow px-4 py-3 {{ $isAllDenied ? 'opacity-60' : '' }}">
        <div class="flex items-start justify-between mb-1">
            <p class="font-medium text-gray-800 text-sm flex-1">{{ $row['campaign']?->title ?? '-' }}</p>
            @if($row['all_denied'] > 0)
                <span class="text-xs bg-gray-800 text-white px-2 py-0.5 rounded ml-2 shrink-0">全否認</span>
            @endif
        </div>
        @if($isCombinedParentView)
        <div class="flex items-center justify-between mt-2 text-sm">
            <span class="text-xs text-gray-500">{{ $row['count'] }}件・全体紹介報酬</span>
            <span class="font-bold text-gray-800">¥{{ number_format($isAllDenied ? 0 : $row['total']) }}</span>
        </div>
        <div class="flex items-center justify-between mt-1 text-sm">
            <span class="text-xs text-gray-500">子支払総額</span>
            <span class="font-bold text-pink-600">¥{{ number_format($isAllDenied ? 0 : $row['child_payout']) }}</span>
        </div>
        @else
        <div class="flex items-center justify-between mt-2 text-sm">
            <span class="text-xs text-gray-500">{{ $row['count'] }}件 × ¥{{ number_format($row['reward']) }}</span>
            <span class="font-bold text-gray-800">¥{{ number_format($isAllDenied ? 0 : $row['total']) }}</span>
        </div>
        @if($targetAgent->parent_id && !$agent->parent_id && $row['diff'] > 0)
        <p class="text-xs text-gray-400 mt-1">差額 ¥{{ number_format($row['diff']) }}</p>
        @endif
        @endif
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-400">データがありません</div>
    @endforelse
    @if($campaignGroups->isNotEmpty())
    <div class="bg-gray-800 text-white rounded-lg px-4 py-3">
        @if($isCombinedParentView)
        <div class="flex justify-between">
            <span class="font-medium">合計（全体紹介報酬）</span>
            <span class="font-bold text-lg">¥{{ number_format($grandTotal) }}</span>
        </div>
        <div class="flex justify-between mt-1">
            <span class="font-medium">子支払総額</span>
            <span class="font-bold text-lg text-pink-300">¥{{ number_format($grandChildPayout) }}</span>
        </div>
        @else
        <div class="flex justify-between">
            <span class="font-medium">合計</span>
            <span class="font-bold text-lg">¥{{ number_format($grandTotal) }}</span>
        </div>
        @endif
    </div>
    @endif
</div>

@endsection
