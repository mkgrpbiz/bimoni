@extends('layouts.admin')

@section('title', '報告管理')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">報告管理</h1>

<form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ステータス</label>
        <select name="status" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
            <option value="">すべて</option>
            <option value="pending"  @selected(request('status') === 'pending')>審査中</option>
            <option value="approved" @selected(request('status') === 'approved')>承認済</option>
            <option value="rejected" @selected(request('status') === 'rejected')>差戻し</option>
        </select>
    </div>
    <button type="submit" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">絞り込み</button>
    <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">リセット</a>
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
                <th class="px-4 py-3 text-left">報告日</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($reports as $report)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 dark:text-gray-200">{{ $report->user->name ?? '（未登録）' }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $report->campaign->title }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded text-xs {{ $report->getStatusColor() }}">
                        {{ $report->getStatusLabel() }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $report->created_at->format('Y/m/d') }}</td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.reports.show', $report) }}" class="text-pink-600 dark:text-pink-400 hover:underline text-xs">確認</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">報告がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $reports->links() }}</div>
@endsection
