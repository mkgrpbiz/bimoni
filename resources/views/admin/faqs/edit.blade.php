@extends('layouts.admin')

@section('title', 'FAQ編集')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.faqs.index', ['category' => $faq->category]) }}"
       class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">← 一覧に戻る</a>
    <h1 class="text-2xl font-bold text-gray-800">FAQ編集</h1>
</div>

<form method="POST" action="{{ route('admin.faqs.update', $faq) }}">
    @csrf
    @method('PUT')
    @include('admin.faqs._form')
</form>
@endsection
