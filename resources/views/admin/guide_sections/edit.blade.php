@extends('layouts.admin')

@section('title', 'セクション編集')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.guide_pages.edit', $section->page) }}"
       class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">← {{ $section->page->title }} に戻る</a>
    <h1 class="text-2xl font-bold text-gray-800">{{ $section->title }}</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- セクション基本情報 --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="font-bold text-gray-700 mb-4">セクション基本情報</h2>
    <form method="POST" action="{{ route('admin.guide_sections.update', $section) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">セクション名</label>
            <input type="text" name="title" value="{{ old('title', $section->title) }}"
                   class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">説明文（ステップの前に表示、任意）</label>
            <textarea name="intro_text" rows="4" class="w-full border rounded px-3 py-2 text-sm">{{ old('intro_text', $section->intro_text) }}</textarea>
        </div>
        <div class="flex items-center justify-between pt-2 border-t">
            <form method="POST" action="{{ route('admin.guide_sections.toggle_visible', $section) }}">
                @csrf @method('PATCH')
                <button type="submit"
                        class="text-xs px-3 py-1.5 rounded
                            {{ $section->is_visible
                                ? 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                                : 'bg-pink-500 text-white hover:bg-pink-600' }}">
                    {{ $section->is_visible ? '非表示にする' : '表示にする' }}
                </button>
            </form>
            <button type="submit" class="bg-pink-500 text-white px-6 py-2 rounded hover:bg-pink-600 text-sm">更新する</button>
        </div>
    </form>
</div>

