@extends('layouts.admin')

@section('title', 'LINE通知')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">LINE通知</h1>

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

    {{-- 送信フォーム --}}
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-4">メッセージ送信</h2>
            <form method="POST" action="{{ route('admin.notifications.line.send') }}">
                @csrf
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        送信先モニター <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">複数選択可（Ctrl/Cmdで選択）</p>
                    <select name="user_ids[]" multiple required
                            class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm h-40">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->name }}
                                {{ $user->line_user_id ? '' : '（LINE未連携）' }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_ids')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        メッセージ <span class="text-red-500">*</span>
                    </label>
                    <textarea name="message" rows="5" required
                              class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm @error('message') border-red-400 @enderror"
                              placeholder="送信するメッセージを入力...">{{ old('message') }}</textarea>
                    @error('message')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit"
                        onclick="return confirm('選択したモニターにLINEを送信しますか？')"
                        class="w-full bg-green-500 text-white py-2 rounded hover:bg-green-600 text-sm font-medium">
                    LINE送信
                </button>
            </form>
        </div>
    </div>

    {{-- 送信履歴 --}}
    <div class="lg:col-span-2">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">送信履歴</h2>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left">送信日時</th>
                        <th class="px-4 py-3 text-left">モニター</th>
                        <th class="px-4 py-3 text-left">種別</th>
                        <th class="px-4 py-3 text-left">メッセージ</th>
                        <th class="px-4 py-3 text-left">結果</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $log->sent_at->format('m/d H:i') }}</td>
                        <td class="px-4 py-3 dark:text-gray-200">{{ $log->user->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $log->notification_type }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-xs truncate">{{ $log->message }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-1.5 py-0.5 rounded
                                {{ $log->status === 'sent' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300' }}">
                                {{ $log->status === 'sent' ? '送信済' : '失敗' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">送信履歴がありません</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
