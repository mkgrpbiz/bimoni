@extends('layouts.admin')
@section('title', '代理店管理')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">代理店管理</h1>
    <a href="{{ route('admin.agents.create') }}" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">＋ 親代理店を追加</a>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">代理店名</th>
                <th class="px-4 py-3 text-right">子代理店数</th>
                <th class="px-4 py-3 text-right">コード数</th>
                <th class="px-4 py-3 text-right">登録数</th>
                <th class="px-4 py-3 text-right">応募数</th>
                <th class="px-4 py-3 text-right">報告数</th>
                <th class="px-4 py-3 text-center">詳細</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($agents as $agent)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $agent->name }}</td>
                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $agent->children->count() }}</td>
                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $agent->codes->count() }}</td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">{{ $registeredMap[$agent->id] ?? 0 }}</td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">{{ $appMap[$agent->id] ?? 0 }}</td>
                <td class="px-4 py-3 text-right font-medium text-green-600 dark:text-green-400">{{ $reportMap[$agent->id] ?? 0 }}</td>
                <td class="px-4 py-3 text-center">
                    <a href="{{ route('admin.agents.show', $agent) }}"
                       class="bg-pink-500 text-white text-xs px-3 py-1 rounded hover:bg-pink-600">詳細</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500">代理店がまだありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
