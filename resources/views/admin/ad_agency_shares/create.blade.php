@extends('layouts.admin')

@section('title', '共有アカウント追加')

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">共有アカウント追加</h1>

    <form method="POST" action="{{ route('admin.ad_agency_shares.store') }}"
          class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">名前 <span class="text-red-400">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">メールアドレス <span class="text-red-400">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">パスワード <span class="text-red-400">*</span></label>
            <input type="text" name="password" value="{{ old('password') }}" required minlength="8"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm font-mono">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">8文字以上。このまま代理店に共有してください。</p>
            @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-pink-500 text-white px-6 py-2 rounded hover:bg-pink-600 text-sm font-medium">追加</button>
            <a href="{{ route('admin.ad_agency_shares.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 text-sm">キャンセル</a>
        </div>
    </form>
</div>
@endsection
