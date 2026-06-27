@extends('layouts.portal')
@section('title', '子代理店を追加')
@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('portal.children') }}" class="bg-gray-800 text-white px-3 py-1.5 rounded text-sm hover:bg-gray-700">← 子代理店一覧</a>
    <h1 class="text-xl font-bold text-gray-800">子代理店を追加</h1>
</div>

<div class="bg-white rounded-lg shadow p-6 max-w-md">
    @if($errors->any())
        <div class="bg-red-50 text-red-700 px-3 py-2 rounded mb-4 text-sm">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif
    <form method="POST" action="{{ route('portal.children.store') }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">代理店名 <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full border rounded px-3 py-2 text-sm">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">500円案件の報酬（子への支払い額）</label>
            <div class="flex items-center gap-2">
                <span class="text-gray-500">¥</span>
                <input type="number" name="child_reward_500" value="{{ old('child_reward_500', 0) }}"
                       min="0" max="500" step="100" required
                       class="flex-1 border rounded px-3 py-2 text-sm @error('child_reward_500') border-red-400 @enderror">
            </div>
            <p class="text-xs text-gray-400 mt-0.5">0〜500円で入力。差額が親の取り分になります。</p>
            @error('child_reward_500')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">1000円案件の報酬（子への支払い額）</label>
            <div class="flex items-center gap-2">
                <span class="text-gray-500">¥</span>
                <input type="number" name="child_reward_1000" value="{{ old('child_reward_1000', 0) }}"
                       min="0" max="1000" step="100" required
                       class="flex-1 border rounded px-3 py-2 text-sm @error('child_reward_1000') border-red-400 @enderror">
            </div>
            <p class="text-xs text-gray-400 mt-0.5">0〜1000円で入力。差額が親の取り分になります。</p>
            @error('child_reward_1000')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">紹介コード（任意）</label>
            <input type="text" name="code" value="{{ old('code') }}" maxlength="20"
                   placeholder="空欄の場合は自動生成"
                   class="w-full border rounded px-3 py-2 text-sm font-mono @error('code') border-red-400 @enderror">
            @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3">
            <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded text-sm hover:bg-gray-700">作成する</button>
            <a href="{{ route('portal.children') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm hover:bg-gray-300">キャンセル</a>
        </div>
    </form>
</div>
@endsection
