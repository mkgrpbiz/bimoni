@extends('layouts.admin')
@section('title', 'LINEデフォルト')
@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-2">LINEデフォルトメッセージ</h1>
<p class="text-sm text-gray-500 mb-6">PR媒体ごとのLINE自動送信メッセージのデフォルトを設定します。案件新規作成時にPR媒体を選択すると自動入力されます。</p>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @foreach($prMediaList as $value => $label)
    @php $def = $defaults->get($value); @endphp
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 mb-4">{{ $label }}</h2>
        <form method="POST" action="{{ route('admin.line_message_defaults.update', $value) }}">
            @csrf @method('PATCH')
            <div class="bg-gray-50 border border-gray-200 rounded p-3 mb-3 text-xs text-gray-600">
                <p class="font-medium text-gray-700 mb-1">使用できるコード</p>
                <div class="grid grid-cols-2 gap-1 font-mono">
                    <span>@{{商品名}}</span><span class="text-gray-400">→ 商品名</span>
                    <span>@{{初回購入費}}</span><span class="text-gray-400">→ 初回購入費（円）</span>
                    <span>@{{モニター協力金}}</span><span class="text-gray-400">→ モニター協力金（円）</span>
                    <span>@{{解約について}}</span><span class="text-gray-400">→ 解約についての内容</span>
                    <span>@{{モニター案内文}}</span><span class="text-gray-400">→ モニター案内文の内容</span>
                    <span>@{{リンク}}</span><span class="text-gray-400">→ リンクURL</span>
                    <span>@{{案内日時}}</span><span class="text-gray-400">→ 案内日時（例: 7月4日 10:00〜11:00）</span>
                </div>
            </div>
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">モニター案内メッセージ</label>
                <textarea name="monitor_invite_message" rows="6"
                          class="w-full border rounded px-3 py-2 text-sm font-mono"
                          placeholder="例: @{{商品名}}のモニターご案内です。">{{ old('monitor_invite_message', $def?->monitor_invite_message ?? '') }}</textarea>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">モニター終了案内文</label>
                <textarea name="monitor_end_message" rows="5"
                          class="w-full border rounded px-3 py-2 text-sm font-mono"
                          placeholder="例: @{{商品名}}モニターへのご参加ありがとうございました。">{{ old('monitor_end_message', $def?->monitor_end_message ?? '') }}</textarea>
            </div>
            <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">保存する</button>
        </form>
    </div>
    @endforeach
</div>

@endsection
