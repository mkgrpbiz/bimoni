@extends('layouts.admin')
@section('title', '代理店管理')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">代理店管理</h1>
    <a href="{{ route('admin.agents.create') }}" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">＋ 親代理店を追加</a>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">代理店名</th>
                <th class="px-4 py-3 text-right">子代理店数</th>
                <th class="px-4 py-3 text-right">コード数</th>
                <th class="px-4 py-3 text-right">登録数</th>
                <th class="px-4 py-3 text-right">応募数</th>
                <th class="px-4 py-3 text-right">報告数</th>
                <th class="px-4 py-3 text-center">詳細</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($agents as $agent)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3">
                    <div id="disp-{{ $agent->id }}" class="flex items-center gap-2">
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $agent->name }}</span>
                        <button type="button" onclick="startEdit({{ $agent->id }}, @json($agent->name))"
                                class="text-gray-400 hover:text-pink-500 shrink-0" title="名前を変更">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
                        </button>
                    </div>
                    <form id="form-{{ $agent->id }}" style="display:none" class="items-center gap-1"
                          method="POST" action="{{ route('admin.agents.update', $agent) }}">
                        @csrf @method('PATCH')
                        <input type="text" name="name" value="{{ $agent->name }}"
                               class="border rounded px-2 py-0.5 text-sm font-medium w-48" required maxlength="100">
                        <button type="submit" class="text-xs bg-pink-500 text-white px-2 py-1 rounded hover:bg-pink-600">保存</button>
                        <button type="button" onclick="cancelEdit({{ $agent->id }})" class="text-xs text-gray-400 hover:text-gray-600 px-2 py-1">×</button>
                    </form>
                </td>
                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $agent->children->count() }}</td>
                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $agent->codes->count() }}</td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">{{ $registeredMap[$agent->id] ?? 0 }}</td>
                <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">{{ $appMap[$agent->id] ?? 0 }}</td>
                <td class="px-4 py-3 text-right font-medium text-green-600 dark:text-green-400">{{ $reportMap[$agent->id] ?? 0 }}</td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.agents.show', $agent) }}"
                           class="bg-pink-500 text-white text-xs px-3 py-1 rounded hover:bg-pink-600">詳細</a>
                        @if(($registeredMap[$agent->id] ?? 0) === 0)
                        <form method="POST" action="{{ route('admin.agents.destroy', $agent) }}"
                              onsubmit="return confirm('{{ $agent->name }} を削除しますか？\n子代理店・コードも全て削除されます。')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-400 hover:text-red-600 border border-red-200 rounded px-2 py-1">削除</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500">代理店がまだありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@push('scripts')
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
</script>
@endpush
@endsection
