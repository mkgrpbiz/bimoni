@extends('layouts.admin')
@section('title', '代理店詳細')
@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.agents.index') }}" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">← 代理店一覧</a>
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $agent->name }}</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    {{-- ポータルURL --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">ポータルURL</h2>
        <div class="flex items-center gap-2">
            <code class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 px-3 py-2 rounded flex-1 break-all">
                {{ $agent->portalUrl() }}
            </code>
            <button onclick="navigator.clipboard.writeText('{{ $agent->portalUrl() }}')"
                    class="bg-pink-500 text-white text-xs px-3 py-1.5 rounded hover:bg-pink-600 shrink-0">コピー</button>
        </div>
    </div>

    {{-- 紹介コード一覧 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-bold text-gray-700 dark:text-gray-200">紹介コード</h2>
            <form method="POST" action="{{ route('admin.agents.add_code', $agent) }}">
                @csrf
                <button type="submit" class="bg-pink-500 text-white text-xs px-3 py-1 rounded hover:bg-pink-600">＋ コード追加</button>
            </form>
        </div>
        <div class="space-y-1">
            @foreach($agent->codes as $code)
            <div class="flex items-center gap-2">
                <span class="font-mono font-bold text-pink-600 dark:text-pink-400">{{ $code->code }}</span>
                <span class="text-xs text-gray-500">{{ $code->label ?? '' }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- 子代理店一覧 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <div class="px-5 py-3 border-b dark:border-gray-700 flex items-center justify-between">
        <h2 class="font-bold text-gray-700 dark:text-gray-200">子代理店一覧</h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">代理店名</th>
                <th class="px-4 py-3 text-left">コード</th>
                <th class="px-4 py-3 text-right">500円報酬</th>
                <th class="px-4 py-3 text-right">1000円報酬</th>
                <th class="px-4 py-3 text-left">ポータルURL</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($agent->children as $child)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $child->name }}</td>
                <td class="px-4 py-3 font-mono text-xs text-gray-800 dark:text-gray-200">
                    {{ $child->codes->pluck('code')->join(', ') }}
                </td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">¥{{ number_format($child->child_reward_500) }}</td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">¥{{ number_format($child->child_reward_1000) }}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <code class="text-xs text-gray-500 truncate max-w-48">{{ $child->portalUrl() }}</code>
                        <button onclick="navigator.clipboard.writeText('{{ $child->portalUrl() }}')"
                                class="bg-gray-500 text-white text-xs px-2 py-0.5 rounded hover:bg-gray-600 shrink-0">コピー</button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-6 text-center text-gray-500">子代理店はまだありません（親ポータルから作成できます）</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
