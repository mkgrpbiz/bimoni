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

@php
$lineCodes = [
    '@{{商品名}}'       => '商品名',
    '@{{初回購入費}}'   => '初回購入費（円）',
    '@{{モニター協力金}}' => 'モニター協力金（円）',
    '@{{解約について}}' => '解約についての内容',
    '@{{モニター案内文}}' => 'モニター案内文の内容',
    '@{{リンク}}'       => 'リンクURL',
    '@{{案内日時}}'     => '案内日時（例: 7月4日 10:00〜11:00）',
];
@endphp

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

    {{-- PR媒体別 LINEデフォルト --}}
    @foreach($prMediaList as $value => $label)
    @php $def = $lineMessageDefaults->get($value); @endphp
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <button type="button" class="accordion-header w-full text-left px-5 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
            <span class="font-semibold text-gray-700">LINE自動送信デフォルト　<span class="text-pink-600">{{ $label }}</span></span>
            <span class="accordion-icon text-gray-400 text-xl leading-none select-none">＋</span>
        </button>
        <div class="accordion-body hidden border-t">
            <div class="px-5 py-5">
                {{-- 使用できるコード --}}
                <div class="bg-gray-50 rounded px-4 py-3 mb-4 text-xs text-gray-600">
                    <p class="font-medium mb-2">使用できるコード</p>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                        @foreach($lineCodes as $code => $desc)
                        <div class="flex gap-2">
                            <code class="text-pink-700 font-mono">{{ $code }}</code>
                            <span class="text-gray-400">→ {{ $desc }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.line_message_defaults.update', $value) }}">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">モニター案内メッセージ</label>
                        <textarea name="monitor_invite_message" rows="6"
                                  class="w-full border rounded px-3 py-2 text-sm font-mono"
                                  placeholder="例: @{{商品名}}のモニターご案内です。&#10;@{{モニター案内文}}&#10;詳細はこちら: @{{リンク}}">{{ old('monitor_invite_message', $def?->monitor_invite_message ?? '') }}</textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">モニター終了案内文</label>
                        <textarea name="monitor_end_message" rows="5"
                                  class="w-full border rounded px-3 py-2 text-sm font-mono"
                                  placeholder="例: @{{商品名}}モニターへのご参加ありがとうございました。&#10;ご報告をお願いします。">{{ old('monitor_end_message', $def?->monitor_end_message ?? '') }}</textarea>
                    </div>
                    <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">保存する</button>
                </form>
            </div>
        </div>
    </div>
    @endforeach

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
