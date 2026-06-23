@extends('layouts.admin')

@section('title', $campaign->title . ' 応募者一覧')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.campaigns.show', $campaign) }}" class="text-gray-400 hover:text-gray-600">← 案件詳細</a>
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $campaign->title }} 応募者一覧</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- フィルター --}}
<form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-4 flex gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ステータス</label>
        <select name="status" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
            <option value="">すべて</option>
            @foreach(['pending'=>'審査中','selected'=>'当選','rejected'=>'落選','line_contacted'=>'LINE案内済','scheduled'=>'日程確定','completed'=>'実施完了','reported'=>'報告済','approved'=>'承認済'] as $val => $label)
                <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">絞り込み</button>
</form>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">モニター</th>
                <th class="px-4 py-3 text-left">ステータス</th>
                <th class="px-4 py-3 text-left">応募日</th>
                <th class="px-4 py-3 text-left">当選日</th>
                <th class="px-4 py-3 text-left">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($applications as $app)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 font-medium">{{ $app->user->name ?? '（未登録）' }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded text-xs {{ $app->getStatusColor() }}">
                        {{ $app->getStatusLabel() }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $app->applied_at->format('Y/m/d') }}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $app->selected_at?->format('Y/m/d') ?? '-' }}</td>
                <td class="px-4 py-3">
                    <div class="flex gap-2 items-center">
                        @if($app->status === 'pending')
                        <form method="POST" action="{{ route('admin.applications.status', $app) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="selected">
                            <button type="submit" class="text-xs bg-blue-600 text-white px-2 py-0.5 rounded hover:bg-blue-700">当選</button>
                        </form>
                        <form method="POST" action="{{ route('admin.applications.status', $app) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" class="text-xs bg-red-500 text-white px-2 py-0.5 rounded hover:bg-red-600">落選</button>
                        </form>
                        @endif
                        <a href="{{ route('admin.applications.show', $app) }}" class="text-xs text-pink-600 dark:text-pink-400 hover:underline">詳細</a>
                    </div>
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
