@extends('layouts.admin')

@section('title', '応募管理')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">応募管理</h1>

{{-- フィルター --}}
<form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">モニター名</label>
        <input type="text" name="q" value="{{ request('q') }}"
               class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm w-36" placeholder="氏名">
    </div>
    <div>
        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">案件</label>
        <select name="campaign_id" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
            <option value="">すべて</option>
            @foreach($campaigns as $c)
                <option value="{{ $c->id }}" @selected(request('campaign_id') == $c->id)>{{ $c->title }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ステータス</label>
        <select name="status" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
            <option value="">すべて</option>
            @foreach(['pending'=>'審査中','selected'=>'当選','rejected'=>'落選','line_contacted'=>'LINE案内済','scheduled'=>'日程確定','completed'=>'実施完了','reported'=>'報告済','approved'=>'承認済','point_granted'=>'協力金付与済','cancelled'=>'キャンセル'] as $val => $label)
                <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">絞り込み</button>
    <a href="{{ route('admin.applications.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">リセット</a>
</form>

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">モニター</th>
                <th class="px-4 py-3 text-left">案件</th>
                <th class="px-4 py-3 text-left">ステータス</th>
                <th class="px-4 py-3 text-left">応募日</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($applications as $app)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3">{{ $app->user->name ?? '（未登録）' }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $app->campaign->title }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded text-xs {{ $app->getStatusColor() }}">
                        {{ $app->getStatusLabel() }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $app->applied_at->format('Y/m/d') }}</td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.applications.show', $app) }}" class="text-pink-600 dark:text-pink-400 hover:underline text-xs">詳細</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">応募がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $applications->links() }}</div>
@endsection
