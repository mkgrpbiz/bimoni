@extends('layouts.portal')
@section('title', 'ユーザー管理')
@section('content')
@php
    $qs = fn($extra = []) => http_build_query(array_filter(array_merge(
        ['mode' => $mode, 'month' => $mode === 'month' ? $month->format('Y-m') : null,
         'child_id' => $childId, 'code_filter' => $codeFilter],
        $extra
    ), fn($v) => $v !== null && $v !== ''));
@endphp

<div class="flex items-center justify-between mb-4">
    <h1 class="text-lg font-bold text-gray-800">ユーザー管理</h1>
    <div class="flex gap-1">
        <a href="?{{ $qs(['mode' => 'all']) }}"
           class="px-3 py-1.5 rounded text-sm {{ $mode === 'all' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 border' }}">累計</a>
        <a href="?{{ $qs(['mode' => 'month']) }}"
           class="px-3 py-1.5 rounded text-sm {{ $mode === 'month' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 border' }}">月次</a>
    </div>
</div>

{{-- 子フィルター（親のみ） --}}
@if(!$agent->parent_id && $agent->children->count())
<div class="flex gap-2 mb-4 flex-wrap">
    <a href="?{{ $qs(['child_id' => null, 'code_filter' => null]) }}"
       class="px-3 py-1 rounded text-xs border {{ !$childId ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-700' }}">全体</a>
    <a href="?{{ $qs(['child_id' => 'parent', 'code_filter' => null]) }}"
       class="px-3 py-1 rounded text-xs border {{ $childId === 'parent' ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-700' }}">親のみ</a>
    @foreach($agent->children as $child)
    <a href="?{{ $qs(['child_id' => $child->id, 'code_filter' => null]) }}"
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
    @if($mode === 'month')<input type="hidden" name="month" value="{{ $month->format('Y-m') }}">@endif
    <select name="code_filter" onchange="this.form.submit()" class="border rounded px-2 py-1.5 text-sm flex-1">
        <option value="">全コード</option>
        @foreach($codeOptions as $code => $label)
        <option value="{{ $code }}" @selected($codeFilter === $code)>{{ $label }}</option>
        @endforeach
    </select>
    @if($codeFilter)
    <a href="?{{ $qs(['code_filter' => null]) }}" class="text-xs text-gray-500 hover:text-gray-700 shrink-0">✕ 解除</a>
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
@endif

{{-- 集計 --}}
<div class="grid grid-cols-3 gap-3 mb-5">
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">登録</p>
        <p class="text-2xl font-bold text-gray-800">{{ $totalRegistered }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">応募</p>
        <p class="text-2xl font-bold text-gray-800">{{ $totalApps }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 mb-1">報告(承認)</p>
        <p class="text-2xl font-bold text-green-600">{{ $totalReports }}</p>
    </div>
</div>

{{-- PC: テーブル --}}
<div class="hidden md:block bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-700">
            <tr>
                <th class="px-4 py-3 text-left">登録日</th>
                <th class="px-4 py-3 text-left">ユーザーID</th>
                <th class="px-4 py-3 text-left">登録コード</th>
                <th class="px-4 py-3 text-left">LINE表示名</th>
                <th class="px-4 py-3 text-left">名前</th>
                <th class="px-4 py-3 text-left">フリガナ</th>
                <th class="px-4 py-3 text-right">応募数</th>
                <th class="px-4 py-3 text-right">報告数</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-xs text-gray-500">{{ $user->created_at?->format('Y/m/d') }}</td>
                <td class="px-4 py-3 font-mono text-xs">{{ $user->bimoni_user_id ?? '-' }}</td>
                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $user->referred_by_code ?? '-' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $user->line_display_name ?? '（未登録）' }}</td>
                <td class="px-4 py-3 text-gray-800">{{ $user->name ?? '-' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $user->name_kana ?? '-' }}</td>
                <td class="px-4 py-3 text-right">{{ $appCounts->get($user->id, 0) }}</td>
                <td class="px-4 py-3 text-right font-medium text-green-600">{{ $reportCounts->get($user->id, 0) }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">ユーザーがいません</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- スマホ: カード --}}
<div class="md:hidden space-y-3">
    @forelse($users as $user)
    <div class="bg-white rounded-lg shadow px-4 py-3">
        <div class="flex items-start justify-between mb-1">
            <div>
                <p class="font-medium text-gray-800 text-sm">{{ $user->name ?? '（未登録）' }}</p>
                <p class="text-xs text-gray-500">{{ $user->name_kana ?? '' }}</p>
            </div>
            <div class="text-right shrink-0 ml-2">
                <p class="font-mono text-xs text-gray-500">{{ $user->bimoni_user_id ?? '-' }}</p>
                <p class="font-mono text-xs text-gray-400">{{ $user->referred_by_code ?? '-' }}</p>
                <p class="text-xs text-gray-400">{{ $user->created_at?->format('Y/m/d') }}</p>
            </div>
        </div>
        <div class="flex gap-3 mt-2 text-xs text-gray-600 border-t pt-2">
            <span>LINE: <span class="text-gray-800">{{ $user->line_display_name ?? '-' }}</span></span>
            <span class="ml-auto">応募 <span class="font-bold text-gray-800">{{ $appCounts->get($user->id, 0) }}</span></span>
            <span>報告 <span class="font-bold text-green-600">{{ $reportCounts->get($user->id, 0) }}</span></span>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-400">ユーザーがいません</div>
    @endforelse
</div>
@endsection
