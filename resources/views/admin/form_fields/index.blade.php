@extends('layouts.admin')
@section('title', '編集')
@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-6">編集</h1>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4 text-sm">{{ session('error') }}</div>
@endif

<div class="space-y-2 max-w-3xl">

    {{-- 利用規約 --}}
    @foreach([['terms', $terms], ['privacy', $privacy]] as [$slug, $page])
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <button type="button" class="accordion-header w-full text-left px-5 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
            <span class="font-semibold text-gray-700">{{ $page->title }}</span>
            <span class="accordion-icon text-gray-400 text-xl leading-none select-none">＋</span>
        </button>
        <div class="accordion-body hidden border-t">
            <div class="px-5 py-5">
                <form method="POST" action="{{ route('admin.form_fields.legal', $slug) }}">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">タイトル</label>
                        <input type="text" name="title" value="{{ old('title', $page->title) }}" required
                               class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">本文（Markdownまたはプレーンテキスト）</label>
                        <textarea name="content" rows="20"
                                  class="w-full border rounded px-3 py-2 text-sm font-mono">{{ old('content', $page->content) }}</textarea>
                    </div>
                    <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">保存する</button>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    {{-- 終了キャンセル自動送信 --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <button type="button" class="accordion-header w-full text-left px-5 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
            <span class="font-semibold text-gray-700">終了キャンセル自動送信</span>
            <span class="accordion-icon text-gray-400 text-xl leading-none select-none">＋</span>
        </button>
        <div class="accordion-body hidden border-t">
            <div class="px-5 py-5">
                <p class="text-xs text-gray-600 mb-4">
                    案件を「終了」ステータスに変更した際、打診中・案内予約中の応募を自動的に応募状態へ戻し、対象ユーザーへこの内容でLINE通知を送信します。
                </p>

                <form method="POST" action="{{ route('admin.form_fields.end_cancel_setting') }}">
                    @csrf @method('PATCH')

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">送信時間帯</label>
                        <p class="text-xs text-gray-500 mb-2">この時間内なら即時送信、時間外は開始時刻に自動送信されます。</p>
                        <div class="flex items-center gap-2">
                            <select name="send_start_hour" class="border rounded px-2 py-1.5 text-sm">
                                @for($h = 0; $h < 24; $h++)
                                <option value="{{ $h }}" @selected(old('send_start_hour', $endCancelSetting->send_start_hour) == $h)>{{ $h }}:00</option>
                                @endfor
                            </select>
                            <span class="text-gray-500 text-sm">〜</span>
                            <select name="send_end_hour" class="border rounded px-2 py-1.5 text-sm">
                                @for($h = 0; $h < 24; $h++)
                                <option value="{{ $h }}" @selected(old('send_end_hour', $endCancelSetting->send_end_hour) == $h)>{{ $h }}:00</option>
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
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">終了案内メッセージ</label>
                        <textarea name="message_template" rows="6"
                                  class="w-full border rounded px-3 py-2 text-sm font-mono">{{ old('message_template', $endCancelSetting->message_template) }}</textarea>
                        @error('message_template')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">保存する</button>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
document.querySelectorAll('.accordion-header').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var body = btn.nextElementSibling;
        var icon = btn.querySelector('.accordion-icon');
        var isOpen = !body.classList.contains('hidden');
        body.classList.toggle('hidden', isOpen);
        icon.textContent = isOpen ? '＋' : '－';
    });
});
</script>

@endsection
