@extends('layouts.admin')
@section('title', '代理店詳細')
@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.agents.index') }}" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">← 代理店一覧</a>
    <div id="disp-{{ $agent->id }}" class="flex items-center gap-2">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $agent->name }}</h1>
        <button type="button" onclick='startEdit({{ $agent->id }}, @json($agent->name))'
                class="text-gray-400 hover:text-pink-500" title="名前を変更">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
        </button>
    </div>
    <form id="form-{{ $agent->id }}" style="display:none" class="items-center gap-1"
          method="POST" action="{{ route('admin.agents.update', $agent) }}">
        @csrf @method('PATCH')
        <input type="text" name="name" value="{{ $agent->name }}"
               class="border rounded px-2 py-1 text-lg font-bold w-64" required maxlength="100">
        <button type="submit" class="text-sm bg-pink-500 text-white px-3 py-1 rounded hover:bg-pink-600">保存</button>
        <button type="button" onclick="cancelEdit({{ $agent->id }})" class="text-sm text-gray-400 hover:text-gray-600 px-2 py-1">×</button>
    </form>
    @php $agentHasUsers = \App\Models\User::whereIn('referred_by_code', $agent->getAllCodeStrings())->exists(); @endphp
    @if(!$agentHasUsers)
    <form method="POST" action="{{ route('admin.agents.destroy', $agent) }}" class="ml-auto"
          onsubmit="return confirm('{{ $agent->name }} を削除しますか？\n子代理店・コードも全て削除されます。')">
        @csrf @method('DELETE')
        <button type="submit" class="text-xs text-red-400 hover:text-red-600 border border-red-300 rounded px-3 py-1.5">この代理店を削除</button>
    </form>
    @endif
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
        <div class="space-y-2 mb-3">
            @foreach($sortedCodes as $index => $code)
            @php $hasUsers = \App\Models\User::where('referred_by_code', $code->code)->exists(); @endphp
            <div class="{{ $index > 0 ? 'code-extra hidden' : '' }} flex items-center gap-2 flex-wrap">
                <span class="font-mono font-bold text-pink-600 dark:text-pink-400">{{ $code->code }}</span>
                @if($code->label)
                    <span class="text-xs text-gray-500">{{ $code->label }}</span>
                @endif
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $userCounts[$code->code] ?? 0 }}名</span>
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
        @if($sortedCodes->count() > 1)
        <button type="button" id="toggle-codes-btn"
                onclick="toggleCodes()"
                class="text-xs text-pink-500 hover:underline mb-4">
            他{{ $sortedCodes->count() - 1 }}件を表示
        </button>
        @endif
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
                <th class="px-4 py-3 text-center">削除</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($agent->children as $child)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3">
                    <div id="disp-{{ $child->id }}" class="flex items-center gap-1">
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $child->name }}</span>
                        <button type="button" onclick='startEdit({{ $child->id }}, @json($child->name))'
                                class="text-gray-400 hover:text-pink-500 shrink-0" title="名前を変更">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
                        </button>
                    </div>
                    <form id="form-{{ $child->id }}" style="display:none" class="items-center gap-1"
                          method="POST" action="{{ route('admin.agents.update', $child) }}">
                        @csrf @method('PATCH')
                        <input type="text" name="name" value="{{ $child->name }}"
                               class="border rounded px-2 py-0.5 text-sm font-medium w-40" required maxlength="100">
                        <button type="submit" class="text-xs bg-pink-500 text-white px-2 py-1 rounded hover:bg-pink-600">保存</button>
                        <button type="button" onclick="cancelEdit({{ $child->id }})" class="text-xs text-gray-400 px-1">×</button>
                    </form>
                </td>
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
                <td class="px-4 py-3 text-center">
                    @php $childHasUsers = \App\Models\User::whereIn('referred_by_code', $child->codes->pluck('code')->toArray())->exists(); @endphp
                    @if(!$childHasUsers)
                    <form method="POST" action="{{ route('admin.agents.destroy', $child) }}"
                          onsubmit="return confirm('{{ $child->name }} を削除しますか？')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-400 hover:text-red-600 border border-red-200 rounded px-2 py-1">削除</button>
                    </form>
                    @else
                    <span class="text-xs text-gray-300">登録者あり</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-6 text-center text-gray-500">子代理店はまだありません（親ポータルから作成できます）</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@push('scripts')
<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex z-50 shadow-lg">
    <a href="{{ route('admin.agents.index') }}"
       class="flex-1 flex flex-col items-center gap-0.5 py-2 text-xs text-gray-500">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        一覧
    </a>
    <a href="{{ route('admin.agents.show', $agent) }}"
       class="flex-1 flex flex-col items-center gap-0.5 py-2 text-xs text-pink-500 font-bold">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        代理店
    </a>
    <a href="{{ route('admin.referrals.index') }}"
       class="flex-1 flex flex-col items-center gap-0.5 py-2 text-xs text-gray-500">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        紹介報酬
    </a>
    <a href="{{ route('admin.dashboard') }}"
       class="flex-1 flex flex-col items-center gap-0.5 py-2 text-xs text-gray-500">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        管理TOP
    </a>
</nav>
<script>
function startEdit(id, name) {
    document.getElementById('disp-' + id).style.display = 'none';
    const form = document.getElementById('form-' + id);
    form.style.display = 'flex';
    form.querySelector('input[name=name]').value = name;
    form.querySelector('input[name=name]').focus();
}
function cancelEdit(id) {
    document.getElementById('disp-' + id).style.display = '';
    document.getElementById('form-' + id).style.display = 'none';
}
function toggleCodes() {
    const extras = document.querySelectorAll('.code-extra');
    const btn = document.getElementById('toggle-codes-btn');
    const isHidden = extras[0].classList.contains('hidden');
    extras.forEach(el => el.classList.toggle('hidden', !isHidden));
    btn.textContent = isHidden ? '折りたたむ' : '他' + extras.length + '件を表示';
}
function copyUrl(url) {
    try {
        const el = document.createElement('textarea');
        el.value = url;
        el.style.cssText = 'position:fixed;top:0;left:0;opacity:0;pointer-events:none;';
        document.body.appendChild(el);
        el.focus();
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    } catch(e) {
        if (navigator.clipboard) navigator.clipboard.writeText(url).catch(() => {});
    }
    alert('コピーしました');
}
</script>
@endpush
@endsection
