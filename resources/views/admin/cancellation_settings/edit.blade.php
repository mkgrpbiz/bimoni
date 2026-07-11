@extends('layouts.admin')

@section('title', '解約方法編集')

@section('content')
<div class="flex items-center gap-3 mb-2">
    <a href="{{ route('admin.cancellation_settings.index') }}"
       class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">← 一覧に戻る</a>
    <h1 class="text-2xl font-bold text-gray-800">{{ $campaign->title }}</h1>
</div>
<p class="text-xs text-gray-400 mb-6">
    ※解約方法の説明文（解約について）は
    <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="text-pink-500 underline">案件編集画面</a>
    で入力してください。ここでは連絡先のみ管理します。
</p>

<form method="POST" action="{{ route('admin.cancellation_settings.update', $campaign) }}"
      class="bg-white rounded-lg shadow p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    @csrf
    @method('PUT')

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">電話番号</label>
        <input type="text" name="cancellation_phone" value="{{ old('cancellation_phone', $campaign->cancellation_phone ?? '') }}"
               class="w-full border rounded px-3 py-2 text-sm @error('cancellation_phone') border-red-400 @enderror"
               placeholder="0120-000-000">
        @error('cancellation_phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">受付時間</label>
        <input type="text" name="cancellation_hours" value="{{ old('cancellation_hours', $campaign->cancellation_hours ?? '') }}"
               class="w-full border rounded px-3 py-2 text-sm @error('cancellation_hours') border-red-400 @enderror"
               placeholder="平日9:00〜18:00（土日祝除く）">
        @error('cancellation_hours')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">マイページURL</label>
        <input type="text" name="cancellation_mypage_url" value="{{ old('cancellation_mypage_url', $campaign->cancellation_mypage_url ?? '') }}"
               class="w-full border rounded px-3 py-2 text-sm @error('cancellation_mypage_url') border-red-400 @enderror"
               placeholder="https://example.com/shop/customers/sign_in">
        @error('cancellation_mypage_url')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
        <input type="email" name="cancellation_email" value="{{ old('cancellation_email', $campaign->cancellation_email ?? '') }}"
               class="w-full border rounded px-3 py-2 text-sm @error('cancellation_email') border-red-400 @enderror"
               placeholder="support@example.com">
        @error('cancellation_email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2 flex justify-end gap-2 pt-2 border-t">
        <button type="submit" class="bg-pink-500 text-white px-6 py-2 rounded hover:bg-pink-600 text-sm">更新する</button>
    </div>
</form>
@endsection
