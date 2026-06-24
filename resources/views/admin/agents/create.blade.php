@extends('layouts.admin')
@section('title', '親代理店を追加')
@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.agents.index') }}" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">← 代理店一覧</a>
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">親代理店を追加</h1>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-md">
    <form method="POST" action="{{ route('admin.agents.store') }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">代理店名 <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm @error('name') border-red-400 @enderror">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">作成後にポータルURLと紹介コードが自動発行されます。</p>
        <div class="flex gap-3">
            <button type="submit" class="bg-pink-500 text-white px-6 py-2 rounded text-sm hover:bg-pink-600">作成する</button>
            <a href="{{ route('admin.agents.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded text-sm hover:bg-gray-600">キャンセル</a>
        </div>
    </form>
</div>
@endsection
