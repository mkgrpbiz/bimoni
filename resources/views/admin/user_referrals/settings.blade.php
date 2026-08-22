@extends('layouts.admin')

@section('title', 'ユーザー紹介管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">ユーザー紹介管理</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-800 rounded px-4 py-2 mb-4">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-lg shadow p-6 max-w-lg">
    <form method="POST" action="{{ route('admin.user_referral_settings.update') }}" class="space-y-5">
        @csrf @method('PATCH')

        <div class="flex items-center gap-3">
            <input type="hidden" name="enabled" value="0">
            <input type="checkbox" name="enabled" value="1" id="f-enabled"
                   {{ $setting->enabled ? 'checked' : '' }}
                   class="rounded border-gray-300 text-pink-500 w-5 h-5">
            <label for="f-enabled" class="text-sm font-medium text-gray-700">お友達紹介プログラムを有効にする</label>
        </div>
        <p class="text-xs text-gray-400 -mt-3">OFFにすると新規の紹介登録・報酬付与が停止します（既に確定した報酬には影響しません）。</p>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">報酬額（紹介された人の初回報告承認時、1回のみ）</label>
            <div class="relative w-48">
                <input type="number" name="reward_amount" min="0"
                       value="{{ old('reward_amount', $setting->reward_amount) }}" required
                       class="w-full border rounded px-3 py-2 text-sm">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">P</span>
            </div>
            @error('reward_amount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="bg-pink-500 text-white px-5 py-2 rounded hover:bg-pink-600 text-sm font-medium">
            保存
        </button>
    </form>
</div>
@endsection
