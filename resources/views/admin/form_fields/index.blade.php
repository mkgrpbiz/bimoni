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
