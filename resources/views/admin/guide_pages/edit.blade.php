@extends('layouts.admin')

@section('title', 'ガイドページ編集')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.guide_pages.index') }}"
       class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">← 一覧に戻る</a>
    <h1 class="text-2xl font-bold text-gray-800">{{ $page->title }}</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('admin.guide_pages.update', $page) }}" enctype="multipart/form-data" class="mb-6">
    @csrf
    @method('PUT')
    @include('admin.guide_pages._form')
</form>

{{-- セクション一覧 --}}
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="font-bold text-gray-700 mb-4">セクション一覧</h2>
    <p class="text-xs text-gray-400 mb-3">ドラッグ&ドロップで並び替えできます。各セクションの中に注意書き・ステップを追加できます。</p>

    <div id="sortable-sections" class="space-y-2 mb-4">
        @forelse($page->sections as $section)
        <div class="border rounded-lg p-3 flex items-center gap-3 hover:bg-gray-50" data-id="{{ $section->id }}">
            <span class="cursor-grab text-gray-400 drag-handle select-none">⠿</span>
            <div class="flex-1">
                <a href="{{ route('admin.guide_sections.edit', $section) }}"
                   class="font-medium text-pink-600 hover:text-pink-800 hover:underline">{{ $section->title }}</a>
                <p class="text-xs text-gray-400 mt-0.5">
                    注意書き{{ $section->notes->count() }}件 / ステップ{{ $section->steps->count() }}件
                </p>
            </div>
            @if($section->is_visible)
                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">表示中</span>
            @else
                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">非表示</span>
            @endif
            <a href="{{ route('admin.guide_sections.edit', $section) }}"
               class="text-xs bg-pink-500 text-white px-3 py-1 rounded hover:bg-pink-600">編集</a>
            <form method="POST" action="{{ route('admin.guide_sections.destroy', $section) }}"
                  onsubmit="return confirm('セクションを削除しますか？（中の注意書き・ステップも削除されます）')">
                @csrf @method('DELETE')
                <button type="submit" class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded hover:bg-red-200">削除</button>
            </form>
        </div>
        @empty
        <p class="text-xs text-gray-400 text-center py-6">セクションがまだありません</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('admin.guide_sections.store', $page) }}" class="flex gap-2 pt-3 border-t">
        @csrf
        <input type="text" name="title" required placeholder="新しいセクション名（例: ご利用手順）"
               class="flex-1 border rounded px-3 py-2 text-sm">
        <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded hover:bg-pink-600 text-sm whitespace-nowrap">＋ 追加</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const sectionsList = document.getElementById('sortable-sections');
if (sectionsList) {
    Sortable.create(sectionsList, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function() {
            const ids = [...sectionsList.querySelectorAll('[data-id]')].map(r => r.dataset.id);
            fetch('{{ route('admin.guide_sections.reorder') }}', {
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
</script>
@endsection