{{-- 注意書きボックス --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="font-bold text-gray-700 mb-1">注意書きボックス</h2>
    <p class="text-xs text-gray-400 mb-4">本文は1行1項目として箇条書き表示されます。ドラッグ&ドロップで並び替えできます。</p>

    <div id="sortable-notes" class="space-y-3 mb-4">
        @forelse($section->notes as $note)
        <div class="border rounded-lg p-3" data-id="{{ $note->id }}">
            <div id="note-disp-{{ $note->id }}" class="flex items-start gap-3">
                <span class="cursor-grab text-gray-400 drag-handle select-none mt-1">⠿</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs px-1.5 py-0.5 rounded {{ $note->style === 'warning' ? 'bg-red-100 text-red-600' : 'bg-pink-100 text-pink-600' }}">
                            {{ $note->style === 'warning' ? '警告色' : '通常色' }}
                        </span>
                        <span class="font-medium text-sm">{{ $note->heading ?: '（見出しなし）' }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 whitespace-pre-wrap">{{ $note->body }}</p>
                </div>
                <button type="button" onclick="showNoteEdit({{ $note->id }})"
                        class="text-xs bg-pink-500 text-white px-2 py-1 rounded hover:bg-pink-600 shrink-0">編集</button>
                <form method="POST" action="{{ route('admin.guide_notes.destroy', $note) }}"
                      onsubmit="return confirm('削除しますか？')" class="shrink-0">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded hover:bg-red-200">削除</button>
                </form>
            </div>
            <form id="note-form-{{ $note->id }}" method="POST" action="{{ route('admin.guide_notes.update', $note) }}" style="display:none" class="space-y-2 mt-2">
                @csrf @method('PUT')
                <input type="text" name="heading" value="{{ $note->heading }}" placeholder="見出し（例: ■ ポイント）" class="w-full border rounded px-2 py-1.5 text-sm">
                <textarea name="body" rows="4" placeholder="1行1項目で入力" class="w-full border rounded px-2 py-1.5 text-sm">{{ $note->body }}</textarea>
                <select name="note_style" class="border rounded px-2 py-1.5 text-sm">
                    <option value="normal" @selected($note->style === 'normal')>通常色（ピンク）</option>
                    <option value="warning" @selected($note->style === 'warning')>警告色（赤）</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="text-xs bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600">保存</button>
                    <button type="button" onclick="cancelNoteEdit({{ $note->id }})" class="text-xs text-gray-500 px-2 py-1.5">キャンセル</button>
                </div>
            </form>
        </div>
        @empty
        <p class="text-xs text-gray-400 text-center py-4">注意書きはまだありません</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('admin.guide_notes.store', $section) }}" class="space-y-2 pt-3 border-t">
        @csrf
        <input type="text" name="heading" placeholder="見出し（例: ■ ポイント、任意）" class="w-full border rounded px-3 py-2 text-sm">
        <textarea name="body" rows="3" placeholder="1行1項目で入力（箇条書きになります）" required class="w-full border rounded px-3 py-2 text-sm"></textarea>
        <div class="flex gap-2 items-center">
            <select name="note_style" class="border rounded px-2 py-2 text-sm">
                <option value="normal">通常色（ピンク）</option>
                <option value="warning">警告色（赤）</option>
            </select>
            <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded hover:bg-pink-600 text-sm">＋ 注意書きを追加</button>
        </div>
    </form>
</div>

{{-- ステップ --}}
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="font-bold text-gray-700 mb-1">ステップ（番号付き手順）</h2>
    <p class="text-xs text-gray-400 mb-4">ドラッグ&ドロップで並び替えできます。</p>

    <div id="sortable-steps" class="space-y-3 mb-4">
        @forelse($section->steps as $i => $step)
        <div class="border rounded-lg p-3" data-id="{{ $step->id }}">
            <div id="step-disp-{{ $step->id }}" class="flex items-start gap-3">
                <span class="cursor-grab text-gray-400 drag-handle select-none mt-1">⠿</span>
                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-pink-500 to-pink-400 text-white flex items-center justify-center font-bold text-sm shrink-0">
                    {{ $i + 1 }}
                </div>
                @if($step->image)
                <img src="{{ asset('storage/' . $step->image) }}" class="w-16 h-16 object-cover rounded border shrink-0">
                @endif
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-sm">{{ $step->title }}</p>
                    <p class="text-xs text-gray-500 mt-1 whitespace-pre-wrap">{{ $step->description }}</p>
                    @if($step->sub_text)
                    <p class="text-xs text-gray-400 mt-1">※ {{ $step->sub_text }}</p>
                    @endif
                </div>
                @if($step->is_visible)
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full shrink-0">表示中</span>
                @else
                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full shrink-0">非表示</span>
                @endif
                <button type="button" onclick="showStepEdit({{ $step->id }})"
                        class="text-xs bg-pink-500 text-white px-2 py-1 rounded hover:bg-pink-600 shrink-0">編集</button>
                <form method="POST" action="{{ route('admin.guide_steps.toggle_visible', $step) }}" class="shrink-0">
                    @csrf @method('PATCH')
                    <button type="submit" class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded hover:bg-gray-300">
                        {{ $step->is_visible ? '非表示' : '表示' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.guide_steps.destroy', $step) }}"
                      onsubmit="return confirm('削除しますか？')" class="shrink-0">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded hover:bg-red-200">削除</button>
                </form>
            </div>
            <form id="step-form-{{ $step->id }}" method="POST" action="{{ route('admin.guide_steps.update', $step) }}"
                  enctype="multipart/form-data" style="display:none" class="space-y-2 mt-2">
                @csrf @method('PUT')
                <input type="text" name="title" value="{{ $step->title }}" placeholder="ステップタイトル" class="w-full border rounded px-2 py-1.5 text-sm">
                <textarea name="description" rows="3" placeholder="説明文" class="w-full border rounded px-2 py-1.5 text-sm">{{ $step->description }}</textarea>
                <input type="text" name="sub_text" value="{{ $step->sub_text }}" placeholder="補足（小さいグレー文字、任意）" class="w-full border rounded px-2 py-1.5 text-sm">
                <input type="file" name="image" accept="image/*" class="w-full border rounded px-2 py-1.5 text-sm">
                <div class="flex gap-2">
                    <button type="submit" class="text-xs bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600">保存</button>
                    <button type="button" onclick="cancelStepEdit({{ $step->id }})" class="text-xs text-gray-500 px-2 py-1.5">キャンセル</button>
                </div>
            </form>
        </div>
        @empty
        <p class="text-xs text-gray-400 text-center py-4">ステップはまだありません</p>
        @endforelse
    </div>

    <form method="POST" action="{{ route('admin.guide_steps.store', $section) }}" enctype="multipart/form-data" class="space-y-2 pt-3 border-t">
        @csrf
        <input type="text" name="title" placeholder="ステップタイトル（例: 商品の梱包）" required class="w-full border rounded px-3 py-2 text-sm">
        <textarea name="description" rows="3" placeholder="説明文" class="w-full border rounded px-3 py-2 text-sm"></textarea>
        <input type="text" name="sub_text" placeholder="補足（小さいグレー文字、任意）" class="w-full border rounded px-3 py-2 text-sm">
        <input type="file" name="image" accept="image/*" class="w-full border rounded px-3 py-2 text-sm">
        <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded hover:bg-pink-600 text-sm">＋ ステップを追加</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function makeSortable(elId, routeUrl) {
    const el = document.getElementById(elId);
    if (!el) return;
    Sortable.create(el, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function() {
            const ids = [...el.querySelectorAll('[data-id]')].map(r => r.dataset.id);
            fetch(routeUrl, {
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
makeSortable('sortable-notes', '{{ route('admin.guide_notes.reorder') }}');
makeSortable('sortable-steps', '{{ route('admin.guide_steps.reorder') }}');

function showNoteEdit(id) {
    document.getElementById('note-disp-' + id).style.display = 'none';
    document.getElementById('note-form-' + id).style.display = 'block';
}
function cancelNoteEdit(id) {
    document.getElementById('note-disp-' + id).style.display = 'flex';
    document.getElementById('note-form-' + id).style.display = 'none';
}
function showStepEdit(id) {
    document.getElementById('step-disp-' + id).style.display = 'none';
    document.getElementById('step-form-' + id).style.display = 'block';
}
function cancelStepEdit(id) {
    document.getElementById('step-disp-' + id).style.display = 'flex';
    document.getElementById('step-form-' + id).style.display = 'none';
}
</script>
@endsection
