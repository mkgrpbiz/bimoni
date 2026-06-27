@extends('layouts.portal')
@section('title', 'ユーザー管理')
@section('content')
<h1 class="text-lg font-bold text-gray-800 mb-4">ユーザー管理</h1>

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
                <td class="px-4 py-3 text-gray-700">{{ $user->line_name ?? '（未登録）' }}</td>
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
            <span>LINE: <span class="text-gray-800">{{ $user->line_name ?? '-' }}</span></span>
            <span class="ml-auto">応募 <span class="font-bold text-gray-800">{{ $appCounts->get($user->id, 0) }}</span></span>
            <span>報告 <span class="font-bold text-green-600">{{ $reportCounts->get($user->id, 0) }}</span></span>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-400">ユーザーがいません</div>
    @endforelse
</div>
@endsection
