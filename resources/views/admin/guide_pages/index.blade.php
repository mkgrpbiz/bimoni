@extends('layouts.admin')

@section('title', 'ガイドページ管理')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold text-gray-800">ガイドページ管理</h1>
    <a href="{{ route('admin.guide_pages.create') }}"
       class="bg-pink-500 text-white px-4 py-2 rounded hover:bg-pink-600 text-sm">
        ＋ 新規ページ追加
    </a>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<p class="text-xs text-gray-400 mb-3">ドラッグ&ドロップで並び替えできます</p>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-800 text-xs">
            <tr>
                <th class="px-3 py-3 w-6"></th>
                <th class="px-4 py-3 text-left">タイトル</th>
                <th class="px-4 py-3 text-left">会員向けURL</th>
                <th class="px-3 py-3 text-center">表示状況</th>
                <th class="px-3 py-3 text-center">操作</th>
            </tr>
        </thead>
        <tbody id="sortable-pages" class="divide-y">
            @forelse($pages as $page)
            <tr class="hover:bg-gray-50" data-id="{{ $page->id }}">
                <td class="px-3 py-3 text-center cursor-grab text-gray-800 drag-handle select-none">⠿</td>
                <td class="px-4 py-3 font-medium">
                    <a href="{{ route('admin.guide_pages.edit', $page) }}"
                       class="font-medium text-pink-600 hover:text-pink-800 hover:underline">{{ $page->title }}</a>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <code class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded break-all">{{ route('member.guide', $page->slug) }}</code>
                        <button type="button" onclick="copyUrl('{{ route('member.guide', $page->slug) }}')"
                                class="bg-pink-500 text-white text-xs px-2 py-1 rounded hover:bg-pink-600 shrink-0">コピー</button>
                    </div>
                </td>
                <td class="px-3 py-3 text-center">
                    @if($page->is_visible)
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">表示中</span>
                    @else
                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">非表示</span>
                    @endif
                </td>
                <td class="px-3 py-3">
                    <div class="flex gap-1 justify-center flex-wrap">
                        <form method="POST" action="{{ route('admin.guide_pages.toggle_visible', $page) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="text-xs px-2 py-1 rounded
                                        {{ $page->is_visible
                                            ? 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                                            : 'bg-pink-500 text-white hover:bg-pink-600' }}">
                                {{ $page->is_visible ? '非表示にする' : '表示にする' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.guide_pages.destroy', $page) }}" class="inline"
                              onsubmit="return confirm('ページごと削除しますか？（セクション・ステップも全て削除されます）')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded hover:bg-red-200">削除</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-400">ページがありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- SortableJS --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const tbody = document.getElementById('sortable-pages');
if (tbody) {
    Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function() {
            const ids = [...tbody.querySelectorAll('tr[data-id]')].map(r => r.dataset.id);
            fetch('{{ route('admin.guide_pages.reorder') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ ids }),
            });
        },
    });
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
@endsection
