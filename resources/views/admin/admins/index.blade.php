@extends('layouts.admin')

@section('title', '管理者管理')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">管理者管理</h1>
    <a href="{{ route('admin.admins.create') }}"
       class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">
        ＋ 追加
    </a>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4 text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
            <tr>
                <th class="px-4 py-2 text-left">名前</th>
                <th class="px-4 py-2 text-left">メールアドレス</th>
                <th class="px-4 py-2 text-left">役割</th>
                <th class="px-4 py-2 text-left">閲覧可能メニュー</th>
                <th class="px-4 py-2 text-left">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @foreach($admins as $adm)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-2 font-medium dark:text-gray-200">
                    {{ $adm->name }}
                    @if($adm->id === auth('web')->id())
                        <span class="text-xs text-pink-500 ml-1">（自分）</span>
                    @endif
                </td>
                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $adm->email }}</td>
                <td class="px-4 py-2">
                    @if($adm->isAdmin())
                        <span class="bg-pink-100 text-pink-700 px-2 py-0.5 rounded text-xs font-bold">管理者</span>
                    @else
                        <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs font-bold">運用担当</span>
                    @endif
                </td>
                <td class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">
                    @if($adm->isAdmin())
                        <span class="text-green-600">全メニュー</span>
                    @else
                        @php
                            $keys = \App\Http\Controllers\Admin\AdminManagerController::menuKeys();
                            $labels = collect($adm->accessible_menus ?? [])->map(fn($k) => $keys[$k] ?? $k);
                        @endphp
                        {{ $labels->join('、') ?: '（なし）' }}
                    @endif
                </td>
                <td class="px-4 py-2">
                    <div class="flex gap-2">
                        <a href="{{ route('admin.admins.edit', $adm) }}"
                           class="bg-gray-500 text-white px-2 py-1 rounded text-xs hover:bg-gray-600">編集</a>
                        <form method="POST" action="{{ route('admin.admins.reset_password', $adm) }}">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('パスワードを bimoni1234 にリセットしますか？')"
                                    class="bg-yellow-500 text-white px-2 py-1 rounded text-xs hover:bg-yellow-600">
                                PW リセット
                            </button>
                        </form>
                        @if($adm->id !== auth('web')->id())
                        <form method="POST" action="{{ route('admin.admins.destroy', $adm) }}">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('削除しますか？')"
                                    class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600">
                                削除
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
