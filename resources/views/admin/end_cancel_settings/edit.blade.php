@extends('layouts.admin')
@section('title', '終了キャンセル自動送信')
@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-6">終了キャンセル自動送信</h1>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="max-w-3xl">
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-xs text-gray-600 mb-4">
            案件を「終了」ステータスに変更した際、打診中・案内予約中の応募を自動的に応募状態へ戻し、対象ユーザーへこの内容でLINE通知を送信します。
        </p>

        <form method="POST" action="{{ route('admin.end_cancel_settings.update') }}">
            @csrf @method('PATCH')

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">送信時間帯</label>
                <p class="text-xs text-gray-500 mb-2">この時間内なら即時送信、時間外は開始時刻に自動送信されます。</p>
                <div class="flex items-center gap-2">
                    <select name="send_start_hour" class="border rounded px-2 py-1.5 text-sm">
                        @for($h = 0; $h < 24; $h++)
                        <option value="{{ $h }}" @selected(old('send_start_hour', $setting->send_start_hour) == $h)>{{ $h }}:00</option>
                        @endfor
                    </select>
                    <span class="text-gray-500 text-sm">〜</span>
                    <select name="send_end_hour" class="border rounded px-2 py-1.5 text-sm">
                        @for($h = 0; $h < 24; $h++)
                        <option value="{{ $h }}" @selected(old('send_end_hour', $setting->send_end_hour) == $h)>{{ $h }}:00</option>
                        @endfor
                    </select>
                </div>
                @error('send_start_hour')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                @error('send_end_hour')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded p-3 mb-3 text-xs text-gray-600">
                <p class="font-medium text-gray-700 mb-1">使用できるコード（自動で値に置換されます）</p>
                <div class="grid grid-cols-2 gap-1 font-mono">
                    <span>@{{商品名}}</span><span class="text-gray-400">→ 商品名</span>
                    <span>@{{初回購入費}}</span><span class="text-gray-400">→ 初回購入費（円）</span>
                    <span>@{{モニター協力金}}</span><span class="text-gray-400">→ モニター協力金（円）</span>
                    <span>@{{解約について}}</span><span class="text-gray-400">→ 解約についての内容</span>
                    <span>@{{モニター案内文}}</span><span class="text-gray-400">→ モニター案内文の内容</span>
                    <span>@{{リンク}}</span><span class="text-gray-400">→ リンクURL</span>
                </div>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">終了案内メッセージ</label>
                <textarea name="message_template" rows="6"
                          class="w-full border rounded px-3 py-2 text-sm font-mono">{{ old('message_template', $setting->message_template) }}</textarea>
                @error('message_template')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="bg-pink-500 text-white px-6 py-2 rounded hover:bg-pink-600 text-sm">
                更新する
            </button>
        </form>
    </div>
</div>
@endsection
