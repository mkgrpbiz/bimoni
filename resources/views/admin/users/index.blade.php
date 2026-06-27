@extends('layouts.admin')

@section('title', 'ユーザー管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">ユーザー管理</h1>
</div>

<form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-4 flex gap-3 items-end">
    <div class="flex-1">
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">ユーザーID・氏名・フリガナで検索</label>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="BMN00100001 または 山田 太郎"
               class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">検索</button>
    <a href="{{ route('admin.users.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded text-sm hover:bg-gray-600">リセット</a>
</form>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm whitespace-nowrap">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">登録日時</th>
                <th class="px-4 py-3 text-left">ユーザーID</th>
                <th class="px-4 py-3 text-left">登録コード</th>
                <th class="px-4 py-3 text-left">LINE表示名</th>
                <th class="px-4 py-3 text-left">名前</th>
                <th class="px-4 py-3 text-left">フリガナ</th>
                <th class="px-4 py-3 text-center">性別</th>
                <th class="px-4 py-3 text-center">年齢</th>
                <th class="px-4 py-3 text-right">応募数</th>
                <th class="px-4 py-3 text-right">実施数</th>
                <th class="px-4 py-3 text-right">支払待ち合計</th>
                <th class="px-4 py-3 text-right">累計支払い</th>
                <th class="px-4 py-3 text-center">詳細</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-400">
                    {{ $user->created_at?->format('Y/m/d') ?? '-' }}
                </td>
                <td class="px-4 py-3 font-mono text-xs text-gray-800 dark:text-gray-200">
                    {{ $user->bimoni_user_id ?? '-' }}
                </td>
                <td class="px-4 py-3 font-mono text-xs text-gray-800 dark:text-gray-200">
                    {{ $user->referred_by_code ?? '-' }}
                </td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200 max-w-32 truncate">
                    {{ $user->line_display_name ?? '-' }}
                </td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                    {{ $user->name ?? '（未登録）' }}
                </td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-400">
                    {{ $user->name_kana ?? '-' }}
                </td>
                <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-400">
                    {{ match($user->gender) { 'male' => '男', 'female' => '女', default => '-' } }}
                </td>
                <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-400">
                    {{ $user->birthdate ? \Carbon\Carbon::parse($user->birthdate)->age : '-' }}
                </td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">
                    {{ $user->applications_count }}
                </td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">
                    {{ $completedMap->get($user->id, 0) }}
                </td>
                <td class="px-4 py-3 text-right font-medium text-yellow-600 dark:text-yellow-400">
                    ¥{{ number_format($pendingMap->get($user->id, 0)) }}
                </td>
                <td class="px-4 py-3 text-right font-medium text-green-600 dark:text-green-400">
                    ¥{{ number_format($paidMap->get($user->id, 0)) }}
                </td>
                <td class="px-4 py-3 text-center">
                    <a href="{{ route('admin.users.show', $user) }}"
                       class="bg-pink-500 text-white text-xs px-3 py-1 rounded hover:bg-pink-600">詳細</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="13" class="px-4 py-8 text-center text-gray-700 dark:text-gray-500">ユーザーがいません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
