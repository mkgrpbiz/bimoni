@extends('layouts.admin')
@section('title', 'ページ編集')
@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-6">ページ編集</h1>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4 text-sm">{{ session('error') }}</div>
@endif

{{-- 利用規約・プライバシーポリシー --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @foreach([['terms', $terms], ['privacy', $privacy]] as [$slug, $page])
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 mb-4">{{ $page->title }}</h2>
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
    @endforeach
</div>

@endsection
