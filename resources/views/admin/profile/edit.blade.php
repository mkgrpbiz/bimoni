@extends('layouts.admin')

@section('title', 'パスワード・メール変更')

@section('content')
<div class="max-w-md">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">アカウント設定</h1>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.profile.update') }}"
          class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-5">
        @csrf @method('PATCH')

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">メールアドレス <span class="text-red-400">*</span></label>
            <input type="email" name="email" value="{{ old('email', $admin->email) }}" required
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <hr class="border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">パスワードを変更する場合のみ入力してください。</p>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">現在のパスワード</label>
            <input type="password" name="current_password" autocomplete="current-password"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
            @error('current_password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">新しいパスワード</label>
            <input type="password" name="password" autocomplete="new-password"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
            @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">新しいパスワード（確認）</label>
            <input type="password" name="password_confirmation" autocomplete="new-password"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
        </div>

        <button type="submit" class="bg-pink-500 text-white px-6 py-2 rounded hover:bg-pink-600 text-sm font-medium">
            更新
        </button>
    </form>
</div>
@endsection
