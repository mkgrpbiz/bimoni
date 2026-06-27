@extends('layouts.portal')
@section('title', '子代理店管理')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-lg font-bold text-gray-800">子代理店管理</h1>
    <a href="{{ route('portal.children.create') }}" class="bg-gray-800 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">＋ 追加</a>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="space-y-4">
    @forelse($agent->children as $child)
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-bold text-gray-800">{{ $child->name }}</h2>
            <p class="text-xs text-gray-500">
                500円→¥{{ number_format($child->child_reward_500) }} ／ 1000円→¥{{ number_format($child->child_reward_1000) }}
            </p>
        </div>
        <div class="mb-3">
            <p class="text-xs text-gray-500 mb-1">ポータルURL</p>
            <div class="flex items-center gap-2">
                <code class="text-xs bg-gray-100 px-2 py-1.5 rounded flex-1 break-all leading-relaxed">{{ $child->portalUrl() }}</code>
                <button onclick="portalCopy('{{ $child->portalUrl() }}')"
                        class="bg-gray-800 text-white text-xs px-3 py-1.5 rounded hover:bg-gray-700 shrink-0">コピー</button>
            </div>
        </div>
        <div>
            <p class="text-xs text-gray-500 mb-2">紹介コード</p>
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach($child->codes as $code)
                @php $hasUsers = \App\Models\User::where('referred_by_code', $code->code)->exists(); @endphp
                <div class="flex items-center gap-1 bg-gray-100 rounded px-3 py-1">
                    <span class="font-mono font-bold text-sm">{{ $code->code }}</span>
                    @if(!$hasUsers)
                    <form method="POST" action="{{ route('portal.children.delete_code', $code) }}"
                          onsubmit="return confirm('コード {{ $code->code }} を削除しますか？')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs ml-1 leading-none">✕</button>
                    </form>
                    @endif
                </div>
                @endforeach
            </div>
            <form method="POST" action="{{ route('portal.children.add_code', $child) }}" class="flex gap-2">
                @csrf
                <input type="text" name="code" maxlength="20" placeholder="コード（空欄=自動生成）"
                       class="border rounded px-2 py-1 text-xs font-mono w-44">
                <button type="submit" class="text-xs text-blue-600 border border-blue-300 px-2 py-1 rounded hover:bg-blue-50">＋追加</button>
            </form>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-400">
        <p class="mb-2">子代理店がまだいません</p>
        <a href="{{ route('portal.children.create') }}" class="text-sm text-blue-600 hover:underline">作成する</a>
    </div>
    @endforelse
</div>
@endsection

<script>
function portalCopy(text) {
    const done = () => alert('コピーしました');
    const fallback = () => {
        const el = document.createElement('textarea');
        el.value = text; el.style.position = 'fixed'; el.style.opacity = '0';
        document.body.appendChild(el); el.focus(); el.select();
        document.execCommand('copy'); document.body.removeChild(el); done();
    };
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(done).catch(fallback);
    } else {
        fallback();
    }
}
</script>
