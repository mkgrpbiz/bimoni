@extends('layouts.portal')
@section('title', '設定')
@section('content')
<h1 class="text-xl font-bold text-gray-800 mb-4">設定</h1>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="space-y-4 max-w-lg">

    {{-- 基本情報 --}}
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 mb-4 text-sm">基本情報</h2>

        {{-- 代理店名（変更不可） --}}
        <div class="mb-4">
            <label class="block text-xs font-medium text-gray-500 mb-1">代理店名</label>
            <div class="bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm text-gray-700">{{ $agent->name }}</div>
        </div>

        {{-- ポータルURL --}}
        <div class="mb-4">
            <label class="block text-xs font-medium text-gray-500 mb-1">ポータルURL</label>
            <div class="flex items-center gap-2">
                <code class="text-xs bg-gray-50 border border-gray-200 px-3 py-2 rounded flex-1 break-all text-gray-600">{{ $agent->portalUrl() }}</code>
                <button type="button" onclick="copyText('{{ $agent->portalUrl() }}')"
                        class="bg-gray-700 text-white text-xs px-3 py-2 rounded hover:bg-gray-600 shrink-0">コピー</button>
            </div>
        </div>

        {{-- 招待リンク --}}
        @if($agent->codes->isNotEmpty())
        <div class="mb-1">
            <label class="block text-xs font-medium text-gray-500 mb-2">招待リンク</label>
            <div class="space-y-2">
                @foreach($agent->codes as $code)
                <div>
                    @if($code->label)
                        <p class="text-xs text-gray-400 mb-1">{{ $code->label }}（{{ $code->code }}）</p>
                    @else
                        <p class="text-xs text-gray-400 mb-1">{{ $code->code }}</p>
                    @endif
                    <div class="flex items-center gap-2">
                        <code class="text-xs bg-gray-50 border border-gray-200 px-3 py-2 rounded flex-1 break-all text-gray-600">{{ route('invite', $code->code) }}</code>
                        <button type="button" onclick="copyText('{{ route('invite', $code->code) }}')"
                                class="bg-pink-500 text-white text-xs px-3 py-2 rounded hover:bg-pink-600 shrink-0">コピー</button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- 招待ページ表示名設定 --}}
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 mb-1 text-sm">招待ページ表示名</h2>
        <p class="text-xs text-gray-400 mb-4">招待ページに表示される名前です。未設定の場合は代理店名が使われます。</p>
        <form method="POST" action="{{ route('portal.settings.update') }}">
            @csrf @method('PATCH')
            <div class="mb-4">
                <input type="text" name="invite_display_name"
                       value="{{ old('invite_display_name', $agent->invite_display_name) }}"
                       placeholder="{{ $agent->name }}"
                       maxlength="100"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm @error('invite_display_name') border-red-400 @enderror">
                @error('invite_display_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded text-sm hover:bg-gray-700">更新する</button>
        </form>
    </div>

</div>

<script>
function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => alert('コピーしました'));
    } else {
        const el = document.createElement('textarea');
        el.value = text;
        el.style.position = 'fixed';
        el.style.opacity = '0';
        document.body.appendChild(el);
        el.focus();
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        alert('コピーしました');
    }
}
</script>
@endsection
