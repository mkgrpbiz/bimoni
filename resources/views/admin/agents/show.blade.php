@extends('layouts.admin')
@section('title', '代理店詳細')
@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.agents.index') }}" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">← 代理店一覧</a>
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $agent->name }}</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    {{-- ポータルURL --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">ポータルURL</h2>
        <div class="flex items-center gap-2">
            <code class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 px-3 py-2 rounded flex-1 break-all">
                {{ $agent->portalUrl() }}
            </code>
            <button type="button" onclick="copyUrl('{{ $agent->portalUrl() }}')"
                    class="bg-pink-500 text-white text-xs px-3 py-1.5 rounded hover:bg-pink-600 shrink-0">コピー</button>
        </div>
    </div>

    {{-- 紹介コード一覧 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">紹介コード</h2>
        @if(session('error'))
            <div class="bg-red-100 text-red-700 px-3 py-2 rounded text-xs mb-3">{{ session('error') }}</div>
        @endif
        <div class="space-y-2 mb-4">
            @foreach($agent->codes as $code)
            @php $hasUsers = \App\Models\User::where('referred_by_code', $code->code)->exists(); @endphp
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-mono font-bold text-pink-600 dark:text-pink-400">{{ $code->code }}</span>
                @if($code->label)
                    <span class="text-xs text-gray-500">{{ $code->label }}</span>
                @endif
                <code class="text-xs text-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-gray-300 px-2 py-0.5 rounded truncate max-w-xs">{{ route('invite', $code->code) }}</code>
                <button type="button" onclick="copyUrl('{{ route('invite', $code->code) }}')"
                        class="text-xs bg-pink-500 text-white px-2 py-0.5 rounded hover:bg-pink-600 shrink-0">コピー</button>
                @if(!$hasUsers)
                <form method="POST" action="{{ route('admin.agents.delete_code', $code) }}" onsubmit="return confirm('コード {{ $code->code }} を削除しますか？')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 border border-red-200 rounded px-1.5 py-0.5">削除</button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
        <form method="POST" action="{{ route('admin.agents.add_code', $agent) }}" class="flex items-end gap-2">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">コード（空欄=自動生成）</label>
                <input type="text" name="code" maxlength="20" placeholder="例: ABC123"
                       class="border rounded px-2 py-1 text-sm font-mono w-36">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">ラベル</label>
                <input type="text" name="label" maxlength="100" placeholder="任意"
                       class="border rounded px-2 py-1 text-sm w-32">
            </div>
            <button type="submit" class="bg-pink-500 text-white text-xs px-3 py-2 rounded hover:bg-pink-600">＋ 追加</button>
        </form>
    </div>
</div>

{{-- 子代理店一覧 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <div class="px-5 py-3 border-b dark:border-gray-700 flex items-center justify-between">
        <h2 class="font-bold text-gray-700 dark:text-gray-200">子代理店一覧</h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">代理店名</th>
                <th class="px-4 py-3 text-left">コード</th>
                <th class="px-4 py-3 text-right">500円報酬</th>
                <th class="px-4 py-3 text-right">1000円報酬</th>
                <th class="px-4 py-3 text-left">招待URL</th>
                <th class="px-4 py-3 text-left">ポータルURL</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($agent->children as $child)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $child->name }}</td>
                <td class="px-4 py-3 font-mono text-xs text-gray-800 dark:text-gray-200">
                    {{ $child->codes->pluck('code')->join(', ') }}
                </td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">¥{{ number_format($child->child_reward_500) }}</td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">¥{{ number_format($child->child_reward_1000) }}</td>
                <td class="px-4 py-3">
                    @foreach($child->codes as $cc)
                    <div class="flex items-center gap-1 mb-1">
                        <code class="text-xs text-gray-500 truncate max-w-36">{{ route('invite', $cc->code) }}</code>
                        <button type="button" onclick="copyUrl('{{ route('invite', $cc->code) }}')"
                                class="bg-pink-500 text-white text-xs px-2 py-0.5 rounded hover:bg-pink-600 shrink-0">コピー</button>
                    </div>
                    @endforeach
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <code class="text-xs text-gray-500 truncate max-w-36">{{ $child->portalUrl() }}</code>
                        <button type="button" onclick="copyUrl('{{ $child->portalUrl() }}')"
                                class="bg-gray-500 text-white text-xs px-2 py-0.5 rounded hover:bg-gray-600 shrink-0">コピー</button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-6 text-center text-gray-500">子代理店はまだありません（親ポータルから作成できます）</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@push('scripts')
<script>
function copyUrl(url) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(() => alert('コピーしました'));
    } else {
        const el = document.createElement('textarea');
        el.value = url;
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
@endpush
@endsection
